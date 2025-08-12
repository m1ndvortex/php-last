<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Basic rate limiting
        if ($this->isExceedingRateLimit($request)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        // Basic input sanitization
        $this->sanitizeInput($request);

        return $next($request);
    }

    /**
     * Check if request is exceeding rate limit
     */
    protected function isExceedingRateLimit(Request $request): bool
    {
        $key = 'rate_limit:' . $request->ip();
        $attempts = Cache::get($key, 0);
        
        // Allow 100 requests per minute
        if ($attempts >= 100) {
            return true;
        }
        
        Cache::put($key, $attempts + 1, 60);
        return false;
    }

    /**
     * Basic input sanitization
     */
    protected function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue($value);
        }
        
        $request->replace($sanitized);
    }

    /**
     * Sanitize a single value
     */
    protected function sanitizeValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }
        
        if (!is_string($value)) {
            return $value;
        }
        
        // Remove script tags completely
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove HTML tags
        $value = strip_tags($value);
        
        // Remove javascript: protocol
        $value = preg_replace('/javascript:/i', '', $value);
        
        // Remove event handlers
        $value = preg_replace('/on\w+\s*=/i', '', $value);
        
        return $value;
    }
}