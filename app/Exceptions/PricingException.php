<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PricingException extends Exception
{
    protected $pricingData;
    
    public function __construct(string $message = 'Pricing calculation error', array $pricingData = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->pricingData = $pricingData;
    }
    
    public function getPricingData(): array
    {
        return $this->pricingData;
    }
    
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'pricing_error',
            'message' => $this->getMessage(),
            'pricing_data' => $this->pricingData,
            'details' => [
                'type' => 'pricing_error',
                'code' => $this->getCode(),
                'timestamp' => now()->toISOString()
            ]
        ], $this->getCode());
    }
}