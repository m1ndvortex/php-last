<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\LocalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CommunicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CommunicationService $communicationService;
    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'preferred_language' => 'en',
        ]);

        $localizationService = Mockery::mock(LocalizationService::class);
        $this->communicationService = new CommunicationService($localizationService);
    }

    public function test_send_communication_handles_email_type()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'subject' => 'Test Email',
            'message' => 'Test email message',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        $this->assertTrue($result);
        $this->assertEquals('sent', $communication->fresh()->status);
        $this->assertNotNull($communication->fresh()->sent_at);
    }

    public function test_send_communication_handles_sms_type()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'Test SMS message',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        // Result depends on simulated API success rate (95%)
        // We'll check that the communication status was updated
        $freshCommunication = $communication->fresh();
        $this->assertContains($freshCommunication->status, ['sent', 'failed']);
    }

    public function test_send_communication_handles_whatsapp_type()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'whatsapp',
            'message' => 'Test WhatsApp message',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        // Result depends on simulated API success rate (90%)
        $freshCommunication = $communication->fresh();
        $this->assertContains($freshCommunication->status, ['sent', 'failed']);
    }

    public function test_send_communication_handles_note_type()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test note',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        $this->assertTrue($result);
        $this->assertEquals('sent', $communication->fresh()->status);
    }

    public function test_send_communication_handles_phone_and_meeting_types()
    {
        $phoneCommunication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'phone',
            'message' => 'Phone call notes',
        ]);

        $meetingCommunication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'meeting',
            'message' => 'Meeting notes',
        ]);

        $phoneResult = $this->communicationService->sendCommunication($phoneCommunication);
        $meetingResult = $this->communicationService->sendCommunication($meetingCommunication);

        $this->assertTrue($phoneResult);
        $this->assertTrue($meetingResult);
        $this->assertEquals('sent', $phoneCommunication->fresh()->status);
        $this->assertEquals('sent', $meetingCommunication->fresh()->status);
    }

    public function test_send_email_fails_without_customer_email()
    {
        $customerWithoutEmail = Customer::create([
            'name' => 'Customer Without Email',
            'phone' => '+1234567890',
        ]);

        $communication = Communication::create([
            'customer_id' => $customerWithoutEmail->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Test email',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        $this->assertFalse($result);
        $this->assertEquals('failed', $communication->fresh()->status);
        $this->assertArrayHasKey('failure_reason', $communication->fresh()->metadata);
    }

    public function test_send_sms_fails_without_customer_phone()
    {
        $customerWithoutPhone = Customer::create([
            'name' => 'Customer Without Phone',
            'email' => 'test@example.com',
        ]);

        $communication = Communication::create([
            'customer_id' => $customerWithoutPhone->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'Test SMS',
        ]);

        $result = $this->communicationService->sendCommunication($communication);

        $this->assertFalse($result);
        $this->assertEquals('failed', $communication->fresh()->status);
    }

    public function test_send_birthday_reminder_creates_communication()
    {
        $customer = Customer::create([
            'name' => 'Birthday Customer',
            'phone' => '+1234567890',
            'birthday' => now()->addDays(15)->subYears(25),
            'preferred_language' => 'en',
        ]);

        $communication = $this->communicationService->sendBirthdayReminder($customer, 'whatsapp');

        $this->assertInstanceOf(Communication::class, $communication);
        $this->assertEquals('whatsapp', $communication->type);
        $this->assertEquals('Birthday Wishes', $communication->subject);
        $this->assertStringContainsString('Happy Birthday', $communication->message);
        $this->assertStringContainsString($customer->name, $communication->message);
        $this->assertTrue($communication->metadata['automated']);
        $this->assertEquals('birthday', $communication->metadata['reminder_type']);
    }

    public function test_send_birthday_reminder_returns_null_for_no_upcoming_birthday()
    {
        $customer = Customer::create([
            'name' => 'No Birthday Customer',
            'phone' => '+1234567890',
        ]);

        $communication = $this->communicationService->sendBirthdayReminder($customer);

        $this->assertNull($communication);
    }

    public function test_send_anniversary_reminder_creates_communication()
    {
        $customer = Customer::create([
            'name' => 'Anniversary Customer',
            'phone' => '+1234567890',
            'anniversary' => now()->addDays(20)->subYears(5),
            'preferred_language' => 'en',
        ]);

        $communication = $this->communicationService->sendAnniversaryReminder($customer, 'whatsapp');

        $this->assertInstanceOf(Communication::class, $communication);
        $this->assertEquals('whatsapp', $communication->type);
        $this->assertEquals('Anniversary Wishes', $communication->subject);
        $this->assertStringContainsString('Happy Anniversary', $communication->message);
        $this->assertStringContainsString($customer->name, $communication->message);
        $this->assertTrue($communication->metadata['automated']);
        $this->assertEquals('anniversary', $communication->metadata['reminder_type']);
    }

    public function test_send_invoice_creates_communication_with_invoice_data()
    {
        $invoiceData = [
            'id' => 123,
            'invoice_number' => 'INV-001',
            'total_amount' => 1500.00,
        ];

        $communication = $this->communicationService->sendInvoice($this->customer, $invoiceData, 'whatsapp');

        $this->assertInstanceOf(Communication::class, $communication);
        $this->assertEquals('whatsapp', $communication->type);
        $this->assertEquals('Invoice #INV-001', $communication->subject);
        $this->assertStringContainsString('INV-001', $communication->message);
        $this->assertStringContainsString('1500', $communication->message);
        $this->assertEquals(123, $communication->metadata['invoice_id']);
        $this->assertEquals('INV-001', $communication->metadata['invoice_number']);
        $this->assertEquals(1500.00, $communication->metadata['amount']);
    }

    public function test_get_birthday_message_returns_correct_language()
    {
        $englishCustomer = Customer::create([
            'name' => 'English Customer',
            'preferred_language' => 'en',
        ]);

        $persianCustomer = Customer::create([
            'name' => 'Persian Customer',
            'preferred_language' => 'fa',
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->communicationService);
        $method = $reflection->getMethod('getBirthdayMessage');
        $method->setAccessible(true);

        $englishMessage = $method->invoke($this->communicationService, $englishCustomer);
        $persianMessage = $method->invoke($this->communicationService, $persianCustomer);

        $this->assertStringContainsString('Happy Birthday', $englishMessage);
        $this->assertStringContainsString('English Customer', $englishMessage);
        
        $this->assertStringContainsString('تولدت مبارک', $persianMessage);
        $this->assertStringContainsString('Persian Customer', $persianMessage);
    }

    public function test_get_anniversary_message_returns_correct_language()
    {
        $englishCustomer = Customer::create([
            'name' => 'English Customer',
            'preferred_language' => 'en',
        ]);

        $persianCustomer = Customer::create([
            'name' => 'Persian Customer',
            'preferred_language' => 'fa',
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->communicationService);
        $method = $reflection->getMethod('getAnniversaryMessage');
        $method->setAccessible(true);

        $englishMessage = $method->invoke($this->communicationService, $englishCustomer);
        $persianMessage = $method->invoke($this->communicationService, $persianCustomer);

        $this->assertStringContainsString('Happy Anniversary', $englishMessage);
        $this->assertStringContainsString('English Customer', $englishMessage);
        
        $this->assertStringContainsString('سالگرد ازدواجتان مبارک', $persianMessage);
        $this->assertStringContainsString('Persian Customer', $persianMessage);
    }

    public function test_get_communication_stats_returns_correct_structure()
    {
        // Create test communications
        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Email 1',
            'status' => 'sent',
        ]);

        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'SMS 1',
            'status' => 'delivered',
        ]);

        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Email 2',
            'status' => 'failed',
        ]);

        $stats = $this->communicationService->getCommunicationStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('success_rate', $stats);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['by_type']['email']);
        $this->assertEquals(1, $stats['by_type']['sms']);
        $this->assertEquals(1, $stats['by_status']['sent']);
        $this->assertEquals(1, $stats['by_status']['delivered']);
        $this->assertEquals(1, $stats['by_status']['failed']);
        $this->assertEquals(66.67, $stats['success_rate']); // 2 successful out of 3 total
    }

    public function test_get_communication_stats_applies_filters()
    {
        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Email',
            'created_at' => now(),
        ]);

        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'SMS',
            'created_at' => now()->subDays(2),
        ]);

        $stats = $this->communicationService->getCommunicationStats([
            'date_from' => now()->format('Y-m-d'),
            'type' => 'email',
        ]);

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['by_type']['email']);
        $this->assertArrayNotHasKey('sms', $stats['by_type']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}