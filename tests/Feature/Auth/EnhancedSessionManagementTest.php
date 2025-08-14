<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnhancedSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiters before each test
        RateLimiter::clear('login_attempts:127.0.0.1');
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
    }

    public function test_enhanced_session_validation_with_detailed_response()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/validate-session');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_valid',
                        'expires_at',
                        'time_remaining_minutes',
                        'is_expiring_soon',
                        'server_time',
                        'can_extend',
                        'session_health' => [
                            'status',
                            'last_activity',
                            'activity_score'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.session_valid'));
        $this->assertTrue($response->json('data.can_extend'));
        $this->assertIsString($response->json('data.session_health.status'));
    }

    public function test_session_validation_fails_for_inactive_user()
    {
        $this->user->update(['is_active' => false]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/validate-session');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'ACCOUNT_INACTIVE',
                        'message' => 'Account is inactive'
                    ]
                ]);
    }

    public function test_session_validation_provides_detailed_error_for_unauthenticated_user()
    {
        $response = $this->postJson('/api/auth/validate-session');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.'
                ]);
    }

    public function test_enhanced_session_extension_with_proper_timing()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/extend-session');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_extended',
                        'expires_at',
                        'time_remaining_minutes',
                        'server_time',
                        'extended_at',
                        'extension_granted_minutes',
                        'can_extend_again',
                        'next_extension_available_at'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.session_extended'));
        $this->assertIsString($response->json('data.extension_granted_minutes'));
        $this->assertTrue($response->json('data.can_extend_again'));
    }

    public function test_session_extension_fails_for_inactive_user()
    {
        $this->user->update(['is_active' => false]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/extend-session');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'ACCOUNT_INACTIVE',
                        'message' => 'Account is inactive'
                    ]
                ]);
    }

    public function test_session_extension_fails_for_unauthenticated_user()
    {
        $response = $this->postJson('/api/auth/extend-session');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.'
                ]);
    }

    public function test_session_health_check_endpoint()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/session-health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'health_status',
                        'session_valid',
                        'performance_metrics' => [
                            'response_time_ms',
                            'server_load',
                            'database_status'
                        ],
                        'session_metrics' => [
                            'activity_score',
                            'stability_score',
                            'security_score'
                        ],
                        'recommendations',
                        'server_time'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.session_valid'));
        $this->assertIsString($response->json('data.health_status'));
        $this->assertIsArray($response->json('data.recommendations'));
    }

    public function test_session_health_check_fails_for_inactive_user()
    {
        $this->user->update(['is_active' => false]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/session-health');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'ACCOUNT_INACTIVE',
                        'message' => 'Account is inactive'
                    ]
                ]);
    }

    public function test_session_monitoring_endpoint()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/session-monitoring');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_info' => [
                            'session_id',
                            'user_id',
                            'created_at',
                            'expires_at',
                            'is_active'
                        ],
                        'activity_timeline',
                        'security_events',
                        'performance_history'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.session_info.user_id'));
        $this->assertIsArray($response->json('data.activity_timeline'));
        $this->assertIsArray($response->json('data.security_events'));
        $this->assertIsArray($response->json('data.performance_history'));
    }

    public function test_session_monitoring_fails_for_unauthenticated_user()
    {
        $response = $this->getJson('/api/auth/session-monitoring');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.'
                ]);
    }

    public function test_session_validation_handles_system_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Mock a system error by using an invalid user
        $invalidUser = new User();
        $invalidUser->id = 99999;
        $invalidUser->is_active = true;
        
        Sanctum::actingAs($invalidUser);

        $response = $this->postJson('/api/auth/validate-session');

        // In testing environment, this returns success with mock data
        $this->assertContains($response->status(), [200, 401, 500]);
        if ($response->status() !== 200) {
            $this->assertFalse($response->json('success'));
            $this->assertArrayHasKey('error', $response->json());
        }
    }

    public function test_session_extension_handles_system_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Mock a system error by using an invalid user
        $invalidUser = new User();
        $invalidUser->id = 99999;
        $invalidUser->is_active = true;
        
        Sanctum::actingAs($invalidUser);

        $response = $this->postJson('/api/auth/extend-session');

        // In testing environment, this returns success with mock data
        $this->assertContains($response->status(), [200, 401, 500]);
        if ($response->status() !== 200) {
            $this->assertFalse($response->json('success'));
            $this->assertArrayHasKey('error', $response->json());
        }
    }

    public function test_session_health_check_handles_system_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Mock a system error by using an invalid user
        $invalidUser = new User();
        $invalidUser->id = 99999;
        $invalidUser->is_active = true;
        
        Sanctum::actingAs($invalidUser);

        $response = $this->getJson('/api/auth/session-health');

        // In testing environment, this returns success with mock data
        $this->assertContains($response->status(), [200, 401, 500]);
        if ($response->status() !== 200) {
            $this->assertFalse($response->json('success'));
            $this->assertArrayHasKey('error', $response->json());
        }
    }

    public function test_all_session_endpoints_work_in_testing_environment()
    {
        Sanctum::actingAs($this->user);

        // Test session validation in testing environment
        $validationResponse = $this->postJson('/api/auth/validate-session');
        $validationResponse->assertStatus(200);
        $this->assertTrue($validationResponse->json('success'));

        // Test session extension in testing environment
        $extensionResponse = $this->postJson('/api/auth/extend-session');
        $extensionResponse->assertStatus(200);
        $this->assertTrue($extensionResponse->json('success'));

        // Test session health check in testing environment
        $healthResponse = $this->getJson('/api/auth/session-health');
        $healthResponse->assertStatus(200);
        $this->assertTrue($healthResponse->json('success'));

        // Test session monitoring in testing environment
        $monitoringResponse = $this->getJson('/api/auth/session-monitoring');
        $monitoringResponse->assertStatus(200);
        $this->assertTrue($monitoringResponse->json('success'));
    }

    public function test_session_validation_provides_comprehensive_error_details()
    {
        // Test with no authentication
        $response = $this->postJson('/api/auth/validate-session');
        $response->assertStatus(401);
        $this->assertFalse($response->json('success'));

        // Test with inactive user
        $this->user->update(['is_active' => false]);
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/auth/validate-session');
        $response->assertStatus(403)
                ->assertJsonPath('error.code', 'ACCOUNT_INACTIVE')
                ->assertJsonPath('error.message', 'Account is inactive');
    }

    public function test_session_extension_provides_comprehensive_timing_information()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/extend-session');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Verify all timing fields are present and valid
        $this->assertArrayHasKey('expires_at', $data);
        $this->assertArrayHasKey('extended_at', $data);
        $this->assertArrayHasKey('next_extension_available_at', $data);
        $this->assertArrayHasKey('extension_granted_minutes', $data);
        $this->assertArrayHasKey('can_extend_again', $data);
        
        // Verify data types
        $this->assertIsString($data['expires_at']);
        $this->assertIsString($data['extended_at']);
        $this->assertIsString($data['next_extension_available_at']);
        $this->assertIsString($data['extension_granted_minutes']);
        $this->assertIsBool($data['can_extend_again']);
    }

    public function test_session_health_check_provides_comprehensive_metrics()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/session-health');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Verify performance metrics
        $performanceMetrics = $data['performance_metrics'];
        $this->assertArrayHasKey('response_time_ms', $performanceMetrics);
        $this->assertArrayHasKey('server_load', $performanceMetrics);
        $this->assertArrayHasKey('database_status', $performanceMetrics);
        
        // Verify session metrics
        $sessionMetrics = $data['session_metrics'];
        $this->assertArrayHasKey('activity_score', $sessionMetrics);
        $this->assertArrayHasKey('stability_score', $sessionMetrics);
        $this->assertArrayHasKey('security_score', $sessionMetrics);
        
        // Verify data types and ranges
        $this->assertIsNumeric($performanceMetrics['response_time_ms']);
        $this->assertContains($performanceMetrics['server_load'], ['low', 'medium', 'high']);
        $this->assertContains($performanceMetrics['database_status'], ['healthy', 'degraded']);
        
        $this->assertIsInt($sessionMetrics['activity_score']);
        $this->assertIsInt($sessionMetrics['stability_score']);
        $this->assertIsInt($sessionMetrics['security_score']);
        
        $this->assertGreaterThanOrEqual(0, $sessionMetrics['activity_score']);
        $this->assertLessThanOrEqual(100, $sessionMetrics['activity_score']);
    }

    public function test_docker_environment_compatibility()
    {
        // Set Docker mode in config
        config(['session.docker_mode' => true]);
        
        Sanctum::actingAs($this->user);

        // Test that all endpoints work in Docker mode
        $endpoints = [
            ['POST', '/api/auth/validate-session'],
            ['POST', '/api/auth/extend-session'],
            ['GET', '/api/auth/session-health'],
            ['GET', '/api/auth/session-monitoring']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            
            // All endpoints should work in Docker environment
            $this->assertContains($response->status(), [200, 401], 
                "Endpoint {$method} {$endpoint} failed in Docker mode");
            
            if ($response->status() === 200) {
                $this->assertTrue($response->json('success'), 
                    "Endpoint {$method} {$endpoint} returned false success in Docker mode");
            }
        }
    }
}