<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check session timeout
        if ($this->isSessionExpired($request)) {
            $this->handleSessionTimeout($request);
            
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please log in again.',
                'error_code' => 'SESSION_EXPIRED'
            ], 401);
        }

        // Update last activity timestamp
        $this->updateLastActivity($request);

        // Regenerate session ID periodically for security
        $this->regenerateSessionId($request);

        return $next($request);
    }

    /**
     * Check if session is expired
     */
    protected function isSessionExpired(Request $request): bool
    {
        if (!Auth::check() || !$request->hasSession()) {
            return false; // Not authenticated or no session, no session to expire
        }

        $lastActivity = $request->session()->get('last_activity');
        $sessionLifetime = config('session.lifetime') * 60; // Convert minutes to seconds
        
        if (!$lastActivity) {
            // First request, set activity time and don't expire
            $request->session()->put('last_activity', time());
            return false;
        }

        return (time() - $lastActivity) > $sessionLifetime;
    }

    /**
     * Handle session timeout
     */
    protected function handleSessionTimeout(Request $request): void
    {
        Log::info('Session timeout', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Update last activity timestamp
     */
    protected function updateLastActivity(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->put('last_activity', time());
        }
    }

    /**
     * Regenerate session ID periodically
     */
    protected function regenerateSessionId(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }
        
        $lastRegeneration = $request->session()->get('last_regeneration', 0);
        
        // Regenerate every 30 minutes
        if ((time() - $lastRegeneration) > 1800) {
            $request->session()->regenerate();
            $request->session()->put('last_regeneration', time());
        }
    }
}