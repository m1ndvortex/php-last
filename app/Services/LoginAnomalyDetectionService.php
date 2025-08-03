<?php

namespace App\Services;

use App\Models\LoginAnomaly;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoginAnomalyDetectionService
{
    private AuditLogService $auditLogService;
    private SessionManagementService $sessionService;

    public function __construct(
        AuditLogService $auditLogService,
        SessionManagementService $sessionService
    ) {
        $this->auditLogService = $auditLogService;
        $this->sessionService = $sessionService;
    }

    /**
     * Analyze login attempt for anomalies
     */
    public function analyzeLoginAttempt(User $user, Request $request, bool $successful = true): array
    {
        $anomalies = [];
        $riskScore = 0;

        // Check for suspicious IP
        $ipAnomaly = $this->checkSuspiciousIP($user, $request);
        if ($ipAnomaly) {
            $anomalies[] = $ipAnomaly;
            $riskScore += $ipAnomaly['risk_score'];
        }

        // Check for new device
        $deviceAnomaly = $this->checkNewDevice($user, $request);
        if ($deviceAnomaly) {
            $anomalies[] = $deviceAnomaly;
            $riskScore += $deviceAnomaly['risk_score'];
        }

        // Check for rapid attempts
        $rapidAnomaly = $this->checkRapidAttempts($user, $request);
        if ($rapidAnomaly) {
            $anomalies[] = $rapidAnomaly;
            $riskScore += $rapidAnomaly['risk_score'];
        }

        // Check for geographic anomaly
        $geoAnomaly = $this->checkGeographicAnomaly($user, $request);
        if ($geoAnomaly) {
            $anomalies[] = $geoAnomaly;
            $riskScore += $geoAnomaly['risk_score'];
        }

        // Check for time-based anomaly
        $timeAnomaly = $this->checkTimeAnomaly($user, $request);
        if ($timeAnomaly) {
            $anomalies[] = $timeAnomaly;
            $riskScore += $timeAnomaly['risk_score'];
        }

        // Check for brute force patterns
        if (!$successful) {
            $bruteForceAnomaly = $this->checkBruteForce($user, $request);
            if ($bruteForceAnomaly) {
                $anomalies[] = $bruteForceAnomaly;
                $riskScore += $bruteForceAnomaly['risk_score'];
            }
        }

        // Store anomalies in database
        foreach ($anomalies as $anomaly) {
            $this->recordAnomaly($user, $anomaly, $request);
        }

        return [
            'has_anomalies' => !empty($anomalies),
            'anomalies' => $anomalies,
            'risk_score' => $riskScore,
            'risk_level' => $this->calculateRiskLevel($riskScore),
            'requires_additional_verification' => $riskScore >= 30
        ];
    }

    /**
     * Check for suspicious IP addresses
     */
    private function checkSuspiciousIP(User $user, Request $request): ?array
    {
        $currentIp = $request->ip();
        
        // Check if IP has been used before
        $hasUsedIp = UserSession::forUser($user->id)
            ->where('ip_address', $currentIp)
            ->exists();

        if ($hasUsedIp) {
            return null; // IP is known
        }

        // Check if IP is in suspicious IP list (would be maintained separately)
        $isSuspicious = $this->isIPSuspicious($currentIp);
        
        if ($isSuspicious || $this->isIPFromSuspiciousCountry($currentIp)) {
            return [
                'type' => 'suspicious_ip',
                'severity' => $isSuspicious ? 'high' : 'medium',
                'risk_score' => $isSuspicious ? 25 : 15,
                'data' => [
                    'ip_address' => $currentIp,
                    'is_known_malicious' => $isSuspicious,
                    'first_time_use' => true
                ]
            ];
        }

        // New IP but not suspicious
        return [
            'type' => 'suspicious_ip',
            'severity' => 'low',
            'risk_score' => 5,
            'data' => [
                'ip_address' => $currentIp,
                'is_known_malicious' => false,
                'first_time_use' => true
            ]
        ];
    }

    /**
     * Check for new device/browser
     */
    private function checkNewDevice(User $user, Request $request): ?array
    {
        $currentUserAgent = $request->userAgent();
        
        $hasUsedDevice = UserSession::forUser($user->id)
            ->where('user_agent', $currentUserAgent)
            ->exists();

        if (!$hasUsedDevice) {
            return [
                'type' => 'new_device',
                'severity' => 'medium',
                'risk_score' => 10,
                'data' => [
                    'user_agent' => $currentUserAgent,
                    'device_fingerprint' => $this->generateDeviceFingerprint($request)
                ]
            ];
        }

        return null;
    }

    /**
     * Check for rapid login attempts
     */
    private function checkRapidAttempts(User $user, Request $request): ?array
    {
        $cacheKey = "login_attempts_{$user->id}_{$request->ip()}";
        $attempts = Cache::get($cacheKey, []);
        
        // Add current attempt
        $attempts[] = now()->timestamp;
        
        // Keep only attempts from last 10 minutes
        $attempts = array_filter($attempts, function ($timestamp) {
            return $timestamp > (now()->timestamp - 600);
        });

        Cache::put($cacheKey, $attempts, 600); // 10 minutes

        if (count($attempts) > 5) {
            return [
                'type' => 'rapid_attempts',
                'severity' => 'high',
                'risk_score' => 20,
                'data' => [
                    'attempts_count' => count($attempts),
                    'time_window' => '10 minutes',
                    'ip_address' => $request->ip()
                ]
            ];
        } elseif (count($attempts) > 3) {
            return [
                'type' => 'rapid_attempts',
                'severity' => 'medium',
                'risk_score' => 10,
                'data' => [
                    'attempts_count' => count($attempts),
                    'time_window' => '10 minutes',
                    'ip_address' => $request->ip()
                ]
            ];
        }

        return null;
    }

    /**
     * Check for geographic anomalies
     */
    private function checkGeographicAnomaly(User $user, Request $request): ?array
    {
        $currentLocation = $this->getLocationFromIP($request->ip());
        
        if (!$currentLocation) {
            return null; // Can't determine location
        }

        // Get user's recent locations
        $recentSessions = UserSession::forUser($user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('location')
            ->pluck('location')
            ->unique();

        if ($recentSessions->isEmpty()) {
            return null; // No location history
        }

        // Check if current location is significantly different
        $isAnomalous = !$recentSessions->contains($currentLocation);
        
        if ($isAnomalous) {
            // Calculate distance (simplified - would use actual geolocation service)
            $distance = $this->calculateDistance($recentSessions->first(), $currentLocation);
            
            if ($distance > 1000) { // More than 1000km
                return [
                    'type' => 'geo_anomaly',
                    'severity' => 'high',
                    'risk_score' => 20,
                    'data' => [
                        'current_location' => $currentLocation,
                        'previous_locations' => $recentSessions->toArray(),
                        'estimated_distance' => $distance
                    ]
                ];
            } elseif ($distance > 100) { // More than 100km
                return [
                    'type' => 'geo_anomaly',
                    'severity' => 'medium',
                    'risk_score' => 10,
                    'data' => [
                        'current_location' => $currentLocation,
                        'previous_locations' => $recentSessions->toArray(),
                        'estimated_distance' => $distance
                    ]
                ];
            }
        }

        return null;
    }

    /**
     * Check for time-based anomalies
     */
    private function checkTimeAnomaly(User $user, Request $request): ?array
    {
        $currentHour = now()->hour;
        
        // Get user's typical login hours from last 30 days
        $typicalHours = UserSession::forUser($user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->map(function ($session) {
                return $session->created_at->hour;
            })
            ->countBy()
            ->sortDesc();

        if ($typicalHours->isEmpty()) {
            return null; // No history
        }

        // Check if current hour is unusual
        $totalLogins = $typicalHours->sum();
        $currentHourLogins = $typicalHours->get($currentHour, 0);
        $percentage = ($currentHourLogins / $totalLogins) * 100;

        if ($percentage < 5 && $totalLogins > 10) { // Less than 5% of logins at this hour
            return [
                'type' => 'time_anomaly',
                'severity' => 'low',
                'risk_score' => 5,
                'data' => [
                    'current_hour' => $currentHour,
                    'typical_hours' => $typicalHours->keys()->take(3)->toArray(),
                    'percentage_at_this_hour' => round($percentage, 2)
                ]
            ];
        }

        return null;
    }

    /**
     * Check for brute force patterns
     */
    private function checkBruteForce(User $user, Request $request): ?array
    {
        $cacheKey = "failed_attempts_{$user->id}";
        $failedAttempts = Cache::get($cacheKey, 0);
        
        Cache::put($cacheKey, $failedAttempts + 1, 3600); // 1 hour

        if ($failedAttempts >= 10) {
            return [
                'type' => 'brute_force',
                'severity' => 'critical',
                'risk_score' => 40,
                'data' => [
                    'failed_attempts' => $failedAttempts + 1,
                    'time_window' => '1 hour',
                    'ip_address' => $request->ip()
                ]
            ];
        } elseif ($failedAttempts >= 5) {
            return [
                'type' => 'brute_force',
                'severity' => 'high',
                'risk_score' => 25,
                'data' => [
                    'failed_attempts' => $failedAttempts + 1,
                    'time_window' => '1 hour',
                    'ip_address' => $request->ip()
                ]
            ];
        }

        return null;
    }

    /**
     * Record anomaly in database
     */
    private function recordAnomaly(User $user, array $anomaly, Request $request): LoginAnomaly
    {
        $loginAnomaly = LoginAnomaly::create([
            'user_id' => $user->id,
            'type' => $anomaly['type'],
            'severity' => $anomaly['severity'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'location' => $this->getLocationFromIP($request->ip()),
            'detection_data' => $anomaly['data']
        ]);

        // Log security event
        $this->auditLogService->logSecurityEvent(
            'login_anomaly_detected',
            "Login anomaly detected: {$anomaly['type']} ({$anomaly['severity']})",
            $user,
            $anomaly['severity'] === 'critical' ? 'critical' : 'warning',
            [
                'anomaly_id' => $loginAnomaly->id,
                'anomaly_type' => $anomaly['type'],
                'risk_score' => $anomaly['risk_score']
            ]
        );

        return $loginAnomaly;
    }

    /**
     * Get unresolved anomalies for user
     */
    public function getUnresolvedAnomalies(User $user): array
    {
        return LoginAnomaly::forUser($user->id)
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($anomaly) {
                return [
                    'id' => $anomaly->id,
                    'type' => $anomaly->type_display,
                    'severity' => $anomaly->severity,
                    'risk_score' => $anomaly->risk_score,
                    'created_at' => $anomaly->created_at,
                    'ip_address' => $anomaly->ip_address,
                    'location' => $anomaly->location,
                    'data' => $anomaly->detection_data
                ];
            })
            ->toArray();
    }

    /**
     * Get anomaly statistics
     */
    public function getAnomalyStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $anomalies = LoginAnomaly::where('created_at', '>=', $startDate);

        return [
            'total_anomalies' => $anomalies->count(),
            'by_severity' => $anomalies->get()->countBy('severity')->toArray(),
            'by_type' => $anomalies->get()->countBy('type')->toArray(),
            'resolved_count' => $anomalies->resolved()->count(),
            'false_positive_count' => $anomalies->falsePositives()->count(),
            'high_risk_count' => $anomalies->where('severity', 'high')->orWhere('severity', 'critical')->count()
        ];
    }

    /**
     * Helper methods
     */
    private function isIPSuspicious(string $ip): bool
    {
        // In a real implementation, this would check against threat intelligence feeds
        // For now, return false
        return false;
    }

    private function isIPFromSuspiciousCountry(string $ip): bool
    {
        // Check if IP is from a country with high fraud rates
        // This would use a geolocation service
        return false;
    }

    private function getLocationFromIP(string $ip): ?string
    {
        // Placeholder - would use actual geolocation service
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local Development';
        }
        return null;
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        return md5($request->userAgent() . $request->header('Accept-Language', ''));
    }

    private function calculateDistance(string $location1, string $location2): int
    {
        // Simplified distance calculation - would use actual geolocation
        return $location1 === $location2 ? 0 : 500; // Placeholder
    }

    private function calculateRiskLevel(int $riskScore): string
    {
        if ($riskScore >= 40) {
            return 'critical';
        } elseif ($riskScore >= 25) {
            return 'high';
        } elseif ($riskScore >= 10) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}