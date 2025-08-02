<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AssetController extends Controller
{
    private AssetService $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Asset::with('costCenter');

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_persian', 'like', "%{$search}%")
                  ->orWhere('asset_number', 'like', "%{$search}%");
            });
        }

        $assets = $query->orderBy('asset_number')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assets->items(),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:equipment,furniture,vehicle,building,software,other',
            'purchase_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'depreciation_method' => 'required|in:straight_line,declining_balance,units_of_production',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $asset = $this->assetService->createAsset($request->all());

        return response()->json([
            'success' => true,
            'data' => $asset->load('costCenter'),
            'message' => 'Asset created successfully',
        ], 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load('costCenter');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'asset_number' => $asset->asset_number,
                'name' => $asset->localized_name,
                'description' => $asset->description,
                'category' => $asset->category,
                'purchase_cost' => $asset->purchase_cost,
                'purchase_date' => $asset->purchase_date->toDateString(),
                'salvage_value' => $asset->salvage_value,
                'useful_life_years' => $asset->useful_life_years,
                'depreciation_method' => $asset->depreciation_method,
                'accumulated_depreciation' => $asset->accumulated_depreciation,
                'current_value' => $asset->current_value,
                'status' => $asset->status,
                'disposal_date' => $asset->disposal_date?->toDateString(),
                'disposal_value' => $asset->disposal_value,
                'cost_center' => $asset->costCenter ? [
                    'id' => $asset->costCenter->id,
                    'name' => $asset->costCenter->localized_name,
                ] : null,
                'metadata' => $asset->metadata,
            ],
        ]);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:equipment,furniture,vehicle,building,software,other',
            'purchase_cost' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'depreciation_method' => 'required|in:straight_line,declining_balance,units_of_production',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $asset = $this->assetService->updateAsset($asset, $request->all());

        return response()->json([
            'success' => true,
            'data' => $asset->load('costCenter'),
            'message' => 'Asset updated successfully',
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        if ($asset->status !== 'disposed') {
            return response()->json([
                'success' => false,
                'message' => 'Only disposed assets can be deleted',
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully',
        ]);
    }

    public function dispose(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'disposal_date' => 'required|date',
            'disposal_value' => 'required|numeric|min:0',
            'disposal_method' => 'string|in:sold,scrapped,donated,traded',
        ]);

        if ($asset->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active assets can be disposed',
            ], 422);
        }

        $transaction = $this->assetService->disposeAsset(
            $asset,
            Carbon::parse($request->disposal_date),
            $request->disposal_value,
            $request->disposal_method ?? 'sold'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'asset' => $asset->fresh()->load('costCenter'),
                'transaction' => $transaction->load('entries.account'),
            ],
            'message' => 'Asset disposed successfully',
        ]);
    }

    public function depreciation(Asset $asset, Request $request): JsonResponse
    {
        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $depreciation = $this->assetService->calculateDepreciation($asset, $asOfDate);

        return response()->json([
            'success' => true,
            'data' => [
                'asset_id' => $asset->id,
                'asset_name' => $asset->localized_name,
                'depreciation_amount' => $depreciation,
                'as_of_date' => $asOfDate?->toDateString() ?? now()->toDateString(),
            ],
        ]);
    }

    public function depreciationSchedule(Asset $asset): JsonResponse
    {
        $schedule = $this->assetService->getDepreciationSchedule($asset);

        return response()->json([
            'success' => true,
            'data' => [
                'asset' => [
                    'id' => $asset->id,
                    'name' => $asset->localized_name,
                    'asset_number' => $asset->asset_number,
                ],
                'schedule' => $schedule,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $register = $this->assetService->getAssetRegister(
            $request->category,
            $request->status
        );

        return response()->json([
            'success' => true,
            'data' => $register,
        ]);
    }

    public function processDepreciation(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $results = $this->assetService->processDepreciation($asOfDate);

        return response()->json([
            'success' => true,
            'data' => [
                'processed_assets' => $results->count(),
                'total_depreciation' => $results->sum('depreciation_expense'),
                'details' => $results,
            ],
            'message' => 'Depreciation processed successfully',
        ]);
    }
}