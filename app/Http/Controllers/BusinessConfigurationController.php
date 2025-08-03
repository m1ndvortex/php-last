<?php

namespace App\Http\Controllers;

use App\Services\BusinessConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessConfigurationController extends Controller
{
    private BusinessConfigurationService $configService;

    public function __construct(BusinessConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Get business information
     */
    public function getBusinessInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->configService->getBusinessInfo()
        ]);
    }

    /**
     * Update business information
     */
    public function updateBusinessInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateBusinessInfo($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Business information updated successfully'
        ]);
    }

    /**
     * Upload business logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $this->configService->uploadLogo($request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'data' => ['logo_path' => $path]
        ]);
    }

    /**
     * Get tax configuration
     */
    public function getTaxConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->configService->getTaxConfig()
        ]);
    }

    /**
     * Update tax configuration
     */
    public function updateTaxConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'tax_inclusive' => 'required|boolean',
            'tax_number_required' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateTaxConfig($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tax configuration updated successfully'
        ]);
    }

    /**
     * Get profit configuration
     */
    public function getProfitConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->configService->getProfitConfig()
        ]);
    }

    /**
     * Update profit configuration
     */
    public function updateProfitConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'default_profit_margin' => 'required|numeric|min:0|max:100',
            'gold_profit_margin' => 'required|numeric|min:0|max:100',
            'jewelry_profit_margin' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->configService->updateProfitConfig($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profit configuration updated successfully'
        ]);
    }

    /**
     * Get configuration by category
     */
    public function getByCategory(string $category): JsonResponse
    {
        $data = $this->configService->getByCategory($category);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): JsonResponse
    {
        $this->configService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Configuration cache cleared successfully'
        ]);
    }
}