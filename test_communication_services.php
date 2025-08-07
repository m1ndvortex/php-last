<?php

require_once 'vendor/autoload.php';

use App\Services\WhatsAppService;
use App\Services\SMSService;
use App\Services\NotificationService;

echo "Testing Communication Services...\n";

try {
    // Test WhatsApp Service
    echo "Testing WhatsApp Service...\n";
    $whatsappService = new WhatsAppService();
    $result = $whatsappService->sendMessage('+989123456789', 'Test message', 'en');
    echo "WhatsApp result: " . json_encode($result) . "\n";

    // Test SMS Service
    echo "Testing SMS Service...\n";
    $smsService = new SMSService();
    $result = $smsService->sendMessage('+989123456789', 'Test SMS', 'en');
    echo "SMS result: " . json_encode($result) . "\n";

    echo "All services tested successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}