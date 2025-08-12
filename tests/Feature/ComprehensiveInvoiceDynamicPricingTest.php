<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InvoiceService;
use App\Services\GoldPricingService;
use App\Services\InventoryManagementService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\BusinessConfiguration;
use App\Models\User;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\PricingException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ComprehensiveInvoiceDynamicPricingTest extends TestCase
{
    use RefreshDatabase;

    protected $invoiceService;
    protected $goldPricingService;
    protected $inventoryService;
    protected $user;
    protected $customer;
    protected $category;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoiceService = app(InvoiceService::class);
        $this->goldPricingService = app(GoldPricingService::class);
        $this->inventoryService = app(InventoryManagementService::class);
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->customer = Customer::factory()->create();
        $this->category = Category::factory()->create(['name' => 'Gold Jewelry']);
        $this->location = Location::factory()->create(['name' => 'Main Store']);

        // Set up business configuration
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_labor_percentage'],
            ['value' => '10.0']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_profit_percentage'],
            ['value' => '15.0']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_tax_percentage'],
            ['value' => '9.0']
        );
    }

    /** @test */
    public function it_creates_invoice_with_dynamic_pricing_and_inventory_integration()
    {
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 8.5,
            'gold_purity' => 18.0,
            'unit_price' => null, // No static price
            'cost_price' => null
        ]);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'gold_pricing' => [
                'gold_price_per_gram' => 65.50,
                'labor_percentage' => 12.5,
                'profit_percentage' => 18.0,
                'tax_percentage' => 9.5
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        // Verify invoice creation
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->customer->id, $invoice->customer_id);
        $this->assertEquals(65.50, $invoice->gold_price_per_gram);
        $this->assertEquals(12.5, $invoice->labor_percentage);
        $this->assertEquals(18.0, $invoice->profit_percentage);
        $this->assertEquals(9.5, $invoice->tax_percentage);

        // Verify inventory reduction
        $inventoryItem->refresh();
        $this->assertEquals(8, $inventoryItem->quantity);

        // Verify invoice item with dynamic pricing
        $invoiceItem = $invoice->items->first();
        $this->assertEquals(2, $invoiceItem->quantity);
        $this->assertEquals(8.5, $invoiceItem->weight);
        $this->assertEquals(18.0, $invoiceItem->gold_purity);

        // Verify price calculations
        $this->assertGreaterThan(0, $invoiceItem->base_gold_cost);
        $this->assertGreaterThan(0, $invoiceItem->labor_cost);
        $this->assertGreaterThan(0, $invoiceItem->profit_amount);
        $this->assertGreaterThan(0, $invoiceItem->tax_amount);
        $this->assertGreaterThan(0, $invoiceItem->unit_price);
        $this->assertGreaterThan(0, $invoiceItem->total_price);

        // Manual calculation verification
        $expectedBaseGoldCost = 8.5 * 65.50 * 2; // weight * price * quantity
        $expectedLaborCost = $expectedBaseGoldCost * 0.125; // 12.5%
        $subtotal = $expectedBaseGoldCost + $expectedLaborCost;
        $expectedProfit = $subtotal * 0.18; // 18%
        $subtotalWithProfit = $subtotal + $expectedProfit;
        $expectedTax = $subtotalWithProfit * 0.095; // 9.5%
        $expectedTotalPrice = $subtotalWithProfit + $expectedTax;

        $this->assertEquals(round($expectedBaseGoldCost, 2), $invoiceItem->base_gold_cost);
        $this->assertEquals(round($expectedLaborCost, 2), $invoiceItem->labor_cost);
        $this->assertEquals(round($expectedProfit, 2), $invoiceItem->profit_amount);
        $this->assertEquals(round($expectedTax, 2), $invoiceItem->tax_amount);
        $this->assertEquals(round($expectedTotalPrice, 2), $invoiceItem->total_price);
    }

    /** @test */
    public function it_handles_mixed_pricing_scenarios_in_single_invoice()
    {
        // Item with dynamic pricing
        $dynamicItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 21.0,
            'unit_price' => null,
            'cost_price' => null
        ]);

        // Item with static pricing
        $staticItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 15,
            'weight' => 3.0,
            'gold_purity' => 18.0,
            'unit_price' => 750.00,
            'cost_price' => 600.00
        ]);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 55.00,
                'labor_percentage' => 15.0,
                'profit_percentage' => 20.0,
                'tax_percentage' => 8.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $dynamicItem->id,
                    'quantity' => 1,
                    'name' => $dynamicItem->name
                ],
                [
                    'inventory_item_id' => $staticItem->id,
                    'quantity' => 2,
                    'name' => $staticItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        $this->assertCount(2, $invoice->items);

        // Find items by inventory item id
        $dynamicInvoiceItem = $invoice->items->where('inventory_item_id', $dynamicItem->id)->first();
        $staticInvoiceItem = $invoice->items->where('inventory_item_id', $staticItem->id)->first();

        // Verify dynamic pricing item
        $this->assertGreaterThan(0, $dynamicInvoiceItem->base_gold_cost);
        $this->assertGreaterThan(0, $dynamicInvoiceItem->labor_cost);
        $this->assertGreaterThan(0, $dynamicInvoiceItem->profit_amount);
        $this->assertGreaterThan(0, $dynamicInvoiceItem->tax_amount);

        // Verify static pricing item has reasonable values
        $this->assertGreaterThan(0, $staticInvoiceItem->unit_price);
        $this->assertGreaterThan(0, $staticInvoiceItem->total_price);

        // Verify inventory reductions
        $dynamicItem->refresh();
        $staticItem->refresh();
        $this->assertEquals(9, $dynamicItem->quantity);
        $this->assertEquals(13, $staticItem->quantity);
    }

    /** @test */
    public function it_updates_invoice_with_dynamic_pricing_changes()
    {
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 20,
            'weight' => 6.0,
            'gold_purity' => 22.0,
            'unit_price' => null,
            'cost_price' => null
        ]);

        // Create initial invoice
        $initialData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 50.00,
                'labor_percentage' => 10.0,
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($initialData);
        $originalTotalPrice = $invoice->items->first()->total_price;

        // Update with new pricing
        $updateData = [
            'gold_pricing' => [
                'gold_price_per_gram' => 70.00, // Increased gold price
                'labor_percentage' => 20.0, // Increased labor
                'profit_percentage' => 25.0, // Increased profit
                'tax_percentage' => 12.0 // Increased tax
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 3, // Changed quantity
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $updateData);

        // Verify pricing parameters updated
        $this->assertEquals(70.00, $updatedInvoice->gold_price_per_gram);
        $this->assertEquals(20.0, $updatedInvoice->labor_percentage);
        $this->assertEquals(25.0, $updatedInvoice->profit_percentage);
        $this->assertEquals(12.0, $updatedInvoice->tax_percentage);

        // Verify new price calculation
        $updatedItem = $updatedInvoice->items->first();
        $this->assertEquals(3, $updatedItem->quantity);
        $this->assertNotEquals($originalTotalPrice, $updatedItem->total_price);
        $this->assertGreaterThan($originalTotalPrice, $updatedItem->total_price);

        // Verify inventory adjustment
        $inventoryItem->refresh();
        $this->assertEquals(17, $inventoryItem->quantity); // 20 - 3 = 17
    }

    /** @test */
    public function it_handles_pricing_validation_errors_during_invoice_creation()
    {
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 18.0
        ]);

        $invalidPricingData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => -10.00, // Invalid negative price
                'labor_percentage' => -5.0, // Invalid negative percentage
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 1,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->invoiceService->createInvoice($invalidPricingData);

        // Verify inventory was not affected
        $inventoryItem->refresh();
        $this->assertEquals(10, $inventoryItem->quantity);
    }

    /** @test */
    public function it_calculates_invoice_totals_correctly_with_dynamic_pricing()
    {
        $items = collect();
        for ($i = 1; $i <= 3; $i++) {
            $items->push(InventoryItem::factory()->create([
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'quantity' => 20,
                'weight' => $i * 2.5, // 2.5, 5.0, 7.5
                'gold_purity' => 18.0,
                'unit_price' => null,
                'cost_price' => null
            ]));
        }

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 60.00,
                'labor_percentage' => 12.0,
                'profit_percentage' => 18.0,
                'tax_percentage' => 10.0
            ],
            'items' => $items->map(function ($item, $index) {
                return [
                    'inventory_item_id' => $item->id,
                    'quantity' => $index + 1, // 1, 2, 3
                    'name' => $item->name
                ];
            })->toArray()
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        // Verify invoice totals are calculated
        $this->assertGreaterThan(0, $invoice->subtotal);
        $this->assertGreaterThan(0, $invoice->tax_amount);
        $this->assertGreaterThan(0, $invoice->total_amount);
        
        // Verify total amount is sum of subtotal and tax
        $this->assertEquals(
            round($invoice->subtotal + $invoice->tax_amount, 2),
            round($invoice->total_amount, 2)
        );

        // Verify all items have proper calculations
        foreach ($invoice->items as $item) {
            $this->assertGreaterThan(0, $item->base_gold_cost);
            $this->assertGreaterThan(0, $item->labor_cost);
            $this->assertGreaterThan(0, $item->profit_amount);
            $this->assertGreaterThan(0, $item->tax_amount);
            $this->assertGreaterThan(0, $item->unit_price);
            $this->assertGreaterThan(0, $item->total_price);
        }
    }

    /** @test */
    public function it_handles_bulk_invoice_creation_with_dynamic_pricing()
    {
        $customers = Customer::factory()->count(3)->create();
        $items = collect();
        
        for ($i = 1; $i <= 5; $i++) {
            $items->push(InventoryItem::factory()->create([
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'quantity' => 50,
                'weight' => rand(20, 80) / 10, // 2.0 to 8.0
                'gold_purity' => collect([18.0, 21.0, 22.0])->random(),
                'unit_price' => null,
                'cost_price' => null
            ]));
        }

        $bulkData = $customers->map(function ($customer, $index) use ($items) {
            return [
                'customer_id' => $customer->id,
                'gold_pricing' => [
                    'gold_price_per_gram' => 50.00 + ($index * 5), // 50, 55, 60
                    'labor_percentage' => 10.0 + ($index * 2), // 10, 12, 14
                    'profit_percentage' => 15.0 + ($index * 3), // 15, 18, 21
                    'tax_percentage' => 9.0 + ($index * 1) // 9, 10, 11
                ],
                'items' => [
                    [
                        'inventory_item_id' => $items->random()->id,
                        'quantity' => rand(1, 3),
                        'name' => 'Test Item'
                    ]
                ]
            ];
        })->toArray();

        // Create invoices individually since bulk method may not exist
        $createdInvoices = [];
        foreach ($bulkData as $invoiceData) {
            try {
                $invoice = $this->invoiceService->createInvoice($invoiceData);
                $createdInvoices[] = $invoice;
            } catch (\Exception $e) {
                // Handle individual failures
            }
        }

        $this->assertCount(3, $createdInvoices);

        // Verify invoices were created successfully
        foreach ($createdInvoices as $index => $invoice) {
            $this->assertInstanceOf(Invoice::class, $invoice);
            $this->assertGreaterThan(0, $invoice->gold_price_per_gram);
        }
    }

    /** @test */
    public function it_provides_detailed_pricing_breakdown_for_reporting()
    {
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 7.5,
            'gold_purity' => 21.0,
            'unit_price' => null,
            'cost_price' => null
        ]);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 65.00,
                'labor_percentage' => 14.0,
                'profit_percentage' => 22.0,
                'tax_percentage' => 11.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        // Verify invoice has pricing parameters stored
        $this->assertEquals(65.00, $invoice->gold_price_per_gram);
        $this->assertEquals(14.0, $invoice->labor_percentage);
        $this->assertEquals(22.0, $invoice->profit_percentage);
        $this->assertEquals(11.0, $invoice->tax_percentage);

        // Verify invoice item has detailed pricing breakdown
        $invoiceItem = $invoice->items->first();
        $this->assertGreaterThan(0, $invoiceItem->base_gold_cost);
        $this->assertGreaterThan(0, $invoiceItem->labor_cost);
        $this->assertGreaterThan(0, $invoiceItem->profit_amount);
        $this->assertGreaterThan(0, $invoiceItem->tax_amount);
        $this->assertEquals(2, $invoiceItem->quantity);
        $this->assertEquals(7.5, $invoiceItem->weight);
        $this->assertEquals(21.0, $invoiceItem->gold_purity);
    }

    /** @test */
    public function it_handles_invoice_cancellation_with_inventory_restoration()
    {
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 15,
            'weight' => 4.5,
            'gold_purity' => 18.0,
            'unit_price' => null,
            'cost_price' => null
        ]);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 55.00,
                'labor_percentage' => 12.0,
                'profit_percentage' => 18.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 5,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        // Verify inventory reduction
        $inventoryItem->refresh();
        $this->assertEquals(10, $inventoryItem->quantity);

        // Cancel invoice
        $cancelledInvoice = $this->invoiceService->cancelInvoice($invoice, 'Customer requested cancellation');

        // Verify cancellation
        $this->assertEquals('cancelled', $cancelledInvoice->status);

        // Verify inventory restoration
        $inventoryItem->refresh();
        $this->assertEquals(15, $inventoryItem->quantity);

        // Verify pricing data is preserved
        $this->assertEquals(55.00, $cancelledInvoice->gold_price_per_gram);
        $this->assertEquals(12.0, $cancelledInvoice->labor_percentage);
        $this->assertEquals(18.0, $cancelledInvoice->profit_percentage);
        $this->assertEquals(9.0, $cancelledInvoice->tax_percentage);
    }
}