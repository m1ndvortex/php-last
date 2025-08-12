<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InventoryException extends Exception
{
    protected $inventoryData;
    
    public function __construct(string $message = 'Inventory operation failed', array $inventoryData = [], int $code = 500)
    {
        parent::__construct($message, $code);
        $this->inventoryData = $inventoryData;
    }
    
    public function getInventoryData(): array
    {
        return $this->inventoryData;
    }
    
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'inventory_error',
            'message' => $this->getMessage(),
            'inventory_data' => $this->inventoryData,
            'details' => [
                'type' => 'inventory_error',
                'code' => $this->getCode(),
                'timestamp' => now()->toISOString()
            ]
        ], $this->getCode());
    }
}