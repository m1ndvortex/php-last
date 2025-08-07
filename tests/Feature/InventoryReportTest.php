<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $mainCategory;
    private Category $subcategory;
    private InventoryItem $item1;
    private InventoryItem $item2;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        // Create location
        $this->location = Location::factory()->create([
            'name' => 'Main Store',
        ]);
        
        // Create categories
        $this->mainCategory = Category::factory()->create([
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
            'parent_id' => null,
            'default_gold_purity' => 18.0,
        ]);

        $this->subcategory = Category::factory()->create([
            'name' => 'Wedding Rings',
            'name_persian' => 'حلقه ازدواج',
            'parent_id' => $this->mainCategory->id,
            'default_gold_purity' => 18.0,
        ]);

        // Create inventory items
        $this->item1 = InventoryItem::create([
            'name' => 'Test Ring 1',
            'sku' => 'TR001',
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->subcategory->id,
            'location_id' => $this->location->id,
            'gold_purity' => 18.0,
            'quantity' => 10,
            'unit_price' => 1000,
            'cost_price' => 800,
            'weight' => 5.5,
        ]);

        $this->item2 = InventoryItem::create([
            'name' => 'Test Ring 2',
            'sku' => 'TR002',
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->subcategory->id,
            'location_id' => $this->location->id,
            'gold_purity' => 21.0,
            'quantity' => 5,
            'unit_price' => 1500,
            'cost_price' => 1200,
            'weight' => 7.2,
        ]);

        // Create sales data
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now(),
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $this->item1->id,
            'quantity' => 2,
            'unit_price' => 1000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $this->item2->id,
            'quantity' => 1,
            'unit_price' => 1500,
        ]);
    }

    public function test_category_hierarchy_report()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-hierarchy');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'filters',
                    'summary' => [
                        'total_main_categories',
                        'total_items',
                        'total_quantity',
                        'total_value',
                        'total_cost',
                    ],
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'total_items',
                            'total_quantity',
                            'total_value',
                            'total_cost',
                            'subcategories' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'items',
                                    'total_items',
                                    'total_quantity',
                                    'total_value',
                                    'total_cost',
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['summary']['total_main_categories']);
        $this->assertEquals(2, $data['summary']['total_items']);
    }

    public function test_category_hierarchy_report_with_filters()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-hierarchy?' . http_build_query([
                'main_category_id' => $this->mainCategory->id,
            ]));

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals($this->mainCategory->id, $data['filters']['main_category_id']);
        $this->assertEquals(1, $data['summary']['total_main_categories']);
    }

    public function test_category_sales_performance_report()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-sales-performance');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'filters',
                    'summary' => [
                        'total_revenue',
                        'total_cost',
                        'total_profit',
                        'total_quantity_sold',
                        'total_orders',
                        'average_margin_percentage',
                    ],
                    'performance_data' => [
                        '*' => [
                            'main_category',
                            'subcategory',
                            'total_quantity_sold',
                            'total_revenue',
                            'total_cost',
                            'total_profit',
                            'margin_percentage',
                            'total_orders',
                            'unique_items_sold',
                            'average_unit_price',
                            'average_order_value',
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3500, $data['summary']['total_revenue']); // At least 2*1000 + 1*1500
        $this->assertGreaterThan(0, $data['summary']['total_cost']);
    }

    public function test_category_sales_performance_with_group_by_main()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-sales-performance?' . http_build_query([
                'group_by' => 'main_category',
            ]));

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals('main_category', $data['filters']['group_by']);
        $this->assertArrayHasKey('category', $data['performance_data'][0]);
        $this->assertEquals('main', $data['performance_data'][0]['category']['type']);
    }

    public function test_category_stock_levels_report()
    {
        // Create low stock item
        $lowStockItem = InventoryItem::create([
            'name' => 'Low Stock Item',
            'sku' => 'LSI001',
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->subcategory->id,
            'location_id' => $this->location->id,
            'quantity' => 2, // Below default threshold of 10
            'unit_price' => 500,
            'cost_price' => 400,
        ]);

        // Create out of stock item
        $outOfStockItem = InventoryItem::create([
            'name' => 'Out of Stock Item',
            'sku' => 'OSI001',
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->subcategory->id,
            'location_id' => $this->location->id,
            'quantity' => 0,
            'unit_price' => 300,
            'cost_price' => 200,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-stock-levels');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'filters',
                    'summary' => [
                        'total_items',
                        'out_of_stock_count',
                        'low_stock_count',
                        'adequate_stock_count',
                        'total_value',
                    ],
                    'stock_levels' => [
                        'out_of_stock',
                        'low_stock',
                        'adequate_stock',
                    ],
                    'category_stats',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['summary']['out_of_stock_count']);
        $this->assertGreaterThanOrEqual(1, $data['summary']['low_stock_count']);
        $this->assertGreaterThanOrEqual(0, $data['summary']['adequate_stock_count']); // May vary based on existing data
    }

    public function test_gold_purity_analysis_report()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/gold-purity-analysis');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'filters',
                    'summary' => [
                        'total_purity_groups',
                        'total_inventory_items',
                        'total_inventory_value',
                        'total_weight',
                        'total_sales_revenue',
                        'total_sales_profit',
                    ],
                    'purity_groups' => [
                        '*' => [
                            'purity',
                            'karat',
                            'display_name',
                            'inventory_count',
                            'total_quantity',
                            'total_weight',
                            'total_inventory_value',
                            'quantity_sold',
                            'sales_revenue',
                            'sales_cost',
                            'sales_profit',
                            'margin_percentage',
                            'turnover_rate',
                            'items',
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, $data['summary']['total_purity_groups']); // 18K and 21K
        $this->assertGreaterThanOrEqual(2, $data['summary']['total_inventory_items']);
        $this->assertGreaterThanOrEqual(3500, $data['summary']['total_sales_revenue']);
    }

    public function test_gold_purity_analysis_with_purity_range()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/gold-purity-analysis?' . http_build_query([
                'purity_range_min' => 18,
                'purity_range_max' => 20,
            ]));

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals(18, $data['filters']['purity_range_min']);
        $this->assertEquals(20, $data['filters']['purity_range_max']);
        $this->assertGreaterThanOrEqual(1, $data['summary']['total_purity_groups']); // At least 18K
    }

    public function test_inventory_analytics_report()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/inventory-analytics');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'filters',
                    'trends',
                    'category_performance',
                    'gold_purity_trends',
                    'stock_alerts' => [
                        'out_of_stock',
                        'low_stock',
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals('month', $data['period']);
        $this->assertIsArray($data['trends']);
        $this->assertIsArray($data['category_performance']);
        $this->assertIsArray($data['gold_purity_trends']);
    }

    public function test_inventory_analytics_with_period()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/inventory-analytics?' . http_build_query([
                'period' => 'week',
            ]));

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals('week', $data['period']);
        $this->assertCount(7, $data['trends']); // 7 weeks
    }

    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/inventory-reports/category-hierarchy');
        $response->assertUnauthorized();
    }

    public function test_invalid_category_filter()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-hierarchy?' . http_build_query([
                'main_category_id' => 99999, // Non-existent category
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['main_category_id']);
    }

    public function test_invalid_date_range()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/category-sales-performance?' . http_build_query([
                'start_date' => '2023-12-31',
                'end_date' => '2023-01-01', // End date before start date
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_invalid_purity_range()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory-reports/gold-purity-analysis?' . http_build_query([
                'purity_range_min' => 20,
                'purity_range_max' => 15, // Max less than min
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['purity_range_max']);
    }
}