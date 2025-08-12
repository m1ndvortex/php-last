<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SimpleCSRFProtection
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSRF for GET, HEAD, OPTIONS requests
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip CSRF if disabled in environment
        if (env('CSRF_DISABLED', false)) {
            return $next($request);
        }

        // Simple CSRF token validation
        if (!$this->validateCSRFToken($request)) {
            Log::warning('CSRF token validation failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
                'error_code' => 'CSRF_TOKEN_MISMATCH'
            ], 419);
        }

        return $next($request);
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRFToken(Request $request): bool
    {
        $token = $request->header('X-CSRF-TOKEN') 
                ?? $request->input('_token')
                ?? $request->header('X-XSRF-TOKEN');

        if (!$token) {
            return false;
        }

        // Check if session exists
        if (!$request->hasSession()) {
            return false;
        }

        // Get session token
        $sessionToken = $request->session()->token();
        
        if (!$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
}