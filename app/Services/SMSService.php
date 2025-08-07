<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

class SMSService
{
    protected ?string $apiUrl;
    protected ?string $apiKey;
    protected ?string $senderId;
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'kavenegar'); // kavenegar, twilio, aws_sns
        $this->apiUrl = config('services.sms.api_url');
        $this->apiKey = config('services.sms.api_key');
        $this->senderId = config('services.sms.sender_id');
    }

    /**
     * Send SMS message
     *
     * @param string $to Phone number
     * @param string $message Message content
     * @param string $language Language code
     * @param array $data Additional data
     * @return array
     */
    public function sendMessage(string $to, string $message, string $language = 'en', array $data = []): array
    {
        try {
            $to = $this->cleanPhoneNumber($to);
            
            if (!$this->isConfigured()) {
                return $this->simulateMessage($to, $message, $language, $data);
            }

            return match ($this->provider) {
                'kavenegar' => $this->sendViaKavenegar($to, $message, $language, $data),
                'twilio' => $this->sendViaTwilio($to, $message, $language, $data),
                'aws_sns' => $this->sendViaAWSSNS($to, $message, $language, $data),
                default => $this->simulateMessage($to, $message, $language, $data)
            };

        } catch (Exception $e) {
            Log::error('SMS service error', [
                'to' => $to,
                'provider' => $this->provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Kavenegar (Iranian SMS provider)
     *
     * @param string $to
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    protected function sendViaKavenegar(string $to, string $message, string $language, array $data): array
    {
        try {
            $response = Http::get($this->apiUrl . '/sms/send.json', [
                'apikey' => $this->apiKey,
                'receptor' => $to,
                'sender' => $this->senderId,
                'message' => $message
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['return']['status'] == 200) {
                    Log::info('Kavenegar SMS sent successfully', [
                        'to' => $to,
                        'message_id' => $responseData['entries'][0]['messageid'] ?? null
                    ]);

                    return [
                        'success' => true,
                        'external_id' => $responseData['entries'][0]['messageid'] ?? null,
                        'response' => $responseData
                    ];
                } else {
                    Log::error('Kavenegar SMS API error', [
                        'to' => $to,
                        'status' => $responseData['return']['status'],
                        'message' => $responseData['return']['message']
                    ]);

                    return [
                        'success' => false,
                        'error' => $responseData['return']['message'] ?? 'Kavenegar API error'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP request failed'
                ];
            }

        } catch (Exception $e) {
            Log::error('Kavenegar SMS error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $to
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    protected function sendViaTwilio(string $to, string $message, string $language, array $data): array
    {
        try {
            $accountSid = config('services.sms.twilio.account_sid');
            $authToken = config('services.sms.twilio.auth_token');
            
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $this->senderId,
                    'To' => '+' . $to,
                    'Body' => $message
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Twilio SMS sent successfully', [
                    'to' => $to,
                    'message_sid' => $responseData['sid'] ?? null
                ]);

                return [
                    'success' => true,
                    'external_id' => $responseData['sid'] ?? null,
                    'response' => $responseData
                ];
            } else {
                $error = $response->json();
                Log::error('Twilio SMS API error', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'Twilio API error'
                ];
            }

        } catch (Exception $e) {
            Log::error('Twilio SMS error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via AWS SNS
     *
     * @param string $to
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    protected function sendViaAWSSNS(string $to, string $message, string $language, array $data): array
    {
        try {
            // This would require AWS SDK integration
            // For now, we'll simulate it
            Log::info('AWS SNS SMS would be sent', [
                'to' => $to,
                'message' => substr($message, 0, 100) . '...'
            ]);

            return $this->simulateMessage($to, $message, $language, $data);

        } catch (Exception $e) {
            Log::error('AWS SNS SMS error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send birthday reminder SMS
     *
     * @param string $to
     * @param string $customerName
     * @param string $language
     * @return array
     */
    public function sendBirthdayReminder(string $to, string $customerName, string $language = 'en'): array
    {
        $message = $this->getBirthdayMessage($customerName, $language);
        
        return $this->sendMessage($to, $message, $language, [
            'type' => 'birthday_reminder',
            'customer_name' => $customerName
        ]);
    }

    /**
     * Send anniversary reminder SMS
     *
     * @param string $to
     * @param string $customerName
     * @param string $language
     * @return array
     */
    public function sendAnniversaryReminder(string $to, string $customerName, string $language = 'en'): array
    {
        $message = $this->getAnniversaryMessage($customerName, $language);
        
        return $this->sendMessage($to, $message, $language, [
            'type' => 'anniversary_reminder',
            'customer_name' => $customerName
        ]);
    }

    /**
     * Send stock alert SMS
     *
     * @param string $to
     * @param array $alertData
     * @param string $language
     * @return array
     */
    public function sendStockAlert(string $to, array $alertData, string $language = 'en'): array
    {
        $message = $this->getStockAlertMessage($alertData, $language);
        
        return $this->sendMessage($to, $message, $language, [
            'type' => 'stock_alert',
            'alert_data' => $alertData
        ]);
    }

    /**
     * Send invoice notification SMS
     *
     * @param string $to
     * @param array $invoiceData
     * @param string $language
     * @return array
     */
    public function sendInvoiceNotification(string $to, array $invoiceData, string $language = 'en'): array
    {
        $message = $this->getInvoiceMessage($invoiceData, $language);
        
        return $this->sendMessage($to, $message, $language, [
            'type' => 'invoice_notification',
            'invoice_id' => $invoiceData['id'] ?? null
        ]);
    }

    /**
     * Get birthday message template
     *
     * @param string $customerName
     * @param string $language
     * @return string
     */
    protected function getBirthdayMessage(string $customerName, string $language): string
    {
        $businessName = config('app.business_name', 'Jewelry Store');

        if ($language === 'fa') {
            return "تولدت مبارک {$customerName} عزیز! 🎉\n" .
                   "برایت روزی پر از شادی آرزو می‌کنیم.\n" .
                   "{$businessName}";
        } else {
            return "Happy Birthday {$customerName}! 🎉\n" .
                   "Wishing you a wonderful day!\n" .
                   "{$businessName}";
        }
    }

    /**
     * Get anniversary message template
     *
     * @param string $customerName
     * @param string $language
     * @return string
     */
    protected function getAnniversaryMessage(string $customerName, string $language): string
    {
        $businessName = config('app.business_name', 'Jewelry Store');

        if ($language === 'fa') {
            return "سالگرد ازدواجتان مبارک {$customerName}! 💍\n" .
                   "سال‌های پر از عشق و خوشبختی برایتان آرزو می‌کنیم.\n" .
                   "{$businessName}";
        } else {
            return "Happy Anniversary {$customerName}! 💍\n" .
                   "Wishing you many more years of love and happiness!\n" .
                   "{$businessName}";
        }
    }

    /**
     * Get stock alert message template
     *
     * @param array $alertData
     * @param string $language
     * @return string
     */
    protected function getStockAlertMessage(array $alertData, string $language): string
    {
        $itemName = $alertData['item_name'] ?? 'Unknown Item';
        $quantity = $alertData['quantity'] ?? 0;
        $alertType = $alertData['type'] ?? 'stock_alert';

        if ($language === 'fa') {
            return match ($alertType) {
                'low_stock' => "هشدار موجودی کم: {$itemName}\nموجودی فعلی: {$quantity}",
                'out_of_stock' => "هشدار اتمام موجودی: {$itemName}\nموجودی: {$quantity}",
                'expiring' => "هشدار انقضا: {$itemName}\nتاریخ انقضا نزدیک است",
                default => "هشدار موجودی: {$itemName}"
            };
        } else {
            return match ($alertType) {
                'low_stock' => "Low Stock Alert: {$itemName}\nCurrent Stock: {$quantity}",
                'out_of_stock' => "Out of Stock Alert: {$itemName}\nStock: {$quantity}",
                'expiring' => "Expiring Item Alert: {$itemName}\nExpiry date approaching",
                default => "Stock Alert: {$itemName}"
            };
        }
    }

    /**
     * Get invoice message template
     *
     * @param array $invoiceData
     * @param string $language
     * @return string
     */
    protected function getInvoiceMessage(array $invoiceData, string $language): string
    {
        $invoiceNumber = $invoiceData['invoice_number'] ?? 'N/A';
        $totalAmount = $invoiceData['total_amount'] ?? 0;
        $customerName = $invoiceData['customer_name'] ?? '';

        if ($language === 'fa') {
            return "سلام {$customerName} عزیز\n" .
                   "فاکتور #{$invoiceNumber} به مبلغ {$totalAmount} تومان آماده است.\n" .
                   "از خرید شما متشکریم.";
        } else {
            return "Dear {$customerName}\n" .
                   "Invoice #{$invoiceNumber} for {$totalAmount} is ready.\n" .
                   "Thank you for your business!";
        }
    }

    /**
     * Clean and format phone number
     *
     * @param string $phone
     * @return string
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming Iran +98 as default)
        if (!str_starts_with($phone, '98') && !str_starts_with($phone, '+98')) {
            if (str_starts_with($phone, '0')) {
                $phone = '98' . substr($phone, 1);
            } else {
                $phone = '98' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Check if SMS is properly configured
     *
     * @return bool
     */
    protected function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiUrl);
    }

    /**
     * Simulate message sending for development/testing
     *
     * @param string $to
     * @param string $message
     * @param string $language
     * @param array $data
     * @return array
     */
    protected function simulateMessage(string $to, string $message, string $language, array $data): array
    {
        Log::info('SMS message simulation', [
            'to' => $to,
            'message' => substr($message, 0, 100) . '...',
            'language' => $language,
            'provider' => $this->provider,
            'data' => $data
        ]);

        // Simulate API delay
        usleep(300000); // 0.3 seconds

        // Simulate 95% success rate
        $success = rand(1, 100) <= 95;

        return [
            'success' => $success,
            'external_id' => $success ? 'sms_sim_' . uniqid() : null,
            'error' => $success ? null : 'Simulated SMS API failure'
        ];
    }

    /**
     * Get message delivery status
     *
     * @param string $messageId
     * @return array
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => true,
                    'status' => 'delivered',
                    'timestamp' => now()->toISOString()
                ];
            }

            return match ($this->provider) {
                'kavenegar' => $this->getKavenegarStatus($messageId),
                'twilio' => $this->getTwilioStatus($messageId),
                default => [
                    'success' => true,
                    'status' => 'delivered',
                    'timestamp' => now()->toISOString()
                ]
            };

        } catch (Exception $e) {
            Log::error('SMS status check error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Kavenegar message status
     *
     * @param string $messageId
     * @return array
     */
    protected function getKavenegarStatus(string $messageId): array
    {
        try {
            $response = Http::get($this->apiUrl . '/sms/status.json', [
                'apikey' => $this->apiKey,
                'messageid' => $messageId
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'status' => $responseData['entries'][0]['status'] ?? 'unknown',
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get message status'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Twilio message status
     *
     * @param string $messageId
     * @return array
     */
    protected function getTwilioStatus(string $messageId): array
    {
        try {
            $accountSid = config('services.sms.twilio.account_sid');
            $authToken = config('services.sms.twilio.auth_token');
            
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages/{$messageId}.json");

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'status' => $responseData['status'] ?? 'unknown',
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get message status'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}