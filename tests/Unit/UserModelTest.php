<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_required_fields()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('en', $user->preferred_language); // Default value
        $this->assertEquals('owner', $user->role); // Default value
        $this->assertTrue($user->is_active); // Default value
        $this->assertFalse($user->two_factor_enabled); // Default value
    }

    public function test_user_preferred_language_defaults_to_config_locale()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->assertEquals(config('app.locale', 'en'), $user->preferred_language);
    }

    public function test_user_can_have_persian_preferred_language()
    {
        $user = User::create([
            'name' => 'کاربر تست',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'preferred_language' => 'fa',
        ]);

        $this->assertEquals('fa', $user->preferred_language);
    }

    public function test_user_has_two_factor_enabled_returns_false_when_disabled()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => false,
        ]);

        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_user_has_two_factor_enabled_returns_false_when_no_secret()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
            'two_factor_secret' => null,
        ]);

        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_user_has_two_factor_enabled_returns_true_when_enabled_with_secret()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
            'two_factor_secret' => 'secret123',
        ]);

        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_user_update_last_login_updates_timestamp()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->assertNull($user->last_login_at);

        $user->updateLastLogin();
        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->last_login_at);
    }

    public function test_user_password_is_hidden_in_array()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('two_factor_secret', $userArray);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $userArray);
    }

    public function test_user_can_create_sanctum_tokens()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('test-token');

        $this->assertNotNull($token);
        $this->assertNotNull($token->plainTextToken);
        $this->assertEquals('test-token', $token->accessToken->name);
    }

    public function test_user_two_factor_recovery_codes_are_cast_to_array()
    {
        $recoveryCodes = ['code1', 'code2', 'code3'];
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        $this->assertIsArray($user->two_factor_recovery_codes);
        $this->assertEquals($recoveryCodes, $user->two_factor_recovery_codes);
    }

    public function test_user_email_must_be_unique()
    {
        User::create([
            'name' => 'First User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'Second User',
            'email' => 'test@example.com', // Same email
            'password' => bcrypt('password123'),
        ]);
    }
}