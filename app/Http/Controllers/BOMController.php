<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\InventoryItem;
use App\Services\BOMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BOMController extends Controller
{
    protected BOMService $bomService;

    public function __construct(BOMService $bomService)
    {
        $this->bomService = $bomService;
    }

    /**
     * Get BOM entries for a finished item.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'finished_item_id' => 'required|exists:inventory_items,id',
        ]);

        $finishedItem = InventoryItem::find($validated['finished_item_id']);
        $bomEntries = $this->bomService->getBOMForItem($finishedItem);

        return response()->json([
            'success' => true,
            'data' => $bomEntries,
        ]);
    }

    /**
     * Create a new BOM entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'finished_item_id' => 'required|exists:inventory_items,id',
            'component_item_id' => 'required|exists:inventory_items,id|different:finished_item_id',
            'quantity_required' => 'required|numeric|min:0.001',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        // Validate no circular references
        if (!$this->bomService->validateBOM($validated['finished_item_id'], $validated['component_item_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Circular reference detected in BOM structure',
            ], 422);
        }

        // Check if BOM entry already exists
        $existingBOM = BillOfMaterial::where('finished_item_id', $validated['finished_item_id'])
            ->where('component_item_id', $validated['component_item_id'])
            ->first();

        if ($existingBOM) {
            return response()->json([
                'success' => false,
                'message' => 'BOM entry already exists for this component',
            ], 422);
        }

        $bomEntry = $this->bomService->createBOMEntry($validated);

        return response()->json([
            'success' => true,
            'message' => 'BOM entry created successfully',
            'data' => $bomEntry->load(['finishedItem', 'componentItem']),
        ], 201);
    }

    /**
     * Get a specific BOM entry.
     */
    public function show(BillOfMaterial $bom): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $bom->load(['finishedItem', 'componentItem']),
        ]);
    }

    /**
     * Update a BOM entry.
     */
    public function update(Request $request, BillOfMaterial $bom): JsonResponse
    {
        $validated = $request->validate([
            'quantity_required' => 'sometimes|required|numeric|min:0.001',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $updatedBOM = $this->bomService->updateBOMEntry($bom, $validated);

        return response()->json([
            'success' => true,
            'message' => 'BOM entry updated successfully',
            'data' => $updatedBOM->load(['finishedItem', 'componentItem']),
        ]);
    }

    /**
     * Delete a BOM entry.
     */
    public function destroy(BillOfMaterial $bom): JsonResponse
    {
        $this->bomService->deleteBOMEntry($bom);

        return response()->json([
            'success' => true,
            'message' => 'BOM entry deleted successfully',
        ]);
    }

    /**
     * Calculate production cost for an item.
     */
    public function productionCost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'nullable|numeric|min:0.001',
        ]);

        $item = InventoryItem::find($validated['item_id']);
        $quantity = $validated['quantity'] ?? 1;

        $costAnalysis = $this->bomService->calculateProductionCost($item, $quantity);

        return response()->json([
            'success' => true,
            'data' => $costAnalysis,
        ]);
    }

    /**
     * Check if an item can be produced.
     */
    public function canProduce(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'nullable|numeric|min:0.001',
        ]);

        $item = InventoryItem::find($validated['item_id']);
        $quantity = $validated['quantity'] ?? 1;

        $canProduce = $this->bomService->canProduce($item, $quantity);

        return response()->json([
            'success' => true,
            'data' => [
                'can_produce' => $canProduce,
                'item' => $item,
                'quantity' => $quantity,
            ],
        ]);
    }

    /**
     * Produce an item using BOM.
     */
    public function produce(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.001',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $item = InventoryItem::find($validated['item_id']);

        try {
            $productionResult = $this->bomService->produceItem(
                $item,
                $validated['quantity'],
                ['location_id' => $validated['location_id'] ?? null]
            );

            return response()->json([
                'success' => true,
                'message' => 'Item produced successfully',
                'data' => $productionResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get production requirements for multiple items.
     */
    public function productionRequirements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $requirements = $this->bomService->getProductionRequirements($validated['items']);

        return response()->json([
            'success' => true,
            'data' => $requirements,
        ]);
    }

    /**
     * Get BOM tree for an item.
     */
    public function bomTree(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'max_depth' => 'nullable|integer|min:1|max:10',
        ]);

        $item = InventoryItem::find($validated['item_id']);
        $maxDepth = $validated['max_depth'] ?? 5;

        $bomTree = $this->bomService->getBOMTree($item, 0, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $item,
                'bom_tree' => $bomTree,
            ],
        ]);
    }

    /**
     * Get items that use a specific component.
     */
    public function usageReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'component_id' => 'required|exists:inventory_items,id',
        ]);

        $component = InventoryItem::find($validated['component_id']);
        $usage = $this->bomService->getItemsUsingComponent($component);

        return response()->json([
            'success' => true,
            'data' => [
                'component' => $component,
                'used_in' => $usage,
            ],
        ]);
    }
}
