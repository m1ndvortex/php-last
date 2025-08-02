<?php

namespace Tests\Unit;

use App\Models\BillOfMaterial;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\BOMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BOMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BOMService $bomService;
    protected User $user;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->bomService = new BOMService();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->actingAs($this->user);
    }

    public function test_create_bom_entry()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $componentItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $bomData = [
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $componentItem->id,
            'quantity_required' => 2.5,
            'wastage_percentage' => 10.0,
            'notes' => 'Test BOM entry',
        ];

        $bomEntry = $this->bomService->createBOMEntry($bomData);

        $this->assertInstanceOf(BillOfMaterial::class, $bomEntry);
        $this->assertEquals($finishedItem->id, $bomEntry->finished_item_id);
        $this->assertEquals($componentItem->id, $bomEntry->component_item_id);
        $this->assertEquals(2.5, $bomEntry->quantity_required);
        $this->assertEquals(10.0, $bomEntry->wastage_percentage);
    }

    public function test_get_bom_for_item()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component1 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component2 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component1->id,
            'is_active' => true,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component2->id,
            'is_active' => true,
        ]);

        $bomEntries = $this->bomService->getBOMForItem($finishedItem);

        $this->assertCount(2, $bomEntries);
        $this->assertTrue($bomEntries->contains('component_item_id', $component1->id));
        $this->assertTrue($bomEntries->contains('component_item_id', $component2->id));
    }

    public function test_calculate_production_cost()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component1 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'cost_price' => 10.00,
            'quantity' => 100,
        ]);

        $component2 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'cost_price' => 20.00,
            'quantity' => 50,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component1->id,
            'quantity_required' => 2,
            'wastage_percentage' => 10,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component2->id,
            'quantity_required' => 1,
            'wastage_percentage' => 5,
        ]);

        $costAnalysis = $this->bomService->calculateProductionCost($finishedItem, 1);

        // Component 1: 2 * 1.1 (10% wastage) * 10.00 = 22.00
        // Component 2: 1 * 1.05 (5% wastage) * 20.00 = 21.00
        // Total: 43.00
        $this->assertEquals(43.00, $costAnalysis['total_cost']);
        $this->assertCount(2, $costAnalysis['component_costs']);
        $this->assertTrue($costAnalysis['can_produce']);
        $this->assertEmpty($costAnalysis['missing_components']);
    }

    public function test_calculate_production_cost_with_insufficient_components()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'cost_price' => 10.00,
            'quantity' => 1, // Insufficient quantity
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component->id,
            'quantity_required' => 5,
            'wastage_percentage' => 0,
        ]);

        $costAnalysis = $this->bomService->calculateProductionCost($finishedItem, 1);

        $this->assertFalse($costAnalysis['can_produce']);
        $this->assertCount(1, $costAnalysis['missing_components']);
        $this->assertEquals(4, $costAnalysis['missing_components'][0]['shortage']);
    }

    public function test_can_produce()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component->id,
            'quantity_required' => 2,
            'wastage_percentage' => 10,
        ]);

        // Can produce 4 items (10 / 2.2 = 4.54, rounded down)
        $this->assertTrue($this->bomService->canProduce($finishedItem, 4));
        $this->assertFalse($this->bomService->canProduce($finishedItem, 5));
    }

    public function test_produce_item()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 0,
            'cost_price' => 0,
        ]);

        $component = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'cost_price' => 20.00,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component->id,
            'quantity_required' => 2,
            'wastage_percentage' => 10,
        ]);

        $productionResult = $this->bomService->produceItem($finishedItem, 1);

        $this->assertEquals(1, $productionResult['quantity_produced']);
        $this->assertEquals(44.00, $productionResult['total_cost']); // 2.2 * 20.00
        $this->assertEquals(44.00, $productionResult['unit_cost']);

        // Check finished item was updated
        $this->assertEquals(1, $finishedItem->fresh()->quantity);
        $this->assertEquals(44.00, $finishedItem->fresh()->cost_price);

        // Check component was consumed
        $this->assertEquals(7.8, $component->fresh()->quantity); // 10 - 2.2

        // Check movements were created
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $component->id,
            'type' => InventoryMovement::TYPE_OUT,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $component->id,
            'type' => InventoryMovement::TYPE_WASTAGE,
            'quantity' => 0.2,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $finishedItem->id,
            'type' => InventoryMovement::TYPE_PRODUCTION,
            'quantity' => 1,
        ]);
    }

    public function test_produce_item_with_insufficient_components_throws_exception()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $component = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 1,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $component->id,
            'quantity_required' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient components to produce the requested quantity');

        $this->bomService->produceItem($finishedItem, 1);
    }

    public function test_get_production_requirements()
    {
        $item1 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $sharedComponent = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $item1->id,
            'component_item_id' => $sharedComponent->id,
            'quantity_required' => 3,
            'wastage_percentage' => 0,
        ]);

        BillOfMaterial::factory()->create([
            'finished_item_id' => $item2->id,
            'component_item_id' => $sharedComponent->id,
            'quantity_required' => 5,
            'wastage_percentage' => 0,
        ]);

        $items = [
            ['item_id' => $item1->id, 'quantity' => 2],
            ['item_id' => $item2->id, 'quantity' => 1],
        ];

        $requirements = $this->bomService->getProductionRequirements($items);

        $this->assertCount(2, $requirements['item_requirements']);
        $this->assertCount(1, $requirements['component_summary']);
        
        // Total requirement: (3 * 2) + (5 * 1) = 11, but only 10 available
        $this->assertCount(1, $requirements['shortages']);
        $this->assertEquals(1, $requirements['shortages'][0]['shortage']);
        $this->assertFalse($requirements['can_produce_all']);
    }

    public function test_validate_bom_prevents_circular_references()
    {
        $item1 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        // Create a BOM where item1 uses item2
        BillOfMaterial::factory()->create([
            'finished_item_id' => $item1->id,
            'component_item_id' => $item2->id,
        ]);

        // Try to create a BOM where item2 uses item1 (circular reference)
        $isValid = $this->bomService->validateBOM($item2->id, $item1->id);
        
        $this->assertFalse($isValid);

        // Valid BOM should pass
        $item3 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $isValid = $this->bomService->validateBOM($item2->id, $item3->id);
        $this->assertTrue($isValid);
    }

    public function test_get_bom_tree()
    {
        $finishedItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $subAssembly = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $rawMaterial = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        // Finished item uses sub-assembly
        BillOfMaterial::factory()->create([
            'finished_item_id' => $finishedItem->id,
            'component_item_id' => $subAssembly->id,
        ]);

        // Sub-assembly uses raw material
        BillOfMaterial::factory()->create([
            'finished_item_id' => $subAssembly->id,
            'component_item_id' => $rawMaterial->id,
        ]);

        $bomTree = $this->bomService->getBOMTree($finishedItem);

        $this->assertCount(1, $bomTree);
        $this->assertEquals(0, $bomTree[0]['depth']);
        $this->assertEquals($subAssembly->id, $bomTree[0]['component']->id);
        
        // Check sub-level
        $this->assertCount(1, $bomTree[0]['children']);
        $this->assertEquals(1, $bomTree[0]['children'][0]['depth']);
        $this->assertEquals($rawMaterial->id, $bomTree[0]['children'][0]['component']->id);
    }
}
