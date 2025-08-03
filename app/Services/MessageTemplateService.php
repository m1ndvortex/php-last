<?php

namespace App\Services;

use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Cache;

class MessageTemplateService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get template by name and language
     */
    public function getTemplate(string $name, string $language = 'en'): ?MessageTemplate
    {
        $cacheKey = "template_{$name}_{$language}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $language) {
            return MessageTemplate::where('name', $name)
                ->where('language', $language)
                ->active()
                ->first();
        });
    }

    /**
     * Get templates by type and category
     */
    public function getTemplatesByTypeAndCategory(string $type, string $category, string $language = 'en'): array
    {
        $cacheKey = "templates_{$type}_{$category}_{$language}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $category, $language) {
            return MessageTemplate::type($type)
                ->category($category)
                ->language($language)
                ->active()
                ->get()
                ->toArray();
        });
    }

    /**
     * Create or update template
     */
    public function saveTemplate(array $data): MessageTemplate
    {
        // Extract variables from content
        $variables = MessageTemplate::extractVariables(
            $data['content'],
            $data['subject'] ?? null
        );

        $template = MessageTemplate::updateOrCreate(
            [
                'name' => $data['name'],
                'language' => $data['language'] ?? 'en'
            ],
            [
                'type' => $data['type'],
                'category' => $data['category'],
                'subject' => $data['subject'] ?? null,
                'content' => $data['content'],
                'variables' => $variables,
                'is_active' => $data['is_active'] ?? true
            ]
        );

        $this->clearTemplateCache($template->name, $template->language);

        return $template;
    }

    /**
     * Render template with variables
     */
    public function renderTemplate(string $templateName, array $variables, string $language = 'en'): ?array
    {
        $template = $this->getTemplate($templateName, $language);

        if (!$template) {
            return null;
        }

        return $template->render($variables);
    }

    /**
     * Get all templates grouped by category
     */
    public function getAllTemplatesGrouped(): array
    {
        return Cache::remember('all_templates_grouped', self::CACHE_TTL, function () {
            return MessageTemplate::active()
                ->get()
                ->groupBy(['category', 'type', 'language'])
                ->toArray();
        });
    }

    /**
     * Seed default templates
     */
    public function seedDefaultTemplates(): void
    {
        $templates = [
            // Invoice templates
            [
                'name' => 'invoice_email_en',
                'type' => 'email',
                'category' => 'invoice',
                'language' => 'en',
                'subject' => 'Invoice #{{invoice_number}} from {{business_name}}',
                'content' => 'Dear {{customer_name}},

Please find attached your invoice #{{invoice_number}} dated {{invoice_date}}.

Invoice Details:
- Amount: {{total_amount}}
- Due Date: {{due_date}}

Thank you for your business!

Best regards,
{{business_name}}
{{business_phone}}',
            ],
            [
                'name' => 'invoice_email_fa',
                'type' => 'email',
                'category' => 'invoice',
                'language' => 'fa',
                'subject' => 'فاکتور شماره {{invoice_number}} از {{business_name}}',
                'content' => '{{customer_name}} عزیز،

لطفاً فاکتور شماره {{invoice_number}} مورخ {{invoice_date}} را در پیوست مشاهده فرمایید.

جزئیات فاکتور:
- مبلغ: {{total_amount}}
- تاریخ سررسید: {{due_date}}

از همکاری شما متشکریم!

با احترام،
{{business_name}}
{{business_phone}}',
            ],
            [
                'name' => 'invoice_sms_en',
                'type' => 'sms',
                'category' => 'invoice',
                'language' => 'en',
                'subject' => null,
                'content' => 'Invoice #{{invoice_number}} for {{total_amount}} has been sent. Due: {{due_date}}. {{business_name}}',
            ],
            [
                'name' => 'invoice_sms_fa',
                'type' => 'sms',
                'category' => 'invoice',
                'language' => 'fa',
                'subject' => null,
                'content' => 'فاکتور {{invoice_number}} به مبلغ {{total_amount}} ارسال شد. سررسید: {{due_date}}. {{business_name}}',
            ],

            // Birthday reminder templates
            [
                'name' => 'birthday_sms_en',
                'type' => 'sms',
                'category' => 'birthday',
                'language' => 'en',
                'subject' => null,
                'content' => 'Happy Birthday {{customer_name}}! 🎉 Wishing you a wonderful year ahead. {{business_name}}',
            ],
            [
                'name' => 'birthday_sms_fa',
                'type' => 'sms',
                'category' => 'birthday',
                'language' => 'fa',
                'subject' => null,
                'content' => '{{customer_name}} عزیز، تولدتان مبارک! 🎉 سالی پر از شادی برایتان آرزومندیم. {{business_name}}',
            ],

            // Payment reminder templates
            [
                'name' => 'payment_reminder_sms_en',
                'type' => 'sms',
                'category' => 'reminder',
                'language' => 'en',
                'subject' => null,
                'content' => 'Reminder: Invoice #{{invoice_number}} ({{total_amount}}) is due on {{due_date}}. {{business_name}}',
            ],
            [
                'name' => 'payment_reminder_sms_fa',
                'type' => 'sms',
                'category' => 'reminder',
                'language' => 'fa',
                'subject' => null,
                'content' => 'یادآوری: فاکتور {{invoice_number}} ({{total_amount}}) در تاریخ {{due_date}} سررسید می‌شود. {{business_name}}',
            ],

            // WhatsApp templates
            [
                'name' => 'invoice_whatsapp_en',
                'type' => 'whatsapp',
                'category' => 'invoice',
                'language' => 'en',
                'subject' => null,
                'content' => 'Hello {{customer_name}},

Your invoice #{{invoice_number}} is ready!
Amount: {{total_amount}}
Due Date: {{due_date}}

Thank you for choosing {{business_name}}! 💎',
            ],
            [
                'name' => 'invoice_whatsapp_fa',
                'type' => 'whatsapp',
                'category' => 'invoice',
                'language' => 'fa',
                'subject' => null,
                'content' => 'سلام {{customer_name}} عزیز،

فاکتور شماره {{invoice_number}} شما آماده است!
مبلغ: {{total_amount}}
تاریخ سررسید: {{due_date}}

از انتخاب {{business_name}} متشکریم! 💎',
            ],
        ];

        foreach ($templates as $templateData) {
            $this->saveTemplate($templateData);
        }
    }

    /**
     * Get default variables for different categories
     */
    public function getDefaultVariables(): array
    {
        return [
            'invoice' => [
                'customer_name', 'invoice_number', 'invoice_date', 'due_date',
                'total_amount', 'subtotal', 'tax_amount', 'business_name',
                'business_phone', 'business_email', 'business_address'
            ],
            'birthday' => [
                'customer_name', 'business_name', 'business_phone'
            ],
            'reminder' => [
                'customer_name', 'invoice_number', 'total_amount', 'due_date',
                'days_overdue', 'business_name', 'business_phone'
            ],
            'notification' => [
                'customer_name', 'message', 'business_name', 'date', 'time'
            ]
        ];
    }

    /**
     * Clear template cache
     */
    private function clearTemplateCache(string $name, string $language): void
    {
        Cache::forget("template_{$name}_{$language}");
        Cache::forget('all_templates_grouped');
    }

    /**
     * Clear all template caches
     */
    public function clearAllCache(): void
    {
        $templates = MessageTemplate::select('name', 'language')->get();
        
        foreach ($templates as $template) {
            Cache::forget("template_{$template->name}_{$template->language}");
        }
        
        Cache::forget('all_templates_grouped');
    }
}