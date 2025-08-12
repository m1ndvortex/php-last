<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SecurityAuditMiddleware
{
    /**
     * Security events to log
     */
    protected array $securityEvents = [
        'login',
        'logout',
        'password_change',
        'failed_login',
        'account_locked',
        'permission_denied',
        'suspicious_activity'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log security-relevant requests
        $this->logSecurityEvent($request, $response);

        return $response;
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent(Request $request, Response $response): void
    {
        $shouldLog = false;
        $eventType = 'general';

        // Determine if this is a security-relevant event
        if ($this->isAuthenticationRequest($request)) {
            $shouldLog = true;
            $eventType = $response->getStatusCode() === 200 ? 'login_success' : 'login_failed';
        } elseif ($this->isLogoutRequest($request)) {
            $shouldLog = true;
            $eventType = 'logout';
        } elseif ($this->isPasswordChangeRequest($request)) {
            $shouldLog = true;
            $eventType = 'password_change';
        } elseif ($this->isUnauthorizedResponse($response)) {
            $shouldLog = true;
            $eventType = 'unauthorized_access';
        } elseif ($this->isSuspiciousRequest($request)) {
            $shouldLog = true;
            $eventType = 'suspicious_activity';
        }

        if ($shouldLog) {
            $this->writeSecurityLog($eventType, $request, $response);
        }
    }

    /**
     * Check if request is authentication related
     */
    protected function isAuthenticationRequest(Request $request): bool
    {
        return $request->is('api/auth/login') || $request->is('login');
    }

    /**
     * Check if request is logout related
     */
    protected function isLogoutRequest(Request $request): bool
    {
        return $request->is('api/auth/logout') || $request->is('logout');
    }

    /**
     * Check if request is password change related
     */
    protected function isPasswordChangeRequest(Request $request): bool
    {
        return $request->is('api/auth/change-password') || 
               $request->is('api/user/password') ||
               $request->is('password/reset');
    }

    /**
     * Check if response indicates unauthorized access
     */
    protected function isUnauthorizedResponse(Response $response): bool
    {
        return in_array($response->getStatusCode(), [401, 403]);
    }

    /**
     * Check if request appears suspicious
     */
    protected function isSuspiciousRequest(Request $request): bool
    {
        // Check for common attack patterns
        $userAgent = $request->userAgent();
        $suspiciousPatterns = [
            'sqlmap',
            'nikto',
            'nmap',
            'burp',
            'scanner',
            'bot'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Check for suspicious parameters
        $input = $request->all();
        foreach ($input as $value) {
            if (is_string($value) && $this->containsSuspiciousContent($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content contains suspicious patterns
     */
    protected function containsSuspiciousContent(string $content): bool
    {
        $suspiciousPatterns = [
            '<script',
            'javascript:',
            'SELECT.*FROM',
            'UNION.*SELECT',
            'DROP.*TABLE',
            '../',
            '..\\',
            'eval(',
            'exec(',
            'system('
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Write security log entry
     */
    protected function writeSecurityLog(string $eventType, Request $request, Response $response): void
    {
        $sessionId = null;
        try {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        } catch (Throwable $e) {
            // Session not available (e.g., during testing)
            $sessionId = 'test-session';
        }

        $logData = [
            'event_type' => $eventType,
            'timestamp' => now()->toISOString(),
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'session_id' => $sessionId,
        ];

        // Add additional context for specific events
        if ($eventType === 'login_failed') {
            $logData['attempted_email'] = $request->input('email');
        }

        Log::channel('security')->info('Security Event', $logData);
    }
}