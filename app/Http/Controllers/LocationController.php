<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Get all locations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Location::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by location type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Include inventory items count if requested
        if ($request->boolean('include_inventory_count')) {
            $query->withCount('inventoryItems');
        }

        // Include inventory items if requested
        if ($request->boolean('include_inventory')) {
            $query->with('inventoryItems');
        }

        $locations = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    /**
     * Store a new location.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'code' => 'required|string|max:10|unique:locations,code',
            'type' => 'required|string|in:storage,showcase,safe,exhibition',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location,
        ], 201);
    }

    /**
     * Get a specific location.
     */
    public function show(Location $location): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $location->load(['inventoryItems.category']),
        ]);
    }

    /**
     * Update a location.
     */
    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'code' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('locations')->ignore($location->id)],
            'type' => 'sometimes|required|string|in:storage,showcase,safe,exhibition',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $location,
        ]);
    }

    /**
     * Delete a location.
     */
    public function destroy(Location $location): JsonResponse
    {
        // Check if location has inventory items
        if ($location->inventoryItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with existing inventory items',
            ], 422);
        }

        // Check if location has movements
        $hasMovements = $location->movementsFrom()->count() > 0 || $location->movementsTo()->count() > 0;
        if ($hasMovements) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with existing inventory movements',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully',
        ]);
    }
}
