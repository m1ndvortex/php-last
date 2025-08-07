<?php

namespace App\Jobs;

use App\Models\Communication;
use App\Models\Customer;
use App\Services\CommunicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCommunicationJob implements ShouldQueue
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
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $customerId,
        public string $type,
        public string $message,
        public array $data = []
    ) {
        $this->onQueue('communications');
    }

    /**
     * Execute the job.
     */
    public function handle(CommunicationService $communicationService): void
    {
        try {
            $customer = Customer::findOrFail($this->customerId);
            
            Log::info('Processing communication job', [
                'customer_id' => $this->customerId,
                'type' => $this->type,
                'customer_language' => $customer->preferred_language
            ]);

            // Create communication record
            $communication = Communication::create([
                'customer_id' => $this->customerId,
                'type' => $this->type,
                'message' => $this->message,
                'status' => 'pending',
                'scheduled_at' => now(),
                'data' => $this->data
            ]);

            // Send based on communication type
            $result = match ($this->type) {
                'whatsapp' => $this->sendWhatsApp($communicationService, $customer, $communication),
                'sms' => $this->sendSMS($communicationService, $customer, $communication),
                'email' => $this->sendEmail($communicationService, $customer, $communication),
                default => throw new \InvalidArgumentException("Unsupported communication type: {$this->type}")
            };

            // Update communication status
            $communication->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'sent_at' => $result['success'] ? now() : null,
                'error_message' => $result['error'] ?? null,
                'external_id' => $result['external_id'] ?? null
            ]);

            Log::info('Communication sent successfully', [
                'communication_id' => $communication->id,
                'type' => $this->type,
                'status' => $communication->status
            ]);

        } catch (\Exception $e) {
            Log::error('Communication job failed', [
                'customer_id' => $this->customerId,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Send WhatsApp message
     */
    private function sendWhatsApp(CommunicationService $service, Customer $customer, Communication $communication): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        return $service->sendWhatsAppMessage(
            $customer->phone,
            $this->message,
            $customer->preferred_language,
            $this->data
        );
    }

    /**
     * Send SMS message
     */
    private function sendSMS(CommunicationService $service, Customer $customer, Communication $communication): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        return $service->sendSMS(
            $customer->phone,
            $this->message,
            $customer->preferred_language
        );
    }

    /**
     * Send email message
     */
    private function sendEmail(CommunicationService $service, Customer $customer, Communication $communication): array
    {
        if (empty($customer->email)) {
            return ['success' => false, 'error' => 'Customer email not available'];
        }

        return $service->sendEmailDirect(
            $customer->email,
            $this->data['subject'] ?? 'Message from ' . config('app.business_name'),
            $this->message,
            $customer->preferred_language,
            $this->data
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendCommunicationJob failed', [
            'customer_id' => $this->customerId,
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Update communication record if it exists
        $communication = Communication::where('customer_id', $this->customerId)
            ->where('type', $this->type)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($communication) {
            $communication->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage()
            ]);
        }
    }
}
