<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use App\Services\SMSService;
use App\Services\NotificationService;
use App\Services\CommunicationService;
use Illuminate\Console\Command;

class TestCommunicationServices extends Command
{
    protected $signature = 'test:communication-services';
    protected $description = 'Test communication services functionality';

    public function handle()
    {
        $this->info('Testing Communication Services...');

        try {
            // Test WhatsApp Service
            $this->info('Testing WhatsApp Service...');
            $whatsappService = app(WhatsAppService::class);
            $result = $whatsappService->sendMessage('+989123456789', 'Test WhatsApp message', 'en');
            $this->line('WhatsApp result: ' . json_encode($result, JSON_PRETTY_PRINT));

            // Test SMS Service
            $this->info('Testing SMS Service...');
            $smsService = app(SMSService::class);
            $result = $smsService->sendMessage('+989123456789', 'Test SMS message', 'en');
            $this->line('SMS result: ' . json_encode($result, JSON_PRETTY_PRINT));

            // Test Notification Service
            $this->info('Testing Notification Service...');
            $notificationService = app(NotificationService::class);
            $notifications = $notificationService->getNotifications(null, ['limit' => 5]);
            $this->line('Notifications count: ' . count($notifications['notifications']));

            // Test Communication Service
            $this->info('Testing Communication Service...');
            $communicationService = app(CommunicationService::class);
            $stats = $communicationService->getCommunicationStats();
            $this->line('Communication stats: ' . json_encode($stats, JSON_PRETTY_PRINT));

            $this->info('All services tested successfully!');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}