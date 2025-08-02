<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BOMService
{
    /**
     * Create a BOM entry.
     */
    public function createBOMEntry(array $data): BillOfMaterial
    {
        return BillOfMaterial::create($data);
    }

    /**
     * Update a BOM entry.
     */
    public function updateBOMEntry(BillOfMaterial $bom, array $data): BillOfMaterial
    {
        $bom->update($data);
        return $bom;
    }

    /**
     * Delete a BOM entry.
     */
    public function deleteBOMEntry(BillOfMaterial $bom): bool
    {
        return $bom->delete();
    }

    /**
     * Get BOM for a finished item.
     */
    public function getBOMForItem(InventoryItem $item): Collection
    {
        return $item->bomAsFinished()
            ->active()
            ->with(['componentItem.category', 'componentItem.location'])
            ->get();
    }

    /**
     * Get items that use a component.
     */
    public function getItemsUsingComponent(InventoryItem $component): Collection
    {
        return BillOfMaterial::usingComponent($component->id)
            ->active()
            ->with(['finishedItem.category', 'finishedItem.location'])
            ->get();
    }

    /**
     * Calculate total cost for producing an item.
     */
    public function calculateProductionCost(InventoryItem $item, float $quantity = 1): array
    {
        $bomEntries = $this->getBOMForItem($item);
        $totalCost = 0;
        $componentCosts = [];
        $missingComponents = [];

        foreach ($bomEntries as $bomEntry) {
            $component = $bomEntry->componentItem;
            $requiredQuantity = $bomEntry->total_quantity_required * $quantity;
            $componentCost = $requiredQuantity * $component->cost_price;
            
            $componentCosts[] = [
                'component' => $component,
                'required_quantity' => $requiredQuantity,
                'available_quantity' => $component->quantity,
                'unit_cost' => $component->cost_price,
                'total_cost' => $componentCost,
                'wastage_quantity' => $bomEntry->wastage_quantity * $quantity,
                'is_sufficient' => $component->quantity >= $requiredQuantity,
            ];
            
            if ($component->quantity < $requiredQuantity) {
                $missingComponents[] = [
                    'component' => $component,
                    'required' => $requiredQuantity,
                    'available' => $component->quantity,
                    'shortage' => $requiredQuantity - $component->quantity,
                ];
            }
            
            $totalCost += $componentCost;
        }

        return [
            'total_cost' => $totalCost,
            'component_costs' => $componentCosts,
            'missing_components' => $missingComponents,
            'can_produce' => empty($missingComponents),
        ];
    }

    /**
     * Check if an item can be produced.
     */
    public function canProduce(InventoryItem $item, float $quantity = 1): bool
    {
        $bomEntries = $this->getBOMForItem($item);
        
        foreach ($bomEntries as $bomEntry) {
            $component = $bomEntry->componentItem;
            $requiredQuantity = $bomEntry->total_quantity_required * $quantity;
            
            if ($component->quantity < $requiredQuantity) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Produce an item using BOM.
     */
    public function produceItem(InventoryItem $item, float $quantity, array $options = []): array
    {
        return DB::transaction(function () use ($item, $quantity, $options) {
            $bomEntries = $this->getBOMForItem($item);
            
            if ($bomEntries->isEmpty()) {
                throw new \Exception('No BOM found for this item');
            }
            
            // Check if we can produce
            if (!$this->canProduce($item, $quantity)) {
                throw new \Exception('Insufficient components to produce the requested quantity');
            }
            
            $movements = [];
            $totalCost = 0;
            
            // Consume components
            foreach ($bomEntries as $bomEntry) {
                $component = $bomEntry->componentItem;
                $requiredQuantity = $bomEntry->total_quantity_required * $quantity;
                $wastageQuantity = $bomEntry->wastage_quantity * $quantity;
                
                // Create outbound movement for component consumption
                $outMovement = InventoryMovement::create([
                    'inventory_item_id' => $component->id,
                    'from_location_id' => $component->location_id,
                    'type' => InventoryMovement::TYPE_OUT,
                    'quantity' => $bomEntry->quantity_required * $quantity,
                    'unit_cost' => $component->cost_price,
                    'reference_type' => 'production',
                    'reference_id' => $item->id,
                    'notes' => "Production consumption for {$item->name}",
                    'user_id' => auth()->id(),
                    'movement_date' => now(),
                ]);
                
                $movements[] = $outMovement;
                
                // Create wastage movement if applicable
                if ($wastageQuantity > 0) {
                    $wastageMovement = InventoryMovement::create([
                        'inventory_item_id' => $component->id,
                        'from_location_id' => $component->location_id,
                        'type' => InventoryMovement::TYPE_WASTAGE,
                        'quantity' => $wastageQuantity,
                        'unit_cost' => $component->cost_price,
                        'reference_type' => 'production_wastage',
                        'reference_id' => $item->id,
                        'notes' => "Production wastage for {$item->name}",
                        'user_id' => auth()->id(),
                        'movement_date' => now(),
                    ]);
                    
                    $movements[] = $wastageMovement;
                }
                
                // Update component quantity
                $component->decrement('quantity', $requiredQuantity);
                $totalCost += $requiredQuantity * $component->cost_price;
            }
            
            // Create inbound movement for finished product
            $inMovement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'to_location_id' => $options['location_id'] ?? $item->location_id,
                'type' => InventoryMovement::TYPE_PRODUCTION,
                'quantity' => $quantity,
                'unit_cost' => $totalCost / $quantity,
                'reference_type' => 'production',
                'notes' => "Production of {$item->name}",
                'user_id' => auth()->id(),
                'movement_date' => now(),
            ]);
            
            $movements[] = $inMovement;
            
            // Update finished item quantity and cost
            $item->increment('quantity', $quantity);
            $item->update(['cost_price' => $totalCost / $quantity]);
            
            return [
                'item' => $item,
                'quantity_produced' => $quantity,
                'total_cost' => $totalCost,
                'unit_cost' => $totalCost / $quantity,
                'movements' => $movements,
            ];
        });
    }

    /**
     * Get production requirements for multiple items.
     */
    public function getProductionRequirements(array $items): array
    {
        $requirements = [];
        $totalComponentNeeds = [];
        
        foreach ($items as $itemData) {
            $item = InventoryItem::find($itemData['item_id']);
            $quantity = $itemData['quantity'];
            
            if (!$item) {
                continue;
            }
            
            $bomEntries = $this->getBOMForItem($item);
            $itemRequirements = [];
            
            foreach ($bomEntries as $bomEntry) {
                $component = $bomEntry->componentItem;
                $requiredQuantity = $bomEntry->total_quantity_required * $quantity;
                
                $itemRequirements[] = [
                    'component' => $component,
                    'required_quantity' => $requiredQuantity,
                    'available_quantity' => $component->quantity,
                    'unit_cost' => $component->cost_price,
                ];
                
                // Aggregate component needs
                $componentId = $component->id;
                if (!isset($totalComponentNeeds[$componentId])) {
                    $totalComponentNeeds[$componentId] = [
                        'component' => $component,
                        'total_required' => 0,
                        'available' => $component->quantity,
                    ];
                }
                $totalComponentNeeds[$componentId]['total_required'] += $requiredQuantity;
            }
            
            $requirements[] = [
                'item' => $item,
                'quantity' => $quantity,
                'components' => $itemRequirements,
            ];
        }
        
        // Check for shortages
        $shortages = [];
        foreach ($totalComponentNeeds as $componentNeed) {
            if ($componentNeed['total_required'] > $componentNeed['available']) {
                $shortages[] = [
                    'component' => $componentNeed['component'],
                    'required' => $componentNeed['total_required'],
                    'available' => $componentNeed['available'],
                    'shortage' => $componentNeed['total_required'] - $componentNeed['available'],
                ];
            }
        }
        
        return [
            'item_requirements' => $requirements,
            'component_summary' => array_values($totalComponentNeeds),
            'shortages' => $shortages,
            'can_produce_all' => empty($shortages),
        ];
    }

    /**
     * Get BOM tree for an item (recursive).
     */
    public function getBOMTree(InventoryItem $item, int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth >= $maxDepth) {
            return [];
        }
        
        $bomEntries = $this->getBOMForItem($item);
        $tree = [];
        
        foreach ($bomEntries as $bomEntry) {
            $component = $bomEntry->componentItem;
            $subTree = $this->getBOMTree($component, $depth + 1, $maxDepth);
            
            $tree[] = [
                'bom_entry' => $bomEntry,
                'component' => $component,
                'depth' => $depth,
                'children' => $subTree,
            ];
        }
        
        return $tree;
    }

    /**
     * Validate BOM for circular references.
     */
    public function validateBOM(int $finishedItemId, int $componentItemId): bool
    {
        // Check if the component item has the finished item in its BOM
        return !$this->hasCircularReference($componentItemId, $finishedItemId, []);
    }

    /**
     * Check for circular references recursively.
     */
    protected function hasCircularReference(int $itemId, int $targetId, array $visited): bool
    {
        if (in_array($itemId, $visited)) {
            return true;
        }
        
        if ($itemId === $targetId) {
            return true;
        }
        
        $visited[] = $itemId;
        
        $bomEntries = BillOfMaterial::forFinishedItem($itemId)->active()->get();
        
        foreach ($bomEntries as $bomEntry) {
            if ($this->hasCircularReference($bomEntry->component_item_id, $targetId, $visited)) {
                return true;
            }
        }
        
        return false;
    }
}