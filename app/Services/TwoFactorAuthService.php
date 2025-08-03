<?php

namespace App\Services;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;
    private MessageTemplateService $messageTemplateService;
    private CommunicationService $communicationService;

    public function __construct(
        MessageTemplateService $messageTemplateService,
        CommunicationService $communicationService
    ) {
        $this->google2fa = new Google2FA();
        $this->messageTemplateService = $messageTemplateService;
        $this->communicationService = $communicationService;
    }

    /**
     * Enable 2FA for user
     */
    public function enable2FA(User $user, string $type, ?string $phone = null): array
    {
        if ($type === 'totp') {
            return $this->enableTOTP($user);
        } elseif ($type === 'sms') {
            return $this->enableSMS($user, $phone);
        }

        throw new \InvalidArgumentException('Invalid 2FA type');
    }

    /**
     * Enable TOTP 2FA
     */
    private function enableTOTP(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Store secret temporarily until confirmed
        Cache::put("2fa_setup_{$user->id}", [
            'type' => 'totp',
            'secret' => $secret
        ], 600); // 10 minutes

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'backup_codes' => $this->generateBackupCodes()
        ];
    }

    /**
     * Enable SMS 2FA
     */
    private function enableSMS(User $user, string $phone): array
    {
        // Store phone temporarily until confirmed
        Cache::put("2fa_setup_{$user->id}", [
            'type' => 'sms',
            'phone' => $phone
        ], 600); // 10 minutes

        // Send verification code
        $this->sendSMSCode($user, $phone);

        return [
            'phone' => $phone,
            'backup_codes' => $this->generateBackupCodes()
        ];
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm2FA(User $user, string $code, ?array $backupCodes = null): bool
    {
        $setupData = Cache::get("2fa_setup_{$user->id}");
        
        if (!$setupData) {
            return false;
        }

        $isValid = false;

        if ($setupData['type'] === 'totp') {
            $isValid = $this->google2fa->verifyKey($setupData['secret'], $code);
        } elseif ($setupData['type'] === 'sms') {
            $isValid = $this->verifySMSCode($user, $code);
        }

        if ($isValid) {
            $user->update([
                'two_factor_enabled' => true,
                'two_factor_type' => $setupData['type'],
                'two_factor_secret' => $setupData['secret'] ?? null,
                'two_factor_phone' => $setupData['phone'] ?? null,
                'two_factor_confirmed_at' => now(),
                'two_factor_backup_codes' => $backupCodes ? encrypt(json_encode($backupCodes)) : null
            ]);

            Cache::forget("2fa_setup_{$user->id}");
            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for user
     */
    public function disable2FA(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_type' => null,
            'two_factor_secret' => null,
            'two_factor_phone' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_backup_codes' => null
        ]);

        // Clean up any pending codes
        TwoFactorCode::where('user_id', $user->id)->delete();
    }

    /**
     * Send 2FA code for login
     */
    public function sendLoginCode(User $user): bool
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        if ($user->two_factor_type === 'sms') {
            return $this->sendSMSCode($user, $user->two_factor_phone);
        }

        return false; // TOTP doesn't need to send codes
    }

    /**
     * Verify 2FA code for login
     */
    public function verifyLoginCode(User $user, string $code, string $ipAddress = null): bool
    {
        if (!$user->two_factor_enabled) {
            return true; // 2FA not enabled
        }

        // Check if it's a backup code
        if ($this->verifyBackupCode($user, $code)) {
            return true;
        }

        if ($user->two_factor_type === 'totp') {
            return $this->google2fa->verifyKey($user->two_factor_secret, $code);
        } elseif ($user->two_factor_type === 'sms') {
            return $this->verifySMSCode($user, $code, $ipAddress);
        }

        return false;
    }

    /**
     * Send SMS code
     */
    private function sendSMSCode(User $user, string $phone): bool
    {
        // Clean up old codes
        TwoFactorCode::where('user_id', $user->id)
            ->where('type', 'sms')
            ->delete();

        $code = TwoFactorCode::generateCode();
        
        TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'sms',
            'expires_at' => now()->addMinutes(5)
        ]);

        // Send SMS using communication service
        $message = "Your verification code is: {$code}. Valid for 5 minutes.";
        
        try {
            return $this->communicationService->sendSMS($phone, $message);
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA SMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify SMS code
     */
    private function verifySMSCode(User $user, string $code, string $ipAddress = null): bool
    {
        $twoFactorCode = TwoFactorCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('type', 'sms')
            ->valid()
            ->first();

        if ($twoFactorCode) {
            $twoFactorCode->markAsUsed($ipAddress);
            return true;
        }

        return false;
    }

    /**
     * Verify backup code
     */
    private function verifyBackupCode(User $user, string $code): bool
    {
        if (!$user->two_factor_backup_codes) {
            return false;
        }

        try {
            $backupCodes = json_decode(decrypt($user->two_factor_backup_codes), true);
            
            if (in_array($code, $backupCodes)) {
                // Remove used backup code
                $backupCodes = array_diff($backupCodes, [$code]);
                $user->update([
                    'two_factor_backup_codes' => encrypt(json_encode(array_values($backupCodes)))
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to verify backup code: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid()), 0, 8));
        }
        
        return $codes;
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(User $user): array
    {
        $backupCodes = $this->generateBackupCodes();
        
        $user->update([
            'two_factor_backup_codes' => encrypt(json_encode($backupCodes))
        ]);

        return $backupCodes;
    }

    /**
     * Get user's backup codes
     */
    public function getBackupCodes(User $user): array
    {
        if (!$user->two_factor_backup_codes) {
            return [];
        }

        try {
            return json_decode(decrypt($user->two_factor_backup_codes), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if user needs 2FA verification
     */
    public function needs2FA(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_confirmed_at;
    }

    /**
     * Clean up expired codes
     */
    public function cleanupExpiredCodes(): int
    {
        return TwoFactorCode::expired()->delete();
    }
}