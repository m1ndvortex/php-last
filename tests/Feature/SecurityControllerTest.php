<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use App\Models\AuditLog;
use App\Models\LoginAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_enable_2fa()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Mock the communication service to avoid actual SMS sending
        $this->mock(\App\Services\CommunicationService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->andReturn(true);
        });

        $response = $this->postJson('/api/security/2fa/enable', [
            'type' => 'sms',
            'phone' => '+1234567890'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['phone', 'backup_codes']
                ]);
    }

    public function test_can_get_active_sessions()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create some sessions
        UserSession::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/security/sessions/active');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'session_id',
                            'ip_address',
                            'device_info',
                            'location',
                            'last_activity',
                            'duration',
                            'is_current'
                        ]
                    ]
                ]);
    }

    public function test_can_terminate_session()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = UserSession::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/security/sessions/terminate', [
            'session_id' => $session->session_id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Session terminated successfully'
                ]);
    }

    public function test_can_get_audit_logs()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create some audit logs
        AuditLog::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/security/audit/logs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'event',
                            'auditable_type',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]);
    }

    public function test_can_filter_audit_logs()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        AuditLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'login',
            'severity' => 'info'
        ]);

        AuditLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'logout',
            'severity' => 'warning'
        ]);

        $response = $this->getJson('/api/security/audit/logs?event=login&severity=info');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('login', $data[0]['event']);
    }

    public function test_can_get_audit_statistics()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create audit logs with different severities
        AuditLog::factory()->create(['severity' => 'info']);
        AuditLog::factory()->create(['severity' => 'warning']);
        AuditLog::factory()->create(['severity' => 'error']);

        $response = $this->getJson('/api/security/audit/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_events',
                        'events_by_severity',
                        'events_by_type',
                        'top_users',
                        'events_by_day'
                    ]
                ]);
    }

    public function test_can_export_audit_logs_as_csv()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        AuditLog::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/security/audit/export', [
            'format' => 'csv'
        ]);



        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Disposition');
    }

    public function test_can_export_audit_logs_as_json()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        AuditLog::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/security/audit/export', [
            'format' => 'json'
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Disposition');
    }

    public function test_can_get_login_anomalies()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        LoginAnomaly::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_resolved' => false
        ]);

        $response = $this->getJson('/api/security/anomalies');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'severity',
                            'risk_score',
                            'created_at',
                            'ip_address',
                            'location',
                            'data'
                        ]
                    ]
                ]);
    }

    public function test_can_get_anomaly_statistics()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        LoginAnomaly::factory()->create(['severity' => 'high']);
        LoginAnomaly::factory()->create(['severity' => 'medium']);
        LoginAnomaly::factory()->create(['type' => 'suspicious_ip']);

        $response = $this->getJson('/api/security/anomalies/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_anomalies',
                        'by_severity',
                        'by_type',
                        'resolved_count',
                        'false_positive_count',
                        'high_risk_count'
                    ]
                ]);
    }

    public function test_can_get_session_stats()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        UserSession::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/security/sessions/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_sessions',
                        'active_sessions',
                        'unique_ips',
                        'unique_devices',
                        'last_login',
                        'current_session_duration'
                    ]
                ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/security/sessions/active');
        $response->assertStatus(401);
    }

    public function test_validates_2fa_enable_request()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/security/2fa/enable', [
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    public function test_validates_session_terminate_request()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/security/sessions/terminate', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_id']);
    }
}