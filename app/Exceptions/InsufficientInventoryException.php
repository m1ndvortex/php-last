<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientInventoryException extends Exception
{
    protected $unavailableItems;
    
    public function __construct(string $message = 'Insufficient inventory', array $unavailableItems = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->unavailableItems = $unavailableItems;
    }
    
    public function getUnavailableItems(): array
    {
        return $this->unavailableItems;
    }
    
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'insufficient_inventory',
            'message' => $this->getMessage(),
            'unavailable_items' => $this->unavailableItems,
            'details' => [
                'type' => 'inventory_error',
                'code' => $this->getCode(),
                'timestamp' => now()->toISOString()
            ]
        ], $this->getCode());
    }
}