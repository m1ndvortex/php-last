<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorHandling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Log successful API calls for monitoring
            if ($request->is('api/*') && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::info('API Success', [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status' => $response->getStatusCode(),
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString()
                ]);
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            // Log the error with context
            Log::error('API Request Failed', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->sanitizeRequestData($request->all()),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);
            
            // Return consistent error response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
            
            // Re-throw for non-API requests to be handled by the global handler
            throw $e;
        }
    }
    
    /**
     * Handle API exceptions with consistent JSON responses
     */
    protected function handleApiException(\Throwable $e, Request $request): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // Determine error type and message
        $errorType = $this->getErrorType($e);
        $message = $this->getErrorMessage($e);
        
        return response()->json([
            'success' => false,
            'error' => $errorType,
            'message' => $message,
            'details' => [
                'type' => class_basename($e),
                'code' => $statusCode,
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID', uniqid()),
                'path' => $request->getPathInfo(),
                'method' => $request->method()
            ]
        ], $statusCode);
    }
    
    /**
     * Determine error type based on exception
     */
    protected function getErrorType(\Throwable $e): string
    {
        $className = class_basename($e);
        
        $errorTypeMap = [
            'ValidationException' => 'validation_failed',
            'ModelNotFoundException' => 'resource_not_found',
            'NotFoundHttpException' => 'endpoint_not_found',
            'AuthenticationException' => 'unauthenticated',
            'AuthorizationException' => 'unauthorized',
            'ThrottleRequestsException' => 'rate_limit_exceeded',
            'InsufficientInventoryException' => 'insufficient_inventory',
            'PricingException' => 'pricing_error',
            'InventoryException' => 'inventory_error',
            'PDOException' => 'database_error',
            'QueryException' => 'database_error',
            'ConnectionException' => 'database_error'
        ];
        
        return $errorTypeMap[$className] ?? 'internal_server_error';
    }
    
    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(\Throwable $e): string
    {
        // For production, return generic messages for security
        if (app()->environment('production')) {
            $genericMessages = [
                'ValidationException' => 'The provided data is invalid.',
                'ModelNotFoundException' => 'The requested resource was not found.',
                'NotFoundHttpException' => 'The requested endpoint was not found.',
                'AuthenticationException' => 'Authentication is required.',
                'AuthorizationException' => 'You are not authorized to perform this action.',
                'ThrottleRequestsException' => 'Too many requests. Please try again later.',
                'PDOException' => 'A database error occurred. Please try again later.',
                'QueryException' => 'A database error occurred. Please try again later.'
            ];
            
            $className = class_basename($e);
            return $genericMessages[$className] ?? 'An unexpected error occurred. Please try again later.';
        }
        
        // For development, return actual error messages
        return $e->getMessage();
    }
    
    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
            'social_security_number'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }
}