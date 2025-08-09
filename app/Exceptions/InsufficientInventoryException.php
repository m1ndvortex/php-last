<?php

namespace App\Exceptions;

use Exception;

class InsufficientInventoryException extends Exception
{
    protected $unavailableItems;

    public function __construct($message = 'Insufficient inventory', $unavailableItems = [], $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->unavailableItems = $unavailableItems;
    }

    public function getUnavailableItems()
    {
        return $this->unavailableItems;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'unavailable_items' => $this->unavailableItems,
            'error_type' => 'insufficient_inventory'
        ], 422);
    }
}