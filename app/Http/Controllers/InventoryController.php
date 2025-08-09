<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Services\InventoryService;
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
        $filters = $request->only([
            'search', 'category_id', 'location_id', 'low_stock', 'expiring', 'expiring_days',
            'gold_purity_min', 'gold_purity_max', 'gold_purity_range'
        ]);

        $items = $this->inventoryService->searchItems($filters);

        return response()->json([
            'success' => true,
            'data' => $items->load(['category', 'location']),
        ]);
    }

    /**
     * Store a new inventory item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'sku' => 'nullable|string|unique:inventory_items,sku',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gold_purity' => 'nullable|numeric|min:0|max:24',
            'weight' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|unique:inventory_items,serial_number',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date|after:today',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

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
}