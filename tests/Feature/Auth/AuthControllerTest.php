<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiters before each test
        RateLimiter::clear('login_attempts:127.0.0.1');
        RateLimiter::clear('password_change:1');
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
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
                            'session_timeout',
                        ],
                        'token',
                        'session_expiry',
                        'server_time',
                    ],
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
        $this->assertNotNull($response->json('data.token'));
        $this->assertNotNull($response->json('data.session_expiry'));
        $this->assertNotNull($response->json('data.server_time'));
    }

    public function test_user_cannot_login_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'The provided credentials are incorrect. Please check your email and password.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_user_cannot_login_with_invalid_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'The provided credentials are incorrect. Please check your email and password.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_user_cannot_login_with_inactive_account()
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'The provided credentials are incorrect. Please check your email and password.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_login_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => '123', // Too short
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Please check your input and try again.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_login_updates_last_login_timestamp()
    {
        $this->assertNull($this->user->last_login_at);

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->last_login_at);
    }

    public function test_login_rate_limiting_works()
    {
        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_authenticated_user_can_logout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'logged_out_at',
                    ],
                ]);
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ]);
    }

    public function test_authenticated_user_can_get_user_info()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/user');

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
                            'session_timeout',
                        ],
                        'session' => [
                            'expires_at',
                            'time_remaining_minutes',
                            'is_expiring_soon',
                            'server_time',
                        ],
                    ],
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.user.id'));
    }

    public function test_unauthenticated_user_cannot_get_user_info()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ]);
    }

    public function test_authenticated_user_can_update_profile()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'preferred_language' => 'fa',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'name' => 'Updated Name',
                            'preferred_language' => 'fa',
                        ],
                    ],
                ]);

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('fa', $this->user->preferred_language);
    }

    public function test_profile_update_validation_fails_with_invalid_language()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/profile', [
            'preferred_language' => 'invalid',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Please check your input and try again.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_authenticated_user_can_change_password()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'NewPassword123', // Strong password
            'new_password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $this->user->password));
    }

    public function test_password_change_fails_with_wrong_current_password()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'NewPassword123', // Strong password
            'new_password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PASSWORD',
                        'message' => 'Current password is incorrect. Please verify and try again.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_password_change_validation_fails_with_mismatched_confirmation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Please check your input and try again.',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_inactive_user_cannot_access_protected_routes()
    {
        $this->user->update(['is_active' => false]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'ACCOUNT_INACTIVE',
                        'message' => 'Account is inactive',
                    ],
                ]);
    }

    public function test_session_validation_works_for_valid_session()
    {
        // Create a real token instead of using Sanctum::actingAs
        $token = $this->user->createToken('test-session');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/auth/validate-session');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'session_valid' => true,
                        'can_extend' => true,
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_valid',
                        'expires_at',
                        'time_remaining_minutes',
                        'is_expiring_soon',
                        'server_time',
                        'can_extend',
                    ],
                ]);
    }

    public function test_session_validation_fails_for_unauthenticated_user()
    {
        $response = $this->getJson('/api/auth/validate-session');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ]);
    }

    public function test_token_refresh_works()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'expires_at',
                        'server_time',
                        'refreshed_at',
                    ],
                ]);

        $this->assertNotNull($response->json('data.token'));
    }

    public function test_token_refresh_fails_for_unauthenticated_user()
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ]);
    }

    public function test_password_change_rate_limiting_works()
    {
        Sanctum::actingAs($this->user);

        // Make 3 failed password change attempts
        for ($i = 0; $i < 3; $i++) {
            $this->putJson('/api/auth/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);
        }

        // 4th attempt should be rate limited
        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_password_change_with_strong_password_works()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'NewStrongPassword123',
            'new_password_confirmation' => 'NewStrongPassword123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully. Other sessions have been logged out for security.',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'changed_at',
                        'other_sessions_revoked',
                    ],
                ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewStrongPassword123', $this->user->password));
    }

    public function test_password_change_validation_enforces_strong_password()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'weak', // Weak password
            'new_password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'retryable' => true,
                    ],
                ]);
    }

    public function test_profile_update_with_enhanced_validation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Test User',
            'preferred_language' => 'fa',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => [
                        'user' => [
                            'name' => 'Updated Test User',
                            'preferred_language' => 'fa',
                        ],
                    ],
                ]);

        $this->user->refresh();
        $this->assertEquals('Updated Test User', $this->user->name);
        $this->assertEquals('fa', $this->user->preferred_language);
    }

    public function test_profile_update_sanitizes_xss_attempts()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => '<script>alert("xss")</script>Malicious Name',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        // Verify the name was sanitized
        $this->user->refresh();
        $this->assertStringNotContainsString('<script>', $this->user->name);
        $this->assertStringContainsString('Malicious Name', $this->user->name);
    }

    public function test_health_check_endpoint_works()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version',
                ]);

        $this->assertEquals('ok', $response->json('status'));
    }
}