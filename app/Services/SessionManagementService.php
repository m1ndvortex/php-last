<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;

class SessionManagementService
{
    private Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Create new session record
     */
    public function createSession(User $user, Request $request): UserSession
    {
        $this->agent->setUserAgent($request->userAgent());

        return UserSession::create([
            'user_id' => $user->id,
            'session_id' => Session::getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->getDeviceType(),
            'browser' => $this->agent->browser(),
            'platform' => $this->agent->platform(),
            'location' => $this->getLocation($request->ip()),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120))
        ]);
    }

    /**
     * Update session activity
     */
    public function updateSessionActivity(string $sessionId): void
    {
        UserSession::where('session_id', $sessionId)
            ->active()
            ->first()
            ?->updateActivity();
    }

    /**
     * Terminate session
     */
    public function terminateSession(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();
        
        if ($session) {
            $session->terminate();
            return true;
        }

        return false;
    }

    /**
     * Terminate all user sessions except current
     */
    public function terminateOtherSessions(User $user, string $currentSessionId): int
    {
        return UserSession::forUser($user->id)
            ->where('session_id', '!=', $currentSessionId)
            ->active()
            ->update(['is_active' => false]);
    }

    /**
     * Get user's active sessions
     */
    public function getUserActiveSessions(User $user): array
    {
        return UserSession::forUser($user->id)
            ->active()
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'ip_address' => $session->ip_address,
                    'device_info' => $session->device_info,
                    'location' => $session->formatted_location,
                    'last_activity' => $session->last_activity,
                    'duration' => $session->duration,
                    'is_current' => $session->session_id === Session::getId()
                ];
            })
            ->toArray();
    }

    /**
     * Check if session should timeout
     */
    public function shouldTimeout(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            return true;
        }

        return $session->isExpired();
    }

    /**
     * Get session timeout warning time (in minutes before expiry)
     */
    public function getTimeoutWarningTime(): int
    {
        return config('session.timeout_warning', 5); // 5 minutes before expiry
    }

    /**
     * Check if session needs timeout warning
     */
    public function needsTimeoutWarning(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            return false;
        }

        $warningTime = now()->addMinutes($this->getTimeoutWarningTime());
        return $session->expires_at <= $warningTime;
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        return UserSession::expired()
            ->update(['is_active' => false]);
    }

    /**
     * Get session statistics for user
     */
    public function getSessionStats(User $user): array
    {
        $sessions = UserSession::forUser($user->id);

        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->active()->count(),
            'unique_ips' => $sessions->distinct('ip_address')->count(),
            'unique_devices' => $sessions->distinct('user_agent')->count(),
            'last_login' => $user->last_login_at,
            'current_session_duration' => $this->getCurrentSessionDuration()
        ];
    }

    /**
     * Detect suspicious login activity
     */
    public function detectSuspiciousActivity(User $user, Request $request): array
    {
        $suspiciousFactors = [];
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        // Check for new IP address
        $hasUsedIp = UserSession::forUser($user->id)
            ->where('ip_address', $currentIp)
            ->exists();

        if (!$hasUsedIp) {
            $suspiciousFactors[] = 'new_ip_address';
        }

        // Check for new device/browser
        $hasUsedDevice = UserSession::forUser($user->id)
            ->where('user_agent', $currentUserAgent)
            ->exists();

        if (!$hasUsedDevice) {
            $suspiciousFactors[] = 'new_device';
        }

        // Check for multiple rapid login attempts
        $recentSessions = UserSession::forUser($user->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentSessions > 3) {
            $suspiciousFactors[] = 'rapid_login_attempts';
        }

        // Check for geographically distant logins
        $lastSession = UserSession::forUser($user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastSession && $lastSession->location && $this->getLocation($currentIp)) {
            // This would require a geolocation service to calculate distance
            // For now, just check if locations are different
            if ($lastSession->location !== $this->getLocation($currentIp)) {
                $suspiciousFactors[] = 'different_location';
            }
        }

        return [
            'is_suspicious' => !empty($suspiciousFactors),
            'factors' => $suspiciousFactors,
            'risk_level' => $this->calculateRiskLevel($suspiciousFactors)
        ];
    }

    /**
     * Get device type
     */
    private function getDeviceType(): string
    {
        if ($this->agent->isMobile()) {
            return 'mobile';
        } elseif ($this->agent->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Get location from IP (placeholder - would use actual geolocation service)
     */
    private function getLocation(string $ip): ?string
    {
        // In a real implementation, you would use a service like MaxMind GeoIP
        // For now, return null or a placeholder
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local Development';
        }

        return null; // Would be replaced with actual geolocation
    }

    /**
     * Get current session duration
     */
    private function getCurrentSessionDuration(): int
    {
        $session = UserSession::where('session_id', Session::getId())->first();
        return $session ? $session->duration : 0;
    }

    /**
     * Calculate risk level based on suspicious factors
     */
    private function calculateRiskLevel(array $factors): string
    {
        $count = count($factors);

        if ($count === 0) {
            return 'low';
        } elseif ($count <= 2) {
            return 'medium';
        } else {
            return 'high';
        }
    }
}