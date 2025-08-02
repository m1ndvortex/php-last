<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Authenticate user with email and password.
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = User::where('email', $email)
                   ->where('is_active', true)
                   ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // Update last login timestamp
        $user->updateLastLogin();

        // Create Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $this->formatUserData($user),
            'token' => $token,
        ];
    }

    /**
     * Logout user by revoking current token.
     *
     * @param User $user
     * @param string|null $tokenId
     * @return bool
     */
    public function logout(User $user, ?string $tokenId = null): bool
    {
        if ($tokenId) {
            $token = $user->tokens()->where('id', $tokenId)->first();
            if ($token) {
                $token->delete();
                return true;
            }
            return false;
        }

        // If no specific token ID, revoke current token
        $user->currentAccessToken()?->delete();
        return true;
    }

    /**
     * Logout user from all devices by revoking all tokens.
     *
     * @param User $user
     * @return int
     */
    public function logoutFromAllDevices(User $user): int
    {
        return $user->tokens()->delete();
    }

    /**
     * Check if a token is valid and active.
     *
     * @param string $token
     * @return User|null
     */
    public function validateToken(string $token): ?User
    {
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken || !$accessToken->tokenable) {
            return null;
        }

        $user = $accessToken->tokenable;
        
        if (!$user->is_active) {
            return null;
        }

        return $user;
    }

    /**
     * Format user data for API responses.
     *
     * @param User $user
     * @return array
     */
    public function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'preferred_language' => $user->preferred_language,
            'role' => $user->role,
            'last_login_at' => $user->last_login_at?->toISOString(),
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
        ];
    }

    /**
     * Update user profile information.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function updateProfile(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Change user password.
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        return $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }
}