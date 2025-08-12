<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with consistent JSON responses.
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Handle custom inventory and pricing exceptions
        if ($e instanceof InsufficientInventoryException || 
            $e instanceof PricingException || 
            $e instanceof InventoryException) {
            return $e->render();
        }

        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
                'details' => [
                    'type' => 'validation_error',
                    'code' => 422,
                    'timestamp' => now()->toISOString()
                ]
            ], 422);
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'error' => 'resource_not_found',
                'message' => 'The requested resource was not found.',
                'details' => [
                    'type' => 'not_found_error',
                    'code' => 404,
                    'timestamp' => now()->toISOString(),
                    'model' => class_basename($e->getModel())
                ]
            ], 404);
        }

        // Handle 404 exceptions
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'error' => 'endpoint_not_found',
                'message' => 'The requested endpoint was not found.',
                'details' => [
                    'type' => 'not_found_error',
                    'code' => 404,
                    'timestamp' => now()->toISOString(),
                    'path' => $request->getPathInfo()
                ]
            ], 404);
        }

        // Handle HTTP exceptions
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'error' => 'http_error',
                'message' => $e->getMessage() ?: 'An HTTP error occurred.',
                'details' => [
                    'type' => 'http_error',
                    'code' => $e->getStatusCode(),
                    'timestamp' => now()->toISOString()
                ]
            ], $e->getStatusCode());
        }

        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'error' => 'unauthenticated',
                'message' => 'Authentication required.',
                'details' => [
                    'type' => 'authentication_error',
                    'code' => 401,
                    'timestamp' => now()->toISOString()
                ]
            ], 401);
        }

        // Handle database connection errors
        if ($e instanceof \PDOException || str_contains($e->getMessage(), 'database')) {
            \Log::error('Database error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'database_error',
                'message' => 'A database error occurred. Please try again later.',
                'details' => [
                    'type' => 'database_error',
                    'code' => 500,
                    'timestamp' => now()->toISOString()
                ]
            ], 500);
        }

        // Handle network/connectivity errors
        if (str_contains($e->getMessage(), 'network') || 
            str_contains($e->getMessage(), 'connection') ||
            str_contains($e->getMessage(), 'timeout')) {
            
            \Log::error('Network error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'network_error',
                'message' => 'A network error occurred. Please check your connection and try again.',
                'details' => [
                    'type' => 'network_error',
                    'code' => 503,
                    'timestamp' => now()->toISOString()
                ]
            ], 503);
        }

        // Log unexpected errors
        \Log::error('Unexpected error: ' . $e->getMessage(), [
            'exception' => $e,
            'request' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);

        // Handle all other exceptions
        return response()->json([
            'success' => false,
            'error' => 'internal_server_error',
            'message' => app()->environment('production') 
                ? 'An unexpected error occurred. Please try again later.'
                : $e->getMessage(),
            'details' => [
                'type' => 'server_error',
                'code' => 500,
                'timestamp' => now()->toISOString(),
                'file' => app()->environment('production') ? null : $e->getFile(),
                'line' => app()->environment('production') ? null : $e->getLine()
            ]
        ], 500);
    }

    /**
     * Handle unauthenticated requests.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required',
                'details' => []
            ]
        ], 401);
    }
}