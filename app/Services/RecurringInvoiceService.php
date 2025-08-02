<?php

namespace App\Services;

use App\Models\RecurringInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecurringInvoiceService
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Create a new recurring invoice.
     */
    public function createRecurringInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Calculate next invoice date
            $startDate = Carbon::parse($data['start_date']);
            $nextInvoiceDate = $this->calculateNextDate($startDate, $data['frequency'], $data['interval'] ?? 1);

            return RecurringInvoice::create([
                'customer_id' => $data['customer_id'],
                'template_id' => $data['template_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'frequency' => $data['frequency'],
                'interval' => $data['interval'] ?? 1,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'next_invoice_date' => $nextInvoiceDate,
                'max_invoices' => $data['max_invoices'] ?? null,
                'amount' => $data['amount'],
                'language' => $data['language'] ?? 'en',
                'is_active' => $data['is_active'] ?? true,
                'invoice_data' => $data['invoice_data'],
            ]);
        });
    }

    /**
     * Update a recurring invoice.
     */
    public function updateRecurringInvoice(RecurringInvoice $recurringInvoice, array $data)
    {
        return DB::transaction(function () use ($recurringInvoice, $data) {
            // If frequency or interval changed, recalculate next invoice date
            if (isset($data['frequency']) || isset($data['interval'])) {
                $frequency = $data['frequency'] ?? $recurringInvoice->frequency;
                $interval = $data['interval'] ?? $recurringInvoice->interval;
                $data['next_invoice_date'] = $this->calculateNextDate(
                    Carbon::parse($recurringInvoice->next_invoice_date),
                    $frequency,
                    $interval
                );
            }

            $recurringInvoice->update([
                'customer_id' => $data['customer_id'] ?? $recurringInvoice->customer_id,
                'template_id' => $data['template_id'] ?? $recurringInvoice->template_id,
                'name' => $data['name'] ?? $recurringInvoice->name,
                'description' => $data['description'] ?? $recurringInvoice->description,
                'frequency' => $data['frequency'] ?? $recurringInvoice->frequency,
                'interval' => $data['interval'] ?? $recurringInvoice->interval,
                'start_date' => $data['start_date'] ?? $recurringInvoice->start_date,
                'end_date' => $data['end_date'] ?? $recurringInvoice->end_date,
                'next_invoice_date' => $data['next_invoice_date'] ?? $recurringInvoice->next_invoice_date,
                'max_invoices' => $data['max_invoices'] ?? $recurringInvoice->max_invoices,
                'amount' => $data['amount'] ?? $recurringInvoice->amount,
                'language' => $data['language'] ?? $recurringInvoice->language,
                'is_active' => $data['is_active'] ?? $recurringInvoice->is_active,
                'invoice_data' => $data['invoice_data'] ?? $recurringInvoice->invoice_data,
            ]);

            return $recurringInvoice;
        });
    }

    /**
     * Process due recurring invoices.
     */
    public function processDueRecurringInvoices()
    {
        $dueRecurringInvoices = RecurringInvoice::due()->get();
        $results = [];

        foreach ($dueRecurringInvoices as $recurringInvoice) {
            try {
                $result = $this->generateInvoiceFromRecurring($recurringInvoice);
                $results[] = [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'success' => true,
                    'invoice_id' => $result['invoice']->id,
                    'invoice_number' => $result['invoice']->invoice_number,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate an invoice from a recurring invoice.
     */
    public function generateInvoiceFromRecurring(RecurringInvoice $recurringInvoice)
    {
        return DB::transaction(function () use ($recurringInvoice) {
            // Check if should be deactivated
            if ($recurringInvoice->shouldDeactivate()) {
                $recurringInvoice->update(['is_active' => false]);
                throw new \Exception('Recurring invoice has reached its limit and has been deactivated');
            }

            // Prepare invoice data
            $invoiceData = array_merge($recurringInvoice->invoice_data, [
                'customer_id' => $recurringInvoice->customer_id,
                'template_id' => $recurringInvoice->template_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'language' => $recurringInvoice->language,
                'status' => 'draft',
            ]);

            // Create the invoice
            $invoice = $this->invoiceService->createInvoice($invoiceData);

            // Update recurring invoice
            $nextInvoiceDate = $this->calculateNextDate(
                Carbon::parse($recurringInvoice->next_invoice_date),
                $recurringInvoice->frequency,
                $recurringInvoice->interval
            );

            $recurringInvoice->update([
                'next_invoice_date' => $nextInvoiceDate,
                'invoices_generated' => $recurringInvoice->invoices_generated + 1,
            ]);

            // Check if should be deactivated after generation
            if ($recurringInvoice->shouldDeactivate()) {
                $recurringInvoice->update(['is_active' => false]);
            }

            return [
                'invoice' => $invoice,
                'recurring_invoice' => $recurringInvoice,
            ];
        });
    }

    /**
     * Calculate next date based on frequency and interval.
     */
    protected function calculateNextDate(Carbon $currentDate, $frequency, $interval)
    {
        switch ($frequency) {
            case 'daily':
                return $currentDate->addDays($interval)->toDateString();
            case 'weekly':
                return $currentDate->addWeeks($interval)->toDateString();
            case 'monthly':
                return $currentDate->addMonths($interval)->toDateString();
            case 'quarterly':
                return $currentDate->addMonths($interval * 3)->toDateString();
            case 'yearly':
                return $currentDate->addYears($interval)->toDateString();
            default:
                throw new \InvalidArgumentException("Invalid frequency: {$frequency}");
        }
    }

    /**
     * Get recurring invoices with filtering.
     */
    public function getRecurringInvoicesWithFilters(array $filters = [])
    {
        $query = RecurringInvoice::with(['customer', 'template']);

        // Filter by customer
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by active status
        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Filter by frequency
        if (isset($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        // Filter by language
        if (isset($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        // Filter by due date
        if (isset($filters['due_soon'])) {
            $daysAhead = $filters['due_soon'];
            $query->where('next_invoice_date', '<=', now()->addDays($daysAhead)->toDateString());
        }

        // Search by name
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'next_invoice_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Pause a recurring invoice.
     */
    public function pauseRecurringInvoice(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->update(['is_active' => false]);
        return $recurringInvoice;
    }

    /**
     * Resume a recurring invoice.
     */
    public function resumeRecurringInvoice(RecurringInvoice $recurringInvoice)
    {
        // Recalculate next invoice date if it's in the past
        if (Carbon::parse($recurringInvoice->next_invoice_date)->isPast()) {
            $nextDate = $this->calculateNextDate(
                now(),
                $recurringInvoice->frequency,
                $recurringInvoice->interval
            );
            $recurringInvoice->update([
                'next_invoice_date' => $nextDate,
                'is_active' => true,
            ]);
        } else {
            $recurringInvoice->update(['is_active' => true]);
        }

        return $recurringInvoice;
    }

    /**
     * Delete a recurring invoice.
     */
    public function deleteRecurringInvoice(RecurringInvoice $recurringInvoice)
    {
        return $recurringInvoice->delete();
    }

    /**
     * Get upcoming recurring invoices.
     */
    public function getUpcomingInvoices($days = 7)
    {
        return RecurringInvoice::active()
            ->where('next_invoice_date', '<=', now()->addDays($days)->toDateString())
            ->where('next_invoice_date', '>=', now()->toDateString())
            ->with(['customer', 'template'])
            ->orderBy('next_invoice_date')
            ->get();
    }

    /**
     * Get recurring invoice statistics.
     */
    public function getRecurringInvoiceStats()
    {
        return [
            'total_active' => RecurringInvoice::active()->count(),
            'total_paused' => RecurringInvoice::where('is_active', false)->count(),
            'due_today' => RecurringInvoice::due()->whereDate('next_invoice_date', now()->toDateString())->count(),
            'due_this_week' => RecurringInvoice::due()->whereBetween('next_invoice_date', [
                now()->toDateString(),
                now()->addWeek()->toDateString()
            ])->count(),
            'total_generated_this_month' => RecurringInvoice::whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('invoices_generated'),
        ];
    }
}