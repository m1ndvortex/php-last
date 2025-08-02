<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
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
                        ],
                        'token',
                    ],
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
        $this->assertNotNull($response->json('data.token'));
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
                        'message' => 'Invalid email or password',
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
                        'message' => 'Invalid email or password',
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
                        'message' => 'Invalid email or password',
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
                        'message' => 'Invalid input data',
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

    public function test_authenticated_user_can_logout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out',
                ]);
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Authentication required',
                    ],
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
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Authentication required',
                    ],
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
                        'message' => 'Invalid input data',
                    ],
                ]);
    }

    public function test_authenticated_user_can_change_password()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully',
                ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_password_change_fails_with_wrong_current_password()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PASSWORD',
                        'message' => 'Current password is incorrect',
                    ],
                ]);
    }

    public function test_password_change_validation_fails_with_mismatched_confirmation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Invalid input data',
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