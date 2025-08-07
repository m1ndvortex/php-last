<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

class WhatsAppService
{
    protected string $apiUrl;
    protected ?string $accessToken;
    protected ?string $phoneNumberId;
    protected ?string $businessAccountId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->businessAccountId = config('services.whatsapp.business_account_id');
    }

    /**
     * Send a text message via WhatsApp Business API
     *
     * @param string $to Phone number in international format (e.g., +1234567890)
     * @param string $message Message content
     * @param string $language Language code (en, fa)
     * @param array $data Additional data for tracking
     * @return array
     */
    public function sendMessage(string $to, string $message, string $language = 'en', array $data = []): array
    {
        try {
            // Clean phone number
            $to = $this->cleanPhoneNumber($to);
            
            if (!$this->isConfigured()) {
                return $this->simulateMessage($to, $message, $language, $data);
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'message_id' => $responseData['messages'][0]['id'] ?? null,
                    'language' => $language
                ]);

                return [
                    'success' => true,
                    'external_id' => $responseData['messages'][0]['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                $error = $response->json();
                Log::error('WhatsApp API error', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'WhatsApp API error',
                    'error_code' => $error['error']['code'] ?? null
                ];
            }

        } catch (Exception $e) {
            Log::error('WhatsApp service error', [
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
     * Send invoice via WhatsApp
     *
     * @param string $to
     * @param array $invoiceData
     * @param string $language
     * @return array
     */
    public function sendInvoice(string $to, array $invoiceData, string $language = 'en'): array
    {
        $message = $this->formatInvoiceMessage($invoiceData, $language);
        
        return $this->sendMessage($to, $message, $language, [
            'type' => 'invoice',
            'invoice_id' => $invoiceData['id'] ?? null,
            'invoice_number' => $invoiceData['invoice_number'] ?? null
        ]);
    }

    /**
     * Send template message (for automated messages like birthday wishes)
     *
     * @param string $to
     * @param string $templateName
     * @param array $parameters
     * @param string $language
     * @return array
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = [], string $language = 'en'): array
    {
        try {
            $to = $this->cleanPhoneNumber($to);
            
            if (!$this->isConfigured()) {
                return $this->simulateTemplate($to, $templateName, $parameters, $language);
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language
                    ]
                ]
            ];

            // Add parameters if provided
            if (!empty($parameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function ($param) {
                            return ['type' => 'text', 'text' => $param];
                        }, $parameters)
                    ]
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('WhatsApp template sent successfully', [
                    'to' => $to,
                    'template' => $templateName,
                    'message_id' => $responseData['messages'][0]['id'] ?? null
                ]);

                return [
                    'success' => true,
                    'external_id' => $responseData['messages'][0]['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                $error = $response->json();
                Log::error('WhatsApp template API error', [
                    'to' => $to,
                    'template' => $templateName,
                    'status' => $response->status(),
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'WhatsApp template API error'
                ];
            }

        } catch (Exception $e) {
            Log::error('WhatsApp template service error', [
                'to' => $to,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken
            ])->get("{$this->apiUrl}/{$messageId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get message status'
                ];
            }

        } catch (Exception $e) {
            Log::error('WhatsApp status check error', [
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
     * Format invoice message for WhatsApp
     *
     * @param array $invoiceData
     * @param string $language
     * @return string
     */
    protected function formatInvoiceMessage(array $invoiceData, string $language): string
    {
        $businessName = config('app.business_name', 'Jewelry Store');
        $invoiceNumber = $invoiceData['invoice_number'] ?? 'N/A';
        $totalAmount = $invoiceData['total_amount'] ?? 0;
        $customerName = $invoiceData['customer_name'] ?? '';

        if ($language === 'fa') {
            return "سلام {$customerName} عزیز،\n\n" .
                   "فاکتور شماره {$invoiceNumber} به مبلغ {$totalAmount} تومان آماده شده است.\n\n" .
                   "از خرید شما متشکریم.\n\n" .
                   "{$businessName}";
        } else {
            return "Dear {$customerName},\n\n" .
                   "Your invoice #{$invoiceNumber} for {$totalAmount} is ready.\n\n" .
                   "Thank you for your business!\n\n" .
                   "{$businessName}";
        }
    }

    /**
     * Check if WhatsApp is properly configured
     *
     * @return bool
     */
    protected function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->phoneNumberId);
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
        Log::info('WhatsApp message simulation', [
            'to' => $to,
            'message' => substr($message, 0, 100) . '...',
            'language' => $language,
            'data' => $data
        ]);

        // Simulate API delay
        usleep(500000); // 0.5 seconds

        // Simulate 90% success rate
        $success = rand(1, 100) <= 90;

        return [
            'success' => $success,
            'external_id' => $success ? 'sim_' . uniqid() : null,
            'error' => $success ? null : 'Simulated API failure'
        ];
    }

    /**
     * Simulate template sending for development/testing
     *
     * @param string $to
     * @param string $templateName
     * @param array $parameters
     * @param string $language
     * @return array
     */
    protected function simulateTemplate(string $to, string $templateName, array $parameters, string $language): array
    {
        Log::info('WhatsApp template simulation', [
            'to' => $to,
            'template' => $templateName,
            'parameters' => $parameters,
            'language' => $language
        ]);

        // Simulate API delay
        usleep(750000); // 0.75 seconds

        // Simulate 85% success rate for templates
        $success = rand(1, 100) <= 85;

        return [
            'success' => $success,
            'external_id' => $success ? 'sim_template_' . uniqid() : null,
            'error' => $success ? null : 'Simulated template API failure'
        ];
    }
}