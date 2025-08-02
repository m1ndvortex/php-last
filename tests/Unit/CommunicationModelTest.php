<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommunicationModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and customer
        $this->user = User::factory()->create();
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
    }

    public function test_communication_can_be_created()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
        ]);

        $this->assertInstanceOf(Communication::class, $communication);
        $this->assertEquals('email', $communication->type);
        $this->assertEquals('Test Subject', $communication->subject);
        $this->assertEquals('Test message content', $communication->message);
        $this->assertEquals('draft', $communication->status);
    }

    public function test_communication_belongs_to_customer()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test note',
        ]);

        $this->assertInstanceOf(Customer::class, $communication->customer);
        $this->assertEquals($this->customer->id, $communication->customer->id);
    }

    public function test_communication_belongs_to_user()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test note',
        ]);

        $this->assertInstanceOf(User::class, $communication->user);
        $this->assertEquals($this->user->id, $communication->user->id);
    }

    public function test_communication_scope_of_type()
    {
        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Email message',
        ]);

        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'SMS message',
        ]);

        $emailCommunications = Communication::ofType('email')->get();
        $this->assertCount(1, $emailCommunications);
        $this->assertEquals('email', $emailCommunications->first()->type);
    }

    public function test_communication_scope_with_status()
    {
        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Draft message',
            'status' => 'draft',
        ]);

        Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Sent message',
            'status' => 'sent',
        ]);

        $sentCommunications = Communication::withStatus('sent')->get();
        $this->assertCount(1, $sentCommunications);
        $this->assertEquals('sent', $sentCommunications->first()->status);
    }

    public function test_communication_can_be_marked_as_sent()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Test message',
        ]);

        $this->assertEquals('draft', $communication->status);
        $this->assertNull($communication->sent_at);

        $communication->markAsSent();

        $this->assertEquals('sent', $communication->fresh()->status);
        $this->assertNotNull($communication->fresh()->sent_at);
    }

    public function test_communication_can_be_marked_as_delivered()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'sms',
            'message' => 'Test SMS',
        ]);

        $communication->markAsDelivered();

        $this->assertEquals('delivered', $communication->fresh()->status);
        $this->assertNotNull($communication->fresh()->delivered_at);
    }

    public function test_communication_can_be_marked_as_read()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'email',
            'message' => 'Test email',
        ]);

        $communication->markAsRead();

        $this->assertEquals('read', $communication->fresh()->status);
        $this->assertNotNull($communication->fresh()->read_at);
    }

    public function test_communication_can_be_marked_as_failed()
    {
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'whatsapp',
            'message' => 'Test WhatsApp',
        ]);

        $communication->markAsFailed('API connection failed');

        $freshCommunication = $communication->fresh();
        $this->assertEquals('failed', $freshCommunication->status);
        $this->assertEquals('API connection failed', $freshCommunication->metadata['failure_reason']);
    }

    public function test_communication_casts_metadata_as_array()
    {
        $metadata = ['key' => 'value', 'number' => 123];
        
        $communication = Communication::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test note',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($communication->metadata);
        $this->assertEquals($metadata, $communication->metadata);
    }

    public function test_communication_constants_are_defined()
    {
        $this->assertIsArray(Communication::TYPES);
        $this->assertArrayHasKey('email', Communication::TYPES);
        $this->assertArrayHasKey('sms', Communication::TYPES);
        $this->assertArrayHasKey('whatsapp', Communication::TYPES);
        $this->assertArrayHasKey('phone', Communication::TYPES);
        $this->assertArrayHasKey('meeting', Communication::TYPES);
        $this->assertArrayHasKey('note', Communication::TYPES);

        $this->assertIsArray(Communication::STATUSES);
        $this->assertArrayHasKey('draft', Communication::STATUSES);
        $this->assertArrayHasKey('sent', Communication::STATUSES);
        $this->assertArrayHasKey('delivered', Communication::STATUSES);
        $this->assertArrayHasKey('read', Communication::STATUSES);
        $this->assertArrayHasKey('failed', Communication::STATUSES);
    }
}