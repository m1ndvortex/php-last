<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CostCenterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CostCenter::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_persian', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $costCenters = $query->withCount('transactions')
            ->orderBy('code')
            ->get()
            ->map(function ($costCenter) {
                return [
                    'id' => $costCenter->id,
                    'code' => $costCenter->code,
                    'name' => $costCenter->localized_name,
                    'description' => $costCenter->description,
                    'is_active' => $costCenter->is_active,
                    'transactions_count' => $costCenter->transactions_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $costCenters,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:cost_centers',
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $costCenter = CostCenter::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $costCenter,
            'message' => 'Cost center created successfully',
        ], 201);
    }

    public function show(CostCenter $costCenter): JsonResponse
    {
        $costCenter->loadCount('transactions', 'assets');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->localized_name,
                'description' => $costCenter->description,
                'is_active' => $costCenter->is_active,
                'transactions_count' => $costCenter->transactions_count,
                'assets_count' => $costCenter->assets_count,
                'metadata' => $costCenter->metadata,
            ],
        ]);
    }

    public function update(Request $request, CostCenter $costCenter): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:cost_centers,code,' . $costCenter->id,
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $costCenter->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $costCenter,
            'message' => 'Cost center updated successfully',
        ]);
    }

    public function destroy(CostCenter $costCenter): JsonResponse
    {
        if ($costCenter->transactions()->exists() || $costCenter->assets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete cost center with associated transactions or assets',
            ], 422);
        }

        $costCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cost center deleted successfully',
        ]);
    }
}