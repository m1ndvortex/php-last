<?php

namespace App\Services;

use App\Models\Communication;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class CommunicationService
{
    protected LocalizationService $localizationService;
    protected WhatsAppService $whatsAppService;
    protected SMSService $smsService;

    public function __construct(
        LocalizationService $localizationService,
        WhatsAppService $whatsAppService,
        SMSService $smsService
    ) {
        $this->localizationService = $localizationService;
        $this->whatsAppService = $whatsAppService;
        $this->smsService = $smsService;
    }

    /**
     * Send communication based on type.
     *
     * @param Communication $communication
     * @return bool
     */
    public function sendCommunication(Communication $communication): bool
    {
        try {
            switch ($communication->type) {
                case 'email':
                    return $this->sendEmail($communication);
                case 'sms':
                    return $this->sendSMSCommunication($communication);
                case 'whatsapp':
                    return $this->sendWhatsApp($communication);
                case 'note':
                    return $this->saveNote($communication);
                default:
                    // For phone, meeting types, just mark as sent
                    $communication->markAsSent();
                    return true;
            }
        } catch (Exception $e) {
            Log::error('Communication sending failed', [
                'communication_id' => $communication->id,
                'type' => $communication->type,
                'error' => $e->getMessage()
            ]);

            $communication->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Send email communication.
     *
     * @param Communication $communication
     * @return bool
     */
    protected function sendEmail(Communication $communication): bool
    {
        $customer = $communication->customer;
        
        if (!$customer->email) {
            throw new Exception('Customer email address is required');
        }

        $result = $this->sendEmailDirect(
            $customer->email,
            $communication->subject ?: 'Message from ' . config('app.business_name'),
            $communication->message,
            $customer->preferred_language,
            [
                'communication_id' => $communication->id,
                'customer_id' => $customer->id
            ]
        );

        if ($result['success']) {
            $communication->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge($communication->metadata ?? [], [
                    'external_id' => $result['external_id'] ?? null,
                    'provider_response' => $result['response'] ?? null
                ])
            ]);
            return true;
        } else {
            throw new Exception($result['error'] ?? 'Email sending failed');
        }
    }

    /**
     * Send SMS directly to a phone number
     *
     * @param string $phone
     * @param string $message
     * @param string $language
     * @return array
     */
    public function sendSMS(string $phone, string $message, string $language = 'en'): array
    {
        return $this->smsService->sendMessage($phone, $message, $language);
    }

    /**
     * Send WhatsApp message directly to a phone number
     *
     * @param string $phone
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    public function sendWhatsAppMessage(string $phone, string $message, string $language = 'en', array $data = []): array
    {
        return $this->whatsAppService->sendMessage($phone, $message, $language, $data);
    }

    /**
     * Send email directly to an email address
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    public function sendEmailDirect(string $email, string $subject, string $message, string $language = 'en', array $data = []): array
    {
        try {
            // TODO: Implement actual email sending logic
            Log::info('Email would be sent', [
                'to' => $email,
                'subject' => $subject,
                'message' => substr($message, 0, 100) . '...',
                'language' => $language,
                'data' => $data
            ]);

            // Simulate email sending
            usleep(200000); // 0.2 seconds
            $success = rand(1, 100) <= 98; // 98% success rate

            return [
                'success' => $success,
                'external_id' => $success ? 'email_sim_' . uniqid() : null,
                'error' => $success ? null : 'Simulated email failure'
            ];

        } catch (Exception $e) {
            Log::error('Email sending failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS communication.
     *
     * @param Communication $communication
     * @return bool
     */
    protected function sendSMSCommunication(Communication $communication): bool
    {
        $customer = $communication->customer;
        
        if (!$customer->phone) {
            throw new Exception('Customer phone number is required');
        }

        // Localize message if needed
        $message = $this->localizeMessage($communication->message, $customer->preferred_language);

        $result = $this->smsService->sendMessage(
            $customer->phone,
            $message,
            $customer->preferred_language,
            [
                'communication_id' => $communication->id,
                'customer_id' => $customer->id
            ]
        );

        if ($result['success']) {
            $communication->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge($communication->metadata ?? [], [
                    'external_id' => $result['external_id'] ?? null,
                    'provider_response' => $result['response'] ?? null
                ])
            ]);
            return true;
        } else {
            throw new Exception($result['error'] ?? 'SMS API call failed');
        }
    }

    /**
     * Send WhatsApp communication.
     *
     * @param Communication $communication
     * @return bool
     */
    protected function sendWhatsApp(Communication $communication): bool
    {
        $customer = $communication->customer;
        
        if (!$customer->phone) {
            throw new Exception('Customer phone number is required');
        }

        // Localize message if needed
        $message = $this->localizeMessage($communication->message, $customer->preferred_language);

        $result = $this->whatsAppService->sendMessage(
            $customer->phone,
            $message,
            $customer->preferred_language,
            [
                'communication_id' => $communication->id,
                'customer_id' => $customer->id
            ]
        );

        if ($result['success']) {
            $communication->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge($communication->metadata ?? [], [
                    'external_id' => $result['external_id'] ?? null,
                    'provider_response' => $result['response'] ?? null
                ])
            ]);
            return true;
        } else {
            throw new Exception($result['error'] ?? 'WhatsApp API call failed');
        }
    }

    /**
     * Save note communication.
     *
     * @param Communication $communication
     * @return bool
     */
    protected function saveNote(Communication $communication): bool
    {
        $communication->markAsSent();
        return true;
    }

    /**
     * Send birthday reminder to customer.
     *
     * @param Customer $customer
     * @param string $type
     * @return Communication|null
     */
    public function sendBirthdayReminder(Customer $customer, string $type = 'whatsapp'): ?Communication
    {
        if (!$customer->birthday || !$customer->hasUpcomingBirthday()) {
            return null;
        }

        $message = $this->getBirthdayMessage($customer);
        
        $communication = Communication::create([
            'customer_id' => $customer->id,
            'user_id' => 1, // System user
            'type' => $type,
            'subject' => 'Birthday Wishes',
            'message' => $message,
            'status' => 'draft',
            'metadata' => [
                'automated' => true,
                'reminder_type' => 'birthday'
            ]
        ]);

        $this->sendCommunication($communication);
        
        return $communication;
    }

    /**
     * Send anniversary reminder to customer.
     *
     * @param Customer $customer
     * @param string $type
     * @return Communication|null
     */
    public function sendAnniversaryReminder(Customer $customer, string $type = 'whatsapp'): ?Communication
    {
        if (!$customer->anniversary || !$customer->hasUpcomingAnniversary()) {
            return null;
        }

        $message = $this->getAnniversaryMessage($customer);
        
        $communication = Communication::create([
            'customer_id' => $customer->id,
            'user_id' => 1, // System user
            'type' => $type,
            'subject' => 'Anniversary Wishes',
            'message' => $message,
            'status' => 'draft',
            'metadata' => [
                'automated' => true,
                'reminder_type' => 'anniversary'
            ]
        ]);

        $this->sendCommunication($communication);
        
        return $communication;
    }

    /**
     * Send invoice via communication channel.
     *
     * @param Customer $customer
     * @param array $invoiceData
     * @param string $type
     * @return Communication
     */
    public function sendInvoice(Customer $customer, array $invoiceData, string $type = 'whatsapp'): Communication
    {
        $message = $this->getInvoiceMessage($customer, $invoiceData);
        
        $communication = Communication::create([
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'subject' => 'Invoice #' . $invoiceData['invoice_number'],
            'message' => $message,
            'status' => 'draft',
            'metadata' => [
                'invoice_id' => $invoiceData['id'] ?? null,
                'invoice_number' => $invoiceData['invoice_number'],
                'amount' => $invoiceData['total_amount']
            ]
        ]);

        $this->sendCommunication($communication);
        
        return $communication;
    }

    /**
     * Get birthday message in customer's preferred language.
     *
     * @param Customer $customer
     * @return string
     */
    protected function getBirthdayMessage(Customer $customer): string
    {
        $templates = [
            'en' => "Happy Birthday, {$customer->name}! 🎉 Wishing you a wonderful day filled with joy and happiness. Thank you for being our valued customer!",
            'fa' => "تولدت مبارک {$customer->name}! 🎉 برایت روزی پر از شادی و خوشحالی آرزو می‌کنیم. از اینکه مشتری عزیز ما هستی متشکریم!"
        ];

        return $templates[$customer->preferred_language] ?? $templates['en'];
    }

    /**
     * Get anniversary message in customer's preferred language.
     *
     * @param Customer $customer
     * @return string
     */
    protected function getAnniversaryMessage(Customer $customer): string
    {
        $templates = [
            'en' => "Happy Anniversary, {$customer->name}! 💍 Wishing you many more years of love and happiness together. Thank you for choosing us for your special moments!",
            'fa' => "سالگرد ازدواجتان مبارک {$customer->name}! 💍 برایتان سال‌های بیشتری پر از عشق و خوشبختی آرزو می‌کنیم. از اینکه ما را برای لحظات خاصتان انتخاب کردید متشکریم!"
        ];

        return $templates[$customer->preferred_language] ?? $templates['en'];
    }

    /**
     * Get invoice message in customer's preferred language.
     *
     * @param Customer $customer
     * @param array $invoiceData
     * @return string
     */
    protected function getInvoiceMessage(Customer $customer, array $invoiceData): string
    {
        $amount = $invoiceData['total_amount'];
        $invoiceNumber = $invoiceData['invoice_number'];

        $templates = [
            'en' => "Dear {$customer->name}, your invoice #{$invoiceNumber} for {$amount} is ready. Thank you for your business!",
            'fa' => "عزیز {$customer->name}، فاکتور شماره {$invoiceNumber} به مبلغ {$amount} آماده است. از خرید شما متشکریم!"
        ];

        return $templates[$customer->preferred_language] ?? $templates['en'];
    }

    /**
     * Localize message based on customer's preferred language.
     *
     * @param string $message
     * @param string $language
     * @return string
     */
    protected function localizeMessage(string $message, string $language): string
    {
        // TODO: Implement message localization logic
        // This could use translation services or predefined templates
        return $message;
    }

    /**
     * Simulate SMS API call (for development/testing).
     *
     * @param array $data
     * @return bool
     */
    protected function simulateSMSAPI(array $data): bool
    {
        // Simulate API delay
        usleep(500000); // 0.5 seconds
        
        // Simulate 95% success rate
        return rand(1, 100) <= 95;
    }

    /**
     * Simulate WhatsApp API call (for development/testing).
     *
     * @param array $data
     * @return bool
     */
    protected function simulateWhatsAppAPI(array $data): bool
    {
        // Simulate API delay
        usleep(1000000); // 1 second
        
        // Simulate 90% success rate
        return rand(1, 100) <= 90;
    }

    /**
     * Get communication statistics.
     *
     * @param array $filters
     * @return array
     */
    public function getCommunicationStats(array $filters = []): array
    {
        $query = Communication::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $baseQuery = clone $query;
        
        $stats = [
            'total' => $baseQuery->count(),
            'by_type' => (clone $baseQuery)->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_status' => (clone $baseQuery)->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'success_rate' => 0
        ];

        $totalSent = (clone $baseQuery)->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $stats['success_rate'] = $stats['total'] > 0 ? round(($totalSent / $stats['total']) * 100, 2) : 0;

        return $stats;
    }
}