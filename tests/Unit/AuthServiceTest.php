<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authService = new AuthService();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
    }

    public function test_authenticate_with_valid_credentials()
    {
        $result = $this->authService->authenticate('test@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertNotNull($result['token']);
    }

    public function test_authenticate_with_invalid_email()
    {
        $result = $this->authService->authenticate('wrong@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_authenticate_with_invalid_password()
    {
        $result = $this->authService->authenticate('test@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function test_authenticate_with_inactive_user()
    {
        $this->user->update(['is_active' => false]);

        $result = $this->authService->authenticate('test@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_authenticate_updates_last_login()
    {
        $this->assertNull($this->user->last_login_at);

        $this->authService->authenticate('test@example.com', 'password123');

        $this->user->refresh();
        $this->assertNotNull($this->user->last_login_at);
    }

    public function test_logout_revokes_current_token()
    {
        $token = $this->user->createToken('test-token');
        
        $result = $this->authService->logout($this->user);

        $this->assertTrue($result);
    }

    public function test_logout_from_all_devices()
    {
        $this->user->createToken('token1');
        $this->user->createToken('token2');
        $this->user->createToken('token3');

        $this->assertEquals(3, $this->user->tokens()->count());

        $deletedCount = $this->authService->logoutFromAllDevices($this->user);

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(0, $this->user->tokens()->count());
    }

    public function test_validate_token_with_valid_token()
    {
        $token = $this->user->createToken('test-token');
        
        $result = $this->authService->validateToken($token->plainTextToken);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($this->user->id, $result->id);
    }

    public function test_validate_token_with_invalid_token()
    {
        $result = $this->authService->validateToken('invalid-token');

        $this->assertNull($result);
    }

    public function test_validate_token_with_inactive_user()
    {
        $token = $this->user->createToken('test-token');
        $this->user->update(['is_active' => false]);
        
        $result = $this->authService->validateToken($token->plainTextToken);

        $this->assertNull($result);
    }

    public function test_format_user_data()
    {
        $result = $this->authService->formatUserData($this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('preferred_language', $result);
        $this->assertArrayHasKey('role', $result);
        $this->assertArrayHasKey('last_login_at', $result);
        $this->assertArrayHasKey('two_factor_enabled', $result);
        
        $this->assertEquals($this->user->id, $result['id']);
        $this->assertEquals($this->user->email, $result['email']);
    }

    public function test_update_profile()
    {
        $result = $this->authService->updateProfile($this->user, [
            'name' => 'Updated Name',
            'preferred_language' => 'fa',
        ]);

        $this->assertTrue($result);
        
        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('fa', $this->user->preferred_language);
    }

    public function test_change_password_with_correct_current_password()
    {
        $result = $this->authService->changePassword($this->user, 'password123', 'newpassword123');

        $this->assertTrue($result);
        
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_change_password_with_incorrect_current_password()
    {
        $result = $this->authService->changePassword($this->user, 'wrongpassword', 'newpassword123');

        $this->assertFalse($result);
        
        $this->user->refresh();
        $this->assertTrue(Hash::check('password123', $this->user->password));
    }
}