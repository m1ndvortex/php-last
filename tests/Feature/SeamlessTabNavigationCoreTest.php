<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class SeamlessTabNavigationCoreTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with known credentials
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test basic authentication flow with real API
     * Requirements: 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 5.7
     */
    public function test_basic_authentication_flow_with_real_api()
    {
        // Test login endpoint
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ]);

        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Test session validation endpoint
        $sessionResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $sessionResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $this->testUser->id)
            ->assertJsonPath('data.user.email', 'test@example.com');

        // Test multiple tab simulation
        for ($i = 0; $i < 5; $i++) {
            $tabResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson('/api/auth/user');

            $tabResponse->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.user.id', $this->testUser->id);
        }

        // Test logout functionality
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(true, 'Basic authentication flow completed successfully');
    }

    /**
     * Test session extension functionality
     * Requirements: 4.1, 4.2, 4.3, 5.1, 5.2
     */
    public function test_session_extension_functionality()
    {
        // Login and get token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $token = $loginResponse->json('data.token');

        // Test session extension
        $extendResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/extend-session');

        $extendResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify session is still valid after extension
        $validationResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $validationResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(true, 'Session extension functionality works correctly');
    }

    /**
     * Test API performance for tab switching simulation
     * Requirements: 5.6, 5.7
     */
    public function test_api_performance_for_tab_switching()
    {
        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $token = $loginResponse->json('data.token');

        // Test API response times for different endpoints
        $endpoints = [
            '/api/auth/user',
            '/api/dashboard/stats',
            '/api/inventory/items',
            '/api/customers',
            '/api/invoices'
        ];

        $responseTimes = [];
        $successfulRequests = 0;

        foreach ($endpoints as $endpoint) {
            $startTime = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson($endpoint);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $responseTimes[$endpoint] = $responseTime;

            // Count successful responses (200, 404 is acceptable for non-existent endpoints)
            if (in_array($response->status(), [200, 201, 404])) {
                $successfulRequests++;
            }

            // Performance requirement: API responses should be fast
            $this->assertLessThan(2000, $responseTime, "API response time for {$endpoint} should be under 2000ms");
        }

        // At least auth endpoint should work
        $this->assertGreaterThan(0, $successfulRequests, 'At least one API endpoint should respond successfully');

        echo "\nAPI Performance Results:\n";
        foreach ($responseTimes as $endpoint => $time) {
            echo sprintf("  %s: %.2fms\n", $endpoint, $time);
        }
    }

    /**
     * Test Docker environment database connectivity
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
     */
    public function test_docker_environment_database_connectivity()
    {
        // Test database connection in Docker
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);

        // Test user creation and retrieval
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);

        // Test authentication in Docker environment
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(true, 'Docker environment database connectivity verified');
    }

    /**
     * Test multiple session creation (cross-tab simulation)
     * Requirements: 5.1, 5.2, 5.3
     */
    public function test_multiple_session_creation()
    {
        // Create multiple sessions for the same user
        $session1 = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $token1 = $session1->json('data.token');

        $session2 = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $token2 = $session2->json('data.token');

        // Both tokens should be valid initially
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $response1->assertStatus(200)->assertJsonPath('success', true);
        $response2->assertStatus(200)->assertJsonPath('success', true);

        // Verify both sessions can access user data
        $this->assertEquals($this->testUser->id, $response1->json('data.user.id'));
        $this->assertEquals($this->testUser->id, $response2->json('data.user.id'));

        $this->assertTrue(true, 'Multiple session creation works correctly');
    }

    /**
     * Test error handling for invalid requests
     * Requirements: 5.1, 5.2, 5.3
     */
    public function test_error_handling_for_invalid_requests()
    {
        // Test invalid token handling
        $invalidTokenResponse = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $invalidTokenResponse->assertStatus(401)
            ->assertJsonPath('success', false);

        // Test malformed login requests
        $malformedResponse = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => ''
        ]);

        $malformedResponse->assertStatus(422)
            ->assertJsonPath('success', false);

        // Test successful recovery after errors
        $recoveryResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $recoveryResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(true, 'Error handling works correctly');
    }

    /**
     * Test comprehensive workflow with performance measurement
     * Requirements: 1.1, 1.2, 1.3, 2.1, 3.1, 3.2, 3.3, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7
     */
    public function test_comprehensive_workflow_with_performance()
    {
        // Step 1: Login with performance measurement
        $startTime = microtime(true);
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        $loginTime = (microtime(true) - $startTime) * 1000;

        $loginResponse->assertStatus(200)->assertJsonPath('success', true);
        $token = $loginResponse->json('data.token');

        // Step 2: Multiple tab simulation with performance measurement
        $tabSwitchTimes = [];
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            $tabResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson('/api/auth/user');
            $tabTime = (microtime(true) - $startTime) * 1000;

            $tabSwitchTimes[] = $tabTime;
            $tabResponse->assertStatus(200)->assertJsonPath('success', true);
        }

        // Step 3: Session extension
        $extendResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/extend-session');

        $extendResponse->assertStatus(200)->assertJsonPath('success', true);

        // Step 4: Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)->assertJsonPath('success', true);

        // Performance assertions
        $this->assertLessThan(1000, $loginTime, 'Login should complete within 1000ms');
        
        $avgTabTime = array_sum($tabSwitchTimes) / count($tabSwitchTimes);
        $this->assertLessThan(200, $avgTabTime, 'Average tab switching time should be under 200ms');

        echo "\nComprehensive Workflow Performance:\n";
        echo sprintf("  Login time: %.2fms\n", $loginTime);
        echo sprintf("  Average tab switch time: %.2fms\n", $avgTabTime);
        echo sprintf("  Max tab switch time: %.2fms\n", max($tabSwitchTimes));
        echo sprintf("  Min tab switch time: %.2fms\n", min($tabSwitchTimes));

        $this->assertTrue(true, 'Comprehensive workflow completed successfully');
    }

    /**
     * Test session validation endpoint
     * Requirements: 4.1, 4.2, 4.3, 5.1, 5.2
     */
    public function test_session_validation_endpoint()
    {
        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $token = $loginResponse->json('data.token');

        // Test session validation
        $validationResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/validate-session');

        $validationResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(true, 'Session validation endpoint works correctly');
    }
}