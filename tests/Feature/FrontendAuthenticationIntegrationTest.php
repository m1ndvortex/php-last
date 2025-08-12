<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FrontendAuthenticationIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'preferred_language' => 'en'
        ]);
        
        // Clean up any existing tokens
        $this->user->tokens()->delete();
    }

    /** @test */
    public function frontend_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'preferred_language',
                            'role',
                            'last_login_at',
                            'session_timeout'
                        ],
                        'token',
                        'session_expiry',
                        'server_time'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('data.token'));
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
    }

    /** @test */
    public function frontend_can_validate_session()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Validate session
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/validate-session');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_valid',
                        'expires_at',
                        'time_remaining_minutes',
                        'is_expiring_soon',
                        'server_time',
                        'can_extend'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.session_valid'));
    }

    /** @test */
    public function frontend_can_extend_session()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Extend session
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/extend-session');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_extended',
                        'expires_at',
                        'time_remaining_minutes',
                        'server_time',
                        'extended_at'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.session_extended'));
    }

    /** @test */
    public function frontend_can_refresh_token()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Refresh token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'expires_at',
                        'server_time',
                        'refreshed_at'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('data.token'));
        $this->assertNotEquals($token, $response->json('data.token'));
    }

    /** @test */
    public function frontend_can_get_user_info()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Get user info
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'preferred_language',
                            'role',
                            'last_login_at',
                            'two_factor_enabled',
                            'session_timeout'
                        ],
                        'session' => [
                            'expires_at',
                            'time_remaining_minutes',
                            'is_expiring_soon',
                            'server_time'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
    }

    /** @test */
    public function frontend_can_logout()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'logged_out_at'
                    ]
                ]);

        $this->assertTrue($response->json('success'));

        // Verify that the token was deleted from the database
        $this->assertEquals(0, $this->user->fresh()->tokens()->count(), 'All tokens should be deleted after logout');
        
        // In a real-world scenario, the token would be invalid for subsequent requests
        // The test environment caches the token within the same request lifecycle
        // so we verify the database state instead
    }

    /** @test */
    public function frontend_handles_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJsonStructure([
                    'success',
                    'error' => [
                        'code',
                        'message',
                        'details',
                        'retryable'
                    ]
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('INVALID_CREDENTIALS', $response->json('error.code'));
    }

    /** @test */
    public function frontend_handles_session_expiry()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Manually expire the token by deleting it from database
        $this->user->tokens()->delete();

        // Try to validate session with expired token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/validate-session');

        $response->assertStatus(401);
        
        // The response might be from the middleware or the controller
        $responseData = $response->json();
        if (isset($responseData['success'])) {
            // Response from controller
            $this->assertFalse($responseData['success']);
            $this->assertArrayHasKey('error', $responseData);
        } else {
            // Response from middleware (unauthenticated)
            $this->assertArrayHasKey('error', $responseData);
        }
    }

    /** @test */
    public function frontend_handles_rate_limiting()
    {
        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);

            if ($i < 5) {
                $response->assertStatus(401);
            } else {
                // 6th attempt should be rate limited
                $response->assertStatus(429)
                        ->assertJsonStructure([
                            'success',
                            'error' => [
                                'code',
                                'message',
                                'details',
                                'retryable',
                                'retry_after'
                            ]
                        ]);

                $this->assertFalse($response->json('success'));
                $this->assertEquals('RATE_LIMITED', $response->json('error.code'));
            }
        }
    }

    /** @test */
    public function frontend_authentication_flow_works_end_to_end()
    {
        // 1. Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token);

        // 2. Get user info
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $userResponse->assertStatus(200);
        $this->assertEquals('test@example.com', $userResponse->json('data.user.email'));

        // 3. Validate session
        $validateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/validate-session');

        $validateResponse->assertStatus(200);
        $this->assertTrue($validateResponse->json('data.session_valid'));

        // 4. Extend session
        $extendResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/extend-session');

        $extendResponse->assertStatus(200);
        $this->assertTrue($extendResponse->json('data.session_extended'));

        // 5. Refresh token
        $refreshResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $refreshResponse->assertStatus(200);
        $newToken = $refreshResponse->json('data.token');
        $this->assertNotNull($newToken);
        $this->assertNotEquals($token, $newToken);

        // 6. Use new token to get user info
        $newUserResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->getJson('/api/auth/user');

        $newUserResponse->assertStatus(200);
        $this->assertEquals('test@example.com', $newUserResponse->json('data.user.email'));

        // 7. Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200);
        $this->assertTrue($logoutResponse->json('success'));

        // 8. Verify that all tokens were deleted from the database
        $this->assertEquals(0, $this->user->fresh()->tokens()->count(), 'All tokens should be deleted after logout in end-to-end test');
    }
}