<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Services\InventoryService;
use App\Http\Requests\StoreInventoryItemRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get inventory items with filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'location_id' => 'nullable|exists:locations,id',
                'low_stock' => 'nullable|boolean',
                'expiring' => 'nullable|boolean',
                'expiring_days' => 'nullable|integer|min:1|max:365',
                'gold_purity_min' => 'nullable|numeric|min:0|max:24',
                'gold_purity_max' => 'nullable|numeric|min:0|max:24',
                'gold_purity_range' => 'nullable|string|max:50',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $filters = $request->only([
                'search', 'category_id', 'location_id', 'low_stock', 'expiring', 'expiring_days',
                'gold_purity_min', 'gold_purity_max', 'gold_purity_range'
            ]);

            $query = $this->inventoryService->searchItems($filters);
            
            // Apply pagination
            $perPage = $request->get('per_page', 50);
            $items = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $items,
                'meta' => [
                    'filters_applied' => array_filter($filters),
                    'total_items' => $items->total(),
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve inventory items', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory items',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new inventory item.
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get category for name and SKU generation
        $category = Category::find($validated['category_id']);

        // Use category name as default if name is not provided
        if (empty($validated['name'])) {
            $validated['name'] = $category->name;
        }

        // Use category Persian name as default if Persian name is not provided
        if (empty($validated['name_persian']) && $category->name_persian) {
            $validated['name_persian'] = $category->name_persian;
        }

        // Generate SKU if not provided
        if (empty($validated['sku'])) {
            $validated['sku'] = $this->inventoryService->generateSKU($category);
        }

        $item = $this->inventoryService->createItem($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => $item->load(['category', 'location']),
        ], 201);
    }

    /**
     * Get a specific inventory item.
     */
    public function show(InventoryItem $inventory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $inventory->load(['category', 'location', 'movements.user', 'movements.fromLocation', 'movements.toLocation']),
        ]);
    }

    /**
     * Update an inventory item.
     */
    public function update(Request $request, InventoryItem $inventory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'sku' => ['sometimes', 'required', 'string', Rule::unique('inventory_items')->ignore($inventory->id)],
            'category_id' => 'sometimes|required|exists:categories,id',
            'location_id' => 'sometimes|required|exists:locations,id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gold_purity' => 'nullable|numeric|min:0|max:24',
            'weight' => 'nullable|numeric|min:0',
            'serial_number' => ['nullable', 'string', Rule::unique('inventory_items')->ignore($inventory->id)],
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $updatedItem = $this->inventoryService->updateItem($inventory, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => $updatedItem->load(['category', 'location']),
        ]);
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(InventoryItem $inventory): JsonResponse
    {
        // Check if item has movements
        if ($inventory->movements()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item with existing movements',
            ], 422);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully',
        ]);
    }

    /**
     * Transfer item between locations.
     */
    public function transfer(Request $request, InventoryItem $inventory): JsonResponse
    {
        $validated = $request->validate([
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);

        try {
            $movement = $this->inventoryService->transferItem(
                $inventory,
                $validated['from_location_id'],
                $validated['to_location_id'],
                $validated['quantity'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Item transferred successfully',
                'data' => $movement->load(['fromLocation', 'toLocation']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get movement history for an item.
     */
    public function movements(InventoryItem $inventory): JsonResponse
    {
        $movements = $this->inventoryService->getMovementHistory($inventory);

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    /**
     * Get low stock items.
     */
    public function lowStock(): JsonResponse
    {
        $items = $this->inventoryService->getLowStockItems();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get expiring items.
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $items = $this->inventoryService->getExpiringItems($days);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get expired items.
     */
    public function expired(): JsonResponse
    {
        $items = $this->inventoryService->getExpiredItems();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get inventory summary by location.
     */
    public function summaryByLocation(): JsonResponse
    {
        $summary = $this->inventoryService->getInventorySummaryByLocation();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get inventory summary by category.
     */
    public function summaryByCategory(): JsonResponse
    {
        $summary = $this->inventoryService->getInventorySummaryByCategory();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get gold purity options and ranges.
     */
    public function goldPurityOptions(): JsonResponse
    {
        $goldPurityService = app(\App\Services\GoldPurityService::class);
        
        return response()->json([
            'success' => true,
            'data' => [
                'standard_purities' => $goldPurityService->getStandardPurities(),
                'purity_ranges' => $goldPurityService->getPurityRanges(),
            ],
        ]);
    }

    /**
     * Get categories for inventory form dropdown.
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'name_persian' => $category->name_persian,
                        'children' => $category->children->map(function ($child) {
                            return [
                                'id' => $child->id,
                                'name' => $child->name,
                                'name_persian' => $child->name_persian,
                            ];
                        })
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to load categories for inventory form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories'
            ], 500);
        }
    }
    
    /**
     * Get locations for inventory form dropdown.
     */
    public function getLocations(): JsonResponse
    {
        try {
            $locations = Location::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'name_persian' => $location->name_persian ?? $location->name,
                        'code' => $location->code ?? null,
                        'description' => $location->description ?? null,
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to load locations for inventory form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locations'
            ], 500);
        }
    }
}