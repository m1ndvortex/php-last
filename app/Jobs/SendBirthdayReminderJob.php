<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\MessageTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendBirthdayReminderJob implements ShouldQueue
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
        public ?int $customerId = null,
        public string $reminderType = 'birthday'
    ) {
        $this->onQueue('reminders');
    }

    /**
     * Execute the job.
     */
    public function handle(MessageTemplateService $templateService): void
    {
        try {
            Log::info('Processing birthday/anniversary reminders', [
                'customer_id' => $this->customerId,
                'type' => $this->reminderType
            ]);

            if ($this->customerId) {
                // Process specific customer
                $customer = Customer::findOrFail($this->customerId);
                $this->processCustomerReminder($templateService, $customer);
            } else {
                // Process all customers with birthdays/anniversaries today
                $this->processAllReminders($templateService);
            }

            Log::info('Birthday/anniversary reminders processing completed');

        } catch (\Exception $e) {
            Log::error('SendBirthdayReminderJob failed', [
                'customer_id' => $this->customerId,
                'type' => $this->reminderType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process all customers with reminders today
     */
    private function processAllReminders(MessageTemplateService $templateService): void
    {
        $today = Carbon::today();
        
        // Find customers with birthdays today
        if ($this->reminderType === 'birthday' || $this->reminderType === 'all') {
            $birthdayCustomers = Customer::whereNotNull('birthday')
                ->whereRaw('DATE_FORMAT(birthday, "%m-%d") = ?', [$today->format('m-d')])
                ->get();

            Log::info('Found birthday customers', ['count' => $birthdayCustomers->count()]);

            foreach ($birthdayCustomers as $customer) {
                $this->sendBirthdayMessage($templateService, $customer);
            }
        }

        // Find customers with anniversaries today
        if ($this->reminderType === 'anniversary' || $this->reminderType === 'all') {
            $anniversaryCustomers = Customer::whereNotNull('anniversary')
                ->whereRaw('DATE_FORMAT(anniversary, "%m-%d") = ?', [$today->format('m-d')])
                ->get();

            Log::info('Found anniversary customers', ['count' => $anniversaryCustomers->count()]);

            foreach ($anniversaryCustomers as $customer) {
                $this->sendAnniversaryMessage($templateService, $customer);
            }
        }
    }

    /**
     * Process reminder for specific customer
     */
    private function processCustomerReminder(MessageTemplateService $templateService, Customer $customer): void
    {
        $today = Carbon::today();

        if ($this->reminderType === 'birthday' && $customer->birthday) {
            $birthday = Carbon::parse($customer->birthday);
            if ($birthday->format('m-d') === $today->format('m-d')) {
                $this->sendBirthdayMessage($templateService, $customer);
            }
        }

        if ($this->reminderType === 'anniversary' && $customer->anniversary) {
            $anniversary = Carbon::parse($customer->anniversary);
            if ($anniversary->format('m-d') === $today->format('m-d')) {
                $this->sendAnniversaryMessage($templateService, $customer);
            }
        }
    }

    /**
     * Send birthday message to customer
     */
    private function sendBirthdayMessage(MessageTemplateService $templateService, Customer $customer): void
    {
        try {
            $age = $customer->birthday ? Carbon::parse($customer->birthday)->age : null;
            
            $variables = [
                'customer_name' => $customer->name,
                'age' => $age,
                'business_name' => config('app.business_name'),
                'year' => Carbon::now()->year
            ];

            $template = $templateService->getTemplate('birthday_reminder', $customer->preferred_language);
            $message = $templateService->processTemplate($template, $variables);

            // Determine communication method
            $communicationMethod = $this->getPreferredCommunicationMethod($customer);
            
            if ($communicationMethod) {
                SendCommunicationJob::dispatch(
                    $customer->id,
                    $communicationMethod,
                    $message,
                    [
                        'type' => 'birthday_reminder',
                        'age' => $age,
                        'template_id' => $template->id ?? null
                    ]
                );

                Log::info('Birthday reminder scheduled', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'method' => $communicationMethod,
                    'age' => $age
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send birthday message', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send anniversary message to customer
     */
    private function sendAnniversaryMessage(MessageTemplateService $templateService, Customer $customer): void
    {
        try {
            $yearsMarried = $customer->anniversary 
                ? Carbon::parse($customer->anniversary)->diffInYears(Carbon::now())
                : null;
            
            $variables = [
                'customer_name' => $customer->name,
                'years_married' => $yearsMarried,
                'business_name' => config('app.business_name'),
                'year' => Carbon::now()->year
            ];

            $template = $templateService->getTemplate('anniversary_reminder', $customer->preferred_language);
            $message = $templateService->processTemplate($template, $variables);

            // Determine communication method
            $communicationMethod = $this->getPreferredCommunicationMethod($customer);
            
            if ($communicationMethod) {
                SendCommunicationJob::dispatch(
                    $customer->id,
                    $communicationMethod,
                    $message,
                    [
                        'type' => 'anniversary_reminder',
                        'years_married' => $yearsMarried,
                        'template_id' => $template->id ?? null
                    ]
                );

                Log::info('Anniversary reminder scheduled', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'method' => $communicationMethod,
                    'years_married' => $yearsMarried
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send anniversary message', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get preferred communication method for customer
     */
    private function getPreferredCommunicationMethod(Customer $customer): ?string
    {
        // Check if customer has preferred method set
        if (!empty($customer->preferred_communication_method)) {
            return $customer->preferred_communication_method;
        }

        // Fallback logic based on available contact info
        if (!empty($customer->phone)) {
            return 'whatsapp'; // Default to WhatsApp if phone available
        }

        if (!empty($customer->email)) {
            return 'email';
        }

        return null; // No communication method available
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBirthdayReminderJob failed', [
            'customer_id' => $this->customerId,
            'type' => $this->reminderType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
