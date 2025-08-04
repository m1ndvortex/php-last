<?php

namespace App\Jobs;

use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $recurringInvoiceId = null
    ) {
        $this->onQueue('invoices');
    }

    /**
     * Execute the job.
     */
    public function handle(RecurringInvoiceService $recurringInvoiceService): void
    {
        try {
            Log::info('Processing recurring invoices job started');

            if ($this->recurringInvoiceId) {
                // Process specific recurring invoice
                $recurringInvoice = RecurringInvoice::findOrFail($this->recurringInvoiceId);
                $this->processRecurringInvoice($recurringInvoiceService, $recurringInvoice);
            } else {
                // Process all due recurring invoices
                $this->processAllDueRecurringInvoices($recurringInvoiceService);
            }

            Log::info('Processing recurring invoices job completed');

        } catch (\Exception $e) {
            Log::error('ProcessRecurringInvoicesJob failed', [
                'recurring_invoice_id' => $this->recurringInvoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process all due recurring invoices
     */
    private function processAllDueRecurringInvoices(RecurringInvoiceService $service): void
    {
        $dueInvoices = RecurringInvoice::where('is_active', true)
            ->where('next_invoice_date', '<=', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('max_invoices')
                    ->orWhereColumn('invoices_generated', '<', 'max_invoices');
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::today());
            })
            ->get();

        Log::info('Found due recurring invoices', ['count' => $dueInvoices->count()]);

        foreach ($dueInvoices as $recurringInvoice) {
            try {
                $this->processRecurringInvoice($service, $recurringInvoice);
            } catch (\Exception $e) {
                Log::error('Failed to process recurring invoice', [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'error' => $e->getMessage()
                ]);
                // Continue processing other invoices
            }
        }
    }

    /**
     * Process a single recurring invoice
     */
    private function processRecurringInvoice(RecurringInvoiceService $service, RecurringInvoice $recurringInvoice): void
    {
        Log::info('Processing recurring invoice', [
            'id' => $recurringInvoice->id,
            'name' => $recurringInvoice->name,
            'next_date' => $recurringInvoice->next_invoice_date
        ]);

        // Check if invoice should be generated
        if (!$this->shouldGenerateInvoice($recurringInvoice)) {
            Log::info('Skipping recurring invoice - conditions not met', [
                'id' => $recurringInvoice->id
            ]);
            return;
        }

        // Generate the invoice
        $invoice = $service->generateInvoiceFromRecurring($recurringInvoice);

        if ($invoice) {
            Log::info('Generated invoice from recurring template', [
                'recurring_invoice_id' => $recurringInvoice->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);

            // Update recurring invoice counters and next date
            $this->updateRecurringInvoice($recurringInvoice);

            // Send notification if configured
            if ($recurringInvoice->customer->preferred_communication_method) {
                $this->scheduleInvoiceNotification($invoice);
            }
        }
    }

    /**
     * Check if invoice should be generated
     */
    private function shouldGenerateInvoice(RecurringInvoice $recurringInvoice): bool
    {
        // Check if active
        if (!$recurringInvoice->is_active) {
            return false;
        }

        // Check if within date range
        if ($recurringInvoice->end_date && Carbon::parse($recurringInvoice->end_date)->lt(Carbon::today())) {
            return false;
        }

        // Check if max invoices reached
        if ($recurringInvoice->max_invoices && $recurringInvoice->invoices_generated >= $recurringInvoice->max_invoices) {
            return false;
        }

        // Check if due date has passed
        if (Carbon::parse($recurringInvoice->next_invoice_date)->gt(Carbon::today())) {
            return false;
        }

        return true;
    }

    /**
     * Update recurring invoice after generation
     */
    private function updateRecurringInvoice(RecurringInvoice $recurringInvoice): void
    {
        $nextDate = $this->calculateNextInvoiceDate($recurringInvoice);
        
        $recurringInvoice->update([
            'invoices_generated' => $recurringInvoice->invoices_generated + 1,
            'next_invoice_date' => $nextDate,
            'last_generated_at' => now()
        ]);

        // Deactivate if max invoices reached
        if ($recurringInvoice->max_invoices && $recurringInvoice->invoices_generated >= $recurringInvoice->max_invoices) {
            $recurringInvoice->update(['is_active' => false]);
            Log::info('Deactivated recurring invoice - max invoices reached', [
                'id' => $recurringInvoice->id
            ]);
        }
    }

    /**
     * Calculate next invoice date based on frequency
     */
    private function calculateNextInvoiceDate(RecurringInvoice $recurringInvoice): Carbon
    {
        $currentDate = Carbon::parse($recurringInvoice->next_invoice_date);
        $interval = $recurringInvoice->interval;

        return match ($recurringInvoice->frequency) {
            'daily' => $currentDate->addDays($interval),
            'weekly' => $currentDate->addWeeks($interval),
            'monthly' => $currentDate->addMonths($interval),
            'quarterly' => $currentDate->addMonths($interval * 3),
            'yearly' => $currentDate->addYears($interval),
            default => $currentDate->addMonths($interval)
        };
    }

    /**
     * Schedule invoice notification
     */
    private function scheduleInvoiceNotification($invoice): void
    {
        $customer = $invoice->customer;
        
        if ($customer->phone && in_array($customer->preferred_communication_method, ['whatsapp', 'sms'])) {
            $message = $customer->preferred_language === 'fa' 
                ? "فاکتور جدید شماره {$invoice->invoice_number} برای شما صادر شد."
                : "New invoice #{$invoice->invoice_number} has been generated for you.";

            SendCommunicationJob::dispatch(
                $customer->id,
                $customer->preferred_communication_method,
                $message,
                ['invoice_id' => $invoice->id]
            )->delay(now()->addMinutes(5));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRecurringInvoicesJob failed', [
            'recurring_invoice_id' => $this->recurringInvoiceId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
