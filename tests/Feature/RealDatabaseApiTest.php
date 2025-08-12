<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealDatabaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create categories
        $category = Category::factory()->create(['name' => 'Gold Jewelry']);
        
        // Create locations
        $location = Location::factory()->create(['name' => 'Main Store']);
        
        // Create customers
        Customer::factory()->count(5)->create();
        
        // Create inventory items
        InventoryItem::factory()->count(10)->create([
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);
        
        // Create accounts manually since there's no factory
        Account::create([
            'code' => '1000',
            'name' => 'Cash',
            'name_persian' => 'نقد',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'currency' => 'USD',
            'opening_balance' => 0,
            'is_active' => true
        ]);
        
        Account::create([
            'code' => '4000',
            'name' => 'Sales Revenue',
            'name_persian' => 'درآمد فروش',
            'type' => 'revenue',
            'subtype' => 'operating_revenue',
            'currency' => 'USD',
            'opening_balance' => 0,
            'is_active' => true
        ]);
    }

    public function test_dashboard_kpis_returns_real_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'gold_sold',
                    'total_profits',
                    'average_price',
                    'returns',
                    'gross_margin',
                    'net_margin',
                    'total_sales',
                    'active_customers',
                    'inventory_value',
                    'pending_invoices'
                ],
                'meta' => [
                    'generated_at',
                    'cache_duration'
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_customers_index_returns_real_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'customer_type'
                        ]
                    ],
                    'current_page',
                    'total'
                ],
                'meta'
            ])
            ->assertJson(['success' => true]);

        $this->assertGreaterThan(0, $response->json('data.total'));
    }

    public function test_inventory_index_returns_real_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'category',
                        'location'
                    ]
                ],
                'meta'
            ])
            ->assertJson(['success' => true]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_accounts_index_returns_real_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/accounting/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'type',
                        'current_balance'
                    ]
                ],
                'meta'
            ])
            ->assertJson(['success' => true]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_categories_hierarchy_returns_real_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/categories/hierarchy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'parent_id'
                    ]
                ]
            ])
            ->assertJson(['success' => true]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_inventory_categories_endpoint_with_caching()
    {
        // First request should be cache miss
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/inventory/categories');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name'
                    ]
                ]
            ]);

        // Second request should be cache hit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/inventory/categories');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');
    }

    public function test_api_error_handling_with_invalid_data()
    {
        // Test validation error handling
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers', [
                'name' => '', // Invalid: required field
                'email' => 'invalid-email' // Invalid: not a valid email
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_api_authentication_required()
    {
        // Test that authentication is required
        $response = $this->getJson('/api/dashboard/kpis');

        $response->assertStatus(401);
    }

    public function test_sales_chart_data_with_different_periods()
    {
        $periods = ['week', 'month', 'year'];

        foreach ($periods as $period) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->getJson("/api/dashboard/sales-chart?period={$period}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'date',
                            'label',
                            'sales'
                        ]
                    ],
                    'meta' => [
                        'period',
                        'data_points'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'meta' => ['period' => $period]
                ]);
        }
    }

    public function test_inventory_filtering_with_real_data()
    {
        $category = Category::first();
        $location = Location::first();

        // Test category filtering
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/inventory?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Test location filtering
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/inventory?location_id={$location->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}