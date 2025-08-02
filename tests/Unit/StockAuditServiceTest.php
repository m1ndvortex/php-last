<?php

namespace Tests\Unit;

use App\Models\StockAudit;
use App\Models\StockAuditItem;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\StockAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockAuditService $stockAuditService;
    protected User $user;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockAuditService = new StockAuditService();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->actingAs($this->user);
    }

    public function test_create_audit_generates_audit_number()
    {
        $audit = $this->stockAuditService->createAudit([
            'location_id' => $this->location->id,
            'auditor_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(StockAudit::class, $audit);
        $this->assertNotNull($audit->audit_number);
        $this->assertEquals(StockAudit::STATUS_PENDING, $audit->status);
        $this->assertEquals($this->location->id, $audit->location_id);
        $this->assertEquals($this->user->id, $audit->auditor_id);
    }

    public function test_create_audit_creates_audit_items()
    {
        // Create some inventory items
        InventoryItem::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $audit = $this->stockAuditService->createAudit([
            'location_id' => $this->location->id,
            'auditor_id' => $this->user->id,
        ]);

        $this->assertCount(3, $audit->auditItems);
        
        foreach ($audit->auditItems as $auditItem) {
            $this->assertEquals($audit->id, $auditItem->stock_audit_id);
            $this->assertFalse($auditItem->is_counted);
            $this->assertEquals(0, $auditItem->variance);
        }
    }

    public function test_start_audit_updates_status()
    {
        $audit = StockAudit::factory()->create([
            'status' => StockAudit::STATUS_PENDING,
            'auditor_id' => $this->user->id,
        ]);

        $startedAudit = $this->stockAuditService->startAudit($audit);

        $this->assertEquals(StockAudit::STATUS_IN_PROGRESS, $startedAudit->status);
        $this->assertNotNull($startedAudit->started_at);
    }

    public function test_update_audit_item_count_calculates_variance()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'cost_price' => 20.00,
        ]);

        $audit = StockAudit::factory()->create([
            'auditor_id' => $this->user->id,
        ]);

        $auditItem = StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'inventory_item_id' => $item->id,
            'system_quantity' => 10,
            'unit_cost' => 20.00,
        ]);

        $updatedAuditItem = $this->stockAuditService->updateAuditItemCount(
            $auditItem,
            8,
            'Found 2 items missing'
        );

        $this->assertEquals(8, $updatedAuditItem->physical_quantity);
        $this->assertEquals(-2, $updatedAuditItem->variance);
        $this->assertEquals(-40.00, $updatedAuditItem->variance_value);
        $this->assertTrue($updatedAuditItem->is_counted);
        $this->assertEquals('Found 2 items missing', $updatedAuditItem->notes);
        $this->assertNotNull($updatedAuditItem->counted_at);
    }

    public function test_complete_audit_creates_adjustment_movements()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'cost_price' => 20.00,
        ]);

        $audit = StockAudit::factory()->create([
            'status' => StockAudit::STATUS_IN_PROGRESS,
            'auditor_id' => $this->user->id,
        ]);

        $auditItem = StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'inventory_item_id' => $item->id,
            'system_quantity' => 10,
            'physical_quantity' => 8,
            'variance' => -2,
            'unit_cost' => 20.00,
            'variance_value' => -40.00,
            'is_counted' => true,
        ]);

        $completedAudit = $this->stockAuditService->completeAudit($audit);

        $this->assertEquals(StockAudit::STATUS_COMPLETED, $completedAudit->status);
        $this->assertNotNull($completedAudit->completed_at);

        // Check adjustment movement was created
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_ADJUSTMENT,
            'quantity' => 2,
            'reference_type' => 'stock_audit',
            'reference_id' => $audit->id,
        ]);

        // Check inventory item quantity was updated
        $this->assertEquals(8, $item->fresh()->quantity);
    }

    public function test_cancel_audit_updates_status()
    {
        $audit = StockAudit::factory()->create([
            'status' => StockAudit::STATUS_PENDING,
            'auditor_id' => $this->user->id,
        ]);

        $cancelledAudit = $this->stockAuditService->cancelAudit($audit);

        $this->assertEquals(StockAudit::STATUS_CANCELLED, $cancelledAudit->status);
    }

    public function test_get_audit_summary()
    {
        $audit = StockAudit::factory()->create([
            'auditor_id' => $this->user->id,
        ]);

        // Create audit items with different scenarios
        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'variance' => 0,
            'variance_value' => 0,
            'is_counted' => true,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'variance' => 2,
            'variance_value' => 40.00,
            'is_counted' => true,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'variance' => -1,
            'variance_value' => -20.00,
            'is_counted' => false,
        ]);

        $summary = $this->stockAuditService->getAuditSummary($audit);

        $this->assertEquals(3, $summary['total_items']);
        $this->assertEquals(2, $summary['counted_items']);
        $this->assertEquals(2, $summary['items_with_variance']);
        $this->assertEquals(20.00, $summary['total_variance_value']);
        $this->assertEquals(40.00, $summary['positive_variance_value']);
        $this->assertEquals(-20.00, $summary['negative_variance_value']);
    }

    public function test_get_variance_report()
    {
        $audit = StockAudit::factory()->create([
            'auditor_id' => $this->user->id,
        ]);

        // Create audit items with variances
        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'variance' => 2,
            'variance_value' => 40.00,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'variance' => 0,
            'variance_value' => 0,
        ]);

        $varianceReport = $this->stockAuditService->getVarianceReport($audit);

        $this->assertCount(1, $varianceReport);
        $this->assertEquals(2, $varianceReport->first()->variance);
    }

    public function test_get_uncounted_items()
    {
        $audit = StockAudit::factory()->create([
            'auditor_id' => $this->user->id,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'is_counted' => true,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'is_counted' => false,
        ]);

        $uncountedItems = $this->stockAuditService->getUncountedItems($audit);

        $this->assertCount(1, $uncountedItems);
        $this->assertFalse($uncountedItems->first()->is_counted);
    }

    public function test_bulk_update_audit_items()
    {
        $item1 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $audit = StockAudit::factory()->create([
            'auditor_id' => $this->user->id,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'inventory_item_id' => $item1->id,
            'system_quantity' => 10,
            'unit_cost' => 20.00,
        ]);

        StockAuditItem::factory()->create([
            'stock_audit_id' => $audit->id,
            'inventory_item_id' => $item2->id,
            'system_quantity' => 5,
            'unit_cost' => 30.00,
        ]);

        $updates = [
            [
                'inventory_item_id' => $item1->id,
                'physical_quantity' => 8,
                'notes' => 'Missing 2 items',
            ],
            [
                'inventory_item_id' => $item2->id,
                'physical_quantity' => 6,
                'notes' => 'Found 1 extra',
            ],
        ];

        $this->stockAuditService->bulkUpdateAuditItems($audit, $updates);

        $auditItem1 = $audit->auditItems()->where('inventory_item_id', $item1->id)->first();
        $auditItem2 = $audit->auditItems()->where('inventory_item_id', $item2->id)->first();

        $this->assertEquals(8, $auditItem1->physical_quantity);
        $this->assertEquals(-2, $auditItem1->variance);
        $this->assertTrue($auditItem1->is_counted);

        $this->assertEquals(6, $auditItem2->physical_quantity);
        $this->assertEquals(1, $auditItem2->variance);
        $this->assertTrue($auditItem2->is_counted);
    }
}
