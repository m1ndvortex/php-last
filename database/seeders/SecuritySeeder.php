<?php

namespace Database\Seeders;

use App\Services\BusinessConfigurationService;
use App\Services\MessageTemplateService;
use App\Services\RolePermissionService;
use Illuminate\Database\Seeder;

class SecuritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolePermissionService = new RolePermissionService();
        $messageTemplateService = new MessageTemplateService();
        $configService = new BusinessConfigurationService();

        // Seed permissions and roles
        $this->command->info('Seeding permissions...');
        $rolePermissionService->seedDefaultPermissions();

        $this->command->info('Seeding roles...');
        $rolePermissionService->seedDefaultRoles();

        // Seed message templates
        $this->command->info('Seeding message templates...');
        $messageTemplateService->seedDefaultTemplates();

        // Seed default business configurations
        $this->command->info('Seeding business configurations...');
        $this->seedBusinessConfigurations($configService);

        $this->command->info('Security seeding completed!');
    }

    /**
     * Seed default business configurations
     */
    private function seedBusinessConfigurations(BusinessConfigurationService $configService): void
    {
        // Business information
        $configService->set('business_name', 'Jewelry Store', 'string', 'business');
        $configService->set('business_address', '', 'string', 'business');
        $configService->set('business_phone', '', 'string', 'business');
        $configService->set('business_email', '', 'string', 'business');
        $configService->set('business_tax_number', '', 'string', 'business');
        $configService->set('business_logo', '', 'string', 'business');
        $configService->set('business_currency', 'USD', 'string', 'business');
        $configService->set('business_timezone', 'UTC', 'string', 'business');

        // Tax configuration
        $configService->set('default_tax_rate', 0, 'number', 'tax');
        $configService->set('tax_inclusive', false, 'boolean', 'tax');
        $configService->set('tax_number_required', false, 'boolean', 'tax');

        // Profit configuration
        $configService->set('default_profit_margin', 20, 'number', 'profit');
        $configService->set('gold_profit_margin', 15, 'number', 'profit');
        $configService->set('jewelry_profit_margin', 25, 'number', 'profit');

        // Security configuration
        $configService->set('session_timeout', 120, 'number', 'security'); // minutes
        $configService->set('max_login_attempts', 5, 'number', 'security');
        $configService->set('lockout_duration', 15, 'number', 'security'); // minutes
        $configService->set('require_2fa', false, 'boolean', 'security');
        $configService->set('audit_log_retention', 365, 'number', 'security'); // days

        // Communication configuration
        $configService->set('sms_provider', '', 'string', 'communication');
        $configService->set('sms_api_key', '', 'string', 'communication', true); // encrypted
        $configService->set('whatsapp_api_key', '', 'string', 'communication', true); // encrypted
        $configService->set('email_from_address', '', 'string', 'communication');
        $configService->set('email_from_name', '', 'string', 'communication');

        // Theme configuration
        $configService->set('default_language', 'en', 'string', 'theme');
        $configService->set('rtl_enabled', true, 'boolean', 'theme');
        $configService->set('theme_color', 'blue', 'string', 'theme');
        $configService->set('dark_mode_enabled', false, 'boolean', 'theme');
    }
}