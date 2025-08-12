<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\BusinessConfiguration;
use App\Services\InventoryManagementService;
use App\Services\GoldPricingService;
use App\Services\IntegrationEventService;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    protected $inventoryService;
    protected $goldPricingService;
    protected $integrationService;

    public function __construct(
        InventoryManagementService $inventoryService,
        GoldPricingService $goldPricingService
    ) {
        $this->inventoryService = $inventoryService;
        $this->goldPricingService = $goldPricingService;
    }

    protected function getIntegrationService()
    {
        if (!$this->integrationService) {
            $this->integrationService = app(IntegrationEventService::class);
        }
        return $this->integrationService;
    }

    /**
     * Create a new invoice with inventory integration and dynamic gold pricing.
     */
    public function createInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate customer exists
                $customer = Customer::findOrFail($data['customer_id']);
                
                // Generate invoice number with proper sequencing
                if (!isset($data['invoice_number'])) {
                    $data['invoice_number'] = $this->generateInvoiceNumber();
                }

                // Check inventory availability first using InventoryManagementService
                if (isset($data['items']) && is_array($data['items'])) {
                    $this->inventoryService->validateInventoryAvailability($data['items']);
                }

                // Get gold pricing parameters with defaults
                $goldPricing = $this->getGoldPricingParameters($data);

                // Create the invoice with gold pricing parameters
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'template_id' => $data['template_id'] ?? null,
                    'invoice_number' => $data['invoice_number'],
                    'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                    'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                    'language' => $data['language'] ?? $customer->preferred_language ?? 'en',
                    'notes' => $data['notes'] ?? null,
                    'internal_notes' => $data['internal_notes'] ?? null,
                    'status' => $data['status'] ?? 'draft',
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    // Store gold pricing parameters
                    'gold_price_per_gram' => $goldPricing['gold_price_per_gram'],
                    'labor_percentage' => $goldPricing['labor_percentage'],
                    'profit_percentage' => $goldPricing['profit_percentage'],
                    'tax_percentage' => $goldPricing['tax_percentage'],
                ]);

                // Add invoice items with dynamic pricing and inventory integration
                if (isset($data['items']) && is_array($data['items'])) {
                    $this->addInvoiceItemsWithDynamicPricing($invoice, $data['items'], $goldPricing);
                }

                // Add tags
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->addInvoiceTags($invoice, $data['tags']);
                }

                // Calculate totals
                $this->calculateInvoiceTotals($invoice);

                // Reserve inventory using InventoryManagementService
                $this->inventoryService->reserveInventory($invoice);

                // Log invoice creation for audit trail
                $this->logInvoiceActivity($invoice, 'created', 'Invoice created successfully with inventory reservation');

                // Trigger cross-module integration
                $this->getIntegrationService()->handleInvoiceCreated($invoice);

                return $invoice->load(['items.inventoryItem', 'tags', 'customer', 'template']);
                
            } catch (InsufficientInventoryException $e) {
                Log::error('Invoice creation failed due to insufficient inventory', [
                    'data' => $data,
                    'unavailable_items' => $e->getUnavailableItems(),
                    'error' => $e->getMessage()
                ]);
                throw $e;
                
            } catch (\Exception $e) {
                Log::error('Invoice creation failed', [
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Failed to create invoice: ' . $e->getMessage());
            }
        });
    }

    /**
     * Update an existing invoice with inventory integration and dynamic pricing.
     */
    public function updateInvoice(Invoice $invoice, array $data)
    {
        return DB::transaction(function () use ($invoice, $data) {
            try {
                // If items are being updated, handle inventory changes
                if (isset($data['items']) && is_array($data['items'])) {
                    // Load invoice with items to ensure we have the current state
                    $invoice->load('items');
                    
                    // Restore inventory from original items first
                    $this->inventoryService->restoreInventory($invoice);
                    
                    // Check availability for new items
                    $this->inventoryService->validateInventoryAvailability($data['items']);
                }

                // Get gold pricing parameters if provided
                $goldPricing = null;
                if (isset($data['gold_pricing'])) {
                    $goldPricing = $this->getGoldPricingParameters($data);
                }

                // Update invoice basic data including gold pricing if provided
                $updateData = [
                    'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                    'template_id' => $data['template_id'] ?? $invoice->template_id,
                    'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                    'due_date' => $data['due_date'] ?? $invoice->due_date,
                    'language' => $data['language'] ?? $invoice->language,
                    'notes' => $data['notes'] ?? $invoice->notes,
                    'internal_notes' => $data['internal_notes'] ?? $invoice->internal_notes,
                    'status' => $data['status'] ?? $invoice->status,
                    'discount_amount' => $data['discount_amount'] ?? $invoice->discount_amount,
                ];

                // Add gold pricing parameters if provided
                if ($goldPricing) {
                    $updateData = array_merge($updateData, [
                        'gold_price_per_gram' => $goldPricing['gold_price_per_gram'],
                        'labor_percentage' => $goldPricing['labor_percentage'],
                        'profit_percentage' => $goldPricing['profit_percentage'],
                        'tax_percentage' => $goldPricing['tax_percentage'],
                    ]);
                }

                $invoice->update($updateData);

                // Update items if provided
                if (isset($data['items'])) {
                    $invoice->items()->delete();
                    
                    if ($goldPricing) {
                        // Use dynamic pricing for new items
                        $this->addInvoiceItemsWithDynamicPricing($invoice, $data['items'], $goldPricing);
                    } else {
                        // Use existing gold pricing from invoice
                        $existingGoldPricing = [
                            'gold_price_per_gram' => $invoice->gold_price_per_gram ?? 0,
                            'labor_percentage' => $invoice->labor_percentage ?? 0,
                            'profit_percentage' => $invoice->profit_percentage ?? 0,
                            'tax_percentage' => $invoice->tax_percentage ?? 0,
                        ];
                        $this->addInvoiceItemsWithDynamicPricing($invoice, $data['items'], $existingGoldPricing);
                    }
                    
                    // Reload the invoice with new items
                    $invoice->load('items');
                    
                    // Reserve inventory for new items
                    $this->inventoryService->reserveInventory($invoice);
                }

                // Update tags if provided
                if (isset($data['tags'])) {
                    $invoice->tags()->delete();
                    $this->addInvoiceTags($invoice, $data['tags']);
                }

                // Recalculate totals
                $this->calculateInvoiceTotals($invoice);

                // Log invoice update for audit trail
                $this->logInvoiceActivity($invoice, 'updated', 'Invoice updated with inventory and pricing integration', [
                    'items_updated' => isset($data['items']),
                    'pricing_updated' => isset($data['gold_pricing']),
                    'new_items_count' => isset($data['items']) ? count($data['items']) : $invoice->items->count()
                ]);

                return $invoice->load(['items.inventoryItem', 'tags', 'customer', 'template']);
                
            } catch (InsufficientInventoryException $e) {
                Log::error('Invoice update failed due to insufficient inventory', [
                    'invoice_id' => $invoice->id,
                    'data' => $data,
                    'unavailable_items' => $e->getUnavailableItems(),
                    'error' => $e->getMessage()
                ]);
                throw $e;
                
            } catch (\Exception $e) {
                Log::error('Invoice update failed', [
                    'invoice_id' => $invoice->id,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Failed to update invoice: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get gold pricing parameters with defaults from business configuration
     */
    protected function getGoldPricingParameters(array $data): array
    {
        try {
            $defaults = $this->goldPricingService->getDefaultPricingSettings();
            
            $goldPricing = [
                'gold_price_per_gram' => $data['gold_pricing']['gold_price_per_gram'] ?? 0,
                'labor_percentage' => $data['gold_pricing']['labor_percentage'] ?? $defaults['default_labor_percentage'],
                'profit_percentage' => $data['gold_pricing']['profit_percentage'] ?? $defaults['default_profit_percentage'],
                'tax_percentage' => $data['gold_pricing']['tax_percentage'] ?? $defaults['default_tax_percentage'],
            ];

            // Validate pricing parameters if gold pricing is provided and gold price > 0
            // Allow gold_price_per_gram = 0 for fallback scenarios, but validate other parameters
            if (isset($data['gold_pricing'])) {
                // Check for negative values which are always invalid
                if ($goldPricing['gold_price_per_gram'] < 0) {
                    throw new \InvalidArgumentException('Invalid pricing parameters: Gold price per gram cannot be negative');
                }
                
                if ($goldPricing['labor_percentage'] < 0) {
                    throw new \InvalidArgumentException('Invalid pricing parameters: Labor percentage cannot be negative');
                }
                
                if ($goldPricing['profit_percentage'] < 0) {
                    throw new \InvalidArgumentException('Invalid pricing parameters: Profit percentage cannot be negative');
                }
                
                if ($goldPricing['tax_percentage'] < 0) {
                    throw new \InvalidArgumentException('Invalid pricing parameters: Tax percentage cannot be negative');
                }
                
                // Only do full validation if gold price > 0 (for dynamic pricing)
                if ($goldPricing['gold_price_per_gram'] > 0) {
                    $validationErrors = $this->goldPricingService->validatePricingParams([
                        'weight' => 1, // Dummy weight for validation
                        'gold_price_per_gram' => $goldPricing['gold_price_per_gram'],
                        'quantity' => 1, // Dummy quantity for validation
                        'labor_percentage' => $goldPricing['labor_percentage'],
                        'profit_percentage' => $goldPricing['profit_percentage'],
                        'tax_percentage' => $goldPricing['tax_percentage'],
                    ]);

                    if (!empty($validationErrors)) {
                        throw new \InvalidArgumentException('Invalid pricing parameters: ' . implode(', ', $validationErrors));
                    }
                }
            }

            return $goldPricing;
            
        } catch (\Exception $e) {
            Log::error('Failed to get gold pricing parameters', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to process gold pricing parameters: ' . $e->getMessage());
        }
    }

    /**
     * Add items to an invoice with dynamic gold pricing and inventory integration.
     */
    protected function addInvoiceItemsWithDynamicPricing(Invoice $invoice, array $items, array $goldPricing)
    {
        foreach ($items as $itemData) {
            $inventoryItem = null;
            $categoryId = $itemData['category_id'] ?? null;
            $mainCategoryId = $itemData['main_category_id'] ?? null;
            $categoryPath = $itemData['category_path'] ?? null;
            
            // Get real inventory item data
            if (isset($itemData['inventory_item_id']) && $itemData['inventory_item_id']) {
                $inventoryItem = InventoryItem::with(['category', 'mainCategory'])->find($itemData['inventory_item_id']);
                
                if ($inventoryItem) {
                    // Use real inventory data
                    $categoryId = $categoryId ?? $inventoryItem->category_id;
                    $mainCategoryId = $mainCategoryId ?? $inventoryItem->main_category_id;
                    
                    // Build category path from real data
                    if (!$categoryPath) {
                        $pathParts = [];
                        if ($inventoryItem->mainCategory) {
                            $pathParts[] = $inventoryItem->mainCategory->localized_name;
                        }
                        if ($inventoryItem->category && $inventoryItem->category->id !== $mainCategoryId) {
                            $pathParts[] = $inventoryItem->category->localized_name;
                        }
                        $categoryPath = implode(' > ', $pathParts);
                    }
                    
                    // Use real inventory data for pricing calculation
                    $itemName = $itemData['name'] ?? $inventoryItem->name;
                    $goldPurity = $itemData['gold_purity'] ?? $inventoryItem->gold_purity;
                    $weight = $itemData['weight'] ?? $inventoryItem->weight;
                } else {
                    // Fallback to provided data if inventory item not found
                    $itemName = $itemData['name'];
                    $goldPurity = $itemData['gold_purity'] ?? null;
                    $weight = $itemData['weight'] ?? null;
                }
            } else {
                // Use provided data for non-inventory items
                $itemName = $itemData['name'];
                $goldPurity = $itemData['gold_purity'] ?? null;
                $weight = $itemData['weight'] ?? null;
            }

            // Calculate dynamic pricing using GoldPricingService
            $unitPrice = $itemData['unit_price'] ?? null;
            $totalPrice = $itemData['total_price'] ?? null;
            $baseGoldCost = 0;
            $laborCost = 0;
            $profit = 0;
            $tax = 0;

            // Use dynamic pricing if weight and gold price are available
            if ($weight && $goldPricing['gold_price_per_gram'] > 0) {
                try {
                    // Validate pricing parameters before calculation
                    $pricingParams = [
                        'weight' => $weight,
                        'gold_price_per_gram' => $goldPricing['gold_price_per_gram'],
                        'labor_percentage' => $goldPricing['labor_percentage'],
                        'profit_percentage' => $goldPricing['profit_percentage'],
                        'tax_percentage' => $goldPricing['tax_percentage'],
                        'quantity' => $itemData['quantity']
                    ];

                    $validationErrors = $this->goldPricingService->validatePricingParams($pricingParams);
                    if (!empty($validationErrors)) {
                        throw new \InvalidArgumentException('Invalid pricing parameters for item: ' . implode(', ', $validationErrors));
                    }

                    $pricingResult = $this->goldPricingService->calculateItemPrice($pricingParams);

                    $unitPrice = $pricingResult['unit_price'];
                    $totalPrice = $pricingResult['total_price'];
                    $baseGoldCost = $pricingResult['base_gold_cost'];
                    $laborCost = $pricingResult['labor_cost'];
                    $profit = $pricingResult['profit'];
                    $tax = $pricingResult['tax'];

                    Log::info('Dynamic pricing calculated for invoice item', [
                        'invoice_id' => $invoice->id,
                        'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                        'item_name' => $itemName,
                        'weight' => $weight,
                        'gold_price_per_gram' => $goldPricing['gold_price_per_gram'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'price_breakdown' => [
                            'base_gold_cost' => $baseGoldCost,
                            'labor_cost' => $laborCost,
                            'profit' => $profit,
                            'tax' => $tax
                        ]
                    ]);

                } catch (\InvalidArgumentException $e) {
                    Log::error('Invalid pricing parameters for invoice item', [
                        'invoice_id' => $invoice->id,
                        'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                        'item_name' => $itemName,
                        'error' => $e->getMessage(),
                        'pricing_params' => $pricingParams ?? []
                    ]);
                    throw new \Exception("Pricing calculation failed for item '{$itemName}': " . $e->getMessage());
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to calculate dynamic pricing, using fallback', [
                        'invoice_id' => $invoice->id,
                        'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                        'item_name' => $itemName,
                        'error' => $e->getMessage(),
                        'weight' => $weight,
                        'gold_price_per_gram' => $goldPricing['gold_price_per_gram']
                    ]);
                    
                    // Fallback to provided or inventory unit price
                    $unitPrice = $unitPrice ?? ($inventoryItem ? $inventoryItem->unit_price : 0);
                    $totalPrice = $itemData['quantity'] * $unitPrice;
                    
                    // Log fallback usage
                    Log::info('Using fallback pricing for invoice item', [
                        'invoice_id' => $invoice->id,
                        'item_name' => $itemName,
                        'fallback_unit_price' => $unitPrice,
                        'fallback_total_price' => $totalPrice
                    ]);
                }
            } else {
                // Use provided or inventory unit price
                $unitPrice = $unitPrice ?? ($inventoryItem ? $inventoryItem->unit_price : 0);
                $totalPrice = $totalPrice ?? ($itemData['quantity'] * $unitPrice);
                
                Log::info('Using static pricing for invoice item', [
                    'invoice_id' => $invoice->id,
                    'item_name' => $itemName,
                    'reason' => $weight ? 'No gold price provided' : 'No weight available',
                    'static_unit_price' => $unitPrice,
                    'static_total_price' => $totalPrice
                ]);
            }

            // Ensure unit_price is never null
            $unitPrice = $unitPrice ?? 0;
            $totalPrice = $totalPrice ?? 0;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                'name' => $itemName,
                'description' => $itemData['description'] ?? ($inventoryItem ? $inventoryItem->description : null),
                'quantity' => $itemData['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'gold_purity' => $goldPurity,
                'weight' => $weight,
                'serial_number' => $itemData['serial_number'] ?? ($inventoryItem ? $inventoryItem->serial_number : null),
                'category_id' => $categoryId,
                'main_category_id' => $mainCategoryId,
                'category_path' => $categoryPath,
                // Store complete price breakdown
                'base_gold_cost' => $baseGoldCost,
                'labor_cost' => $laborCost,
                'profit_amount' => $profit,
                'tax_amount' => $tax,
            ]);
        }
    }

    /**
     * Add items to an invoice (legacy method for backward compatibility).
     */
    protected function addInvoiceItems(Invoice $invoice, array $items)
    {
        // Get default gold pricing for legacy calls
        $defaults = $this->goldPricingService->getDefaultPricingSettings();
        $goldPricing = [
            'gold_price_per_gram' => 0, // No dynamic pricing for legacy calls
            'labor_percentage' => $defaults['default_labor_percentage'],
            'profit_percentage' => $defaults['default_profit_percentage'],
            'tax_percentage' => $defaults['default_tax_percentage'],
        ];
        
        return $this->addInvoiceItemsWithDynamicPricing($invoice, $items, $goldPricing);
    }

    /**
     * Add tags to an invoice.
     */
    protected function addInvoiceTags(Invoice $invoice, array $tags)
    {
        foreach ($tags as $tag) {
            InvoiceTag::create([
                'invoice_id' => $invoice->id,
                'tag' => $tag,
            ]);
        }
    }

    /**
     * Generate proper invoice number with sequencing.
     */
    protected function generateInvoiceNumber(): string
    {
        // Get current year and month for better organization
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get the last invoice number for this year/month
        $lastInvoice = Invoice::where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract the sequence number and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return "INV-{$year}{$month}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate invoice totals with real business tax rates.
     */
    public function calculateInvoiceTotals(Invoice $invoice)
    {
        $subtotal = $invoice->items()->sum('total_price');
        $discountAmount = $invoice->discount_amount ?? 0;
        
        // Get real tax rate from business configuration
        $taxRate = $this->getBusinessTaxRate();
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        return $invoice;
    }

    /**
     * Get business tax rate from configuration.
     */
    protected function getBusinessTaxRate(): float
    {
        try {
            $taxRate = BusinessConfiguration::getValue('tax_rate', 9.0);
            return is_numeric($taxRate) ? (float) $taxRate : 9.0;
        } catch (\Exception $e) {
            // Fallback to default 9% tax rate
            return 9.0;
        }
    }

    /**
     * Get invoices with advanced filtering.
     */
    public function getInvoicesWithFilters(array $filters = [])
    {
        $query = Invoice::with(['customer', 'items.category', 'items.mainCategory', 'tags', 'template']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Filter by date range
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        // Filter by customer
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by language
        if (isset($filters['language'])) {
            $query->byLanguage($filters['language']);
        }

        // Filter by main category
        if (isset($filters['main_category_id']) && $filters['main_category_id']) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('main_category_id', $filters['main_category_id']);
            });
        }

        // Filter by category (subcategory)
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Filter by gold purity range
        if (isset($filters['gold_purity_min']) || isset($filters['gold_purity_max'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                if (isset($filters['gold_purity_min'])) {
                    $q->where('gold_purity', '>=', $filters['gold_purity_min']);
                }
                if (isset($filters['gold_purity_max'])) {
                    $q->where('gold_purity', '<=', $filters['gold_purity_max']);
                }
            });
        }

        // Filter by tags
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tag', $filters['tags']);
            });
        }

        // Search by invoice number, customer name, or item category
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.category', function ($categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('name_persian', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.mainCategory', function ($mainCategoryQuery) use ($search) {
                      $mainCategoryQuery->where('name', 'like', "%{$search}%")
                                       ->orWhere('name_persian', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Duplicate an invoice.
     */
    public function duplicateInvoice(Invoice $originalInvoice, array $overrides = [])
    {
        return DB::transaction(function () use ($originalInvoice, $overrides) {
            // Create new invoice with original data
            $newInvoiceData = [
                'customer_id' => $originalInvoice->customer_id,
                'template_id' => $originalInvoice->template_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'language' => $originalInvoice->language,
                'notes' => $originalInvoice->notes,
                'status' => 'draft',
            ];

            // Apply overrides
            $newInvoiceData = array_merge($newInvoiceData, $overrides);

            $newInvoice = Invoice::create($newInvoiceData);

            // Copy items
            foreach ($originalInvoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $newInvoice->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'gold_purity' => $item->gold_purity,
                    'weight' => $item->weight,
                    'serial_number' => $item->serial_number,
                ]);
            }

            // Copy tags
            foreach ($originalInvoice->tags as $tag) {
                InvoiceTag::create([
                    'invoice_id' => $newInvoice->id,
                    'tag' => $tag->tag,
                ]);
            }

            // Calculate totals
            $this->calculateInvoiceTotals($newInvoice);

            return $newInvoice->load(['items', 'tags', 'customer', 'template']);
        });
    }

    /**
     * Mark invoice as sent with proper status tracking.
     */
    public function markAsSent(Invoice $invoice, array $options = [])
    {
        try {
            $invoice->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Log status change
            $this->logInvoiceActivity($invoice, 'sent', 'Invoice marked as sent', $options);

            // Trigger sent event for notifications
            event(new \App\Events\InvoiceSent($invoice));

            return $invoice;
        } catch (\Exception $e) {
            \Log::error('Failed to mark invoice as sent', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to mark invoice as sent: ' . $e->getMessage());
        }
    }

    /**
     * Mark invoice as paid with payment processing.
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = [])
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            try {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $paymentData['payment_method'] ?? null,
                ]);

                // Create payment record if payment data provided
                if (!empty($paymentData)) {
                    $this->createPaymentRecord($invoice, $paymentData);
                }

                // Log status change
                $this->logInvoiceActivity($invoice, 'paid', 'Invoice marked as paid', $paymentData);

                // Update customer payment history
                $this->updateCustomerPaymentHistory($invoice);

                // Trigger paid event for notifications and accounting
                event(new \App\Events\InvoicePaid($invoice, $paymentData));

                return $invoice;
            } catch (\Exception $e) {
                \Log::error('Failed to mark invoice as paid', [
                    'invoice_id' => $invoice->id,
                    'payment_data' => $paymentData,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to mark invoice as paid: ' . $e->getMessage());
            }
        });
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(Invoice $invoice)
    {
        try {
            if ($invoice->status !== 'paid' && $invoice->due_date < now()) {
                $invoice->update(['status' => 'overdue']);
                
                $this->logInvoiceActivity($invoice, 'overdue', 'Invoice marked as overdue');
                
                // Trigger overdue event for notifications
                event(new \App\Events\InvoiceOverdue($invoice));
            }

            return $invoice;
        } catch (\Exception $e) {
            \Log::error('Failed to mark invoice as overdue', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to mark invoice as overdue: ' . $e->getMessage());
        }
    }

    /**
     * Cancel an invoice and restore inventory using InventoryManagementService.
     */
    public function cancelInvoice(Invoice $invoice, string $reason = '')
    {
        return DB::transaction(function () use ($invoice, $reason) {
            try {
                // Restore inventory using InventoryManagementService
                $this->inventoryService->restoreInventory($invoice);

                $invoice->update([
                    'status' => 'cancelled',
                    'internal_notes' => ($invoice->internal_notes ? $invoice->internal_notes . "\n" : '') . 
                                      "Cancelled on " . now()->format('Y-m-d H:i:s') . ": {$reason}"
                ]);

                $this->logInvoiceActivity($invoice, 'cancelled', "Invoice cancelled with inventory restoration: {$reason}");

                // Trigger cancelled event
                event(new \App\Events\InvoiceCancelled($invoice, $reason));

                Log::info('Invoice cancelled successfully', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'reason' => $reason
                ]);

                return $invoice;
            } catch (\Exception $e) {
                Log::error('Failed to cancel invoice', [
                    'invoice_id' => $invoice->id,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Failed to cancel invoice: ' . $e->getMessage());
            }
        });
    }

    /**
     * Delete an invoice.
     */
    public function deleteInvoice(Invoice $invoice)
    {
        return DB::transaction(function () use ($invoice) {
            try {
                // Load invoice with items to ensure we have the current state
                $invoice->load('items');
                
                // Restore inventory before deletion
                $this->inventoryService->restoreInventory($invoice);
                
                // Log inventory restoration for audit trail
                $this->logInvoiceActivity($invoice, 'inventory_restored', 'Inventory restored before invoice deletion');
                
                // Delete PDF file if exists
                if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
                    Storage::delete($invoice->pdf_path);
                }

                // Delete attachments
                foreach ($invoice->attachments as $attachment) {
                    if (Storage::exists($attachment->file_path)) {
                        Storage::delete($attachment->file_path);
                    }
                }

                // Log invoice deletion for audit trail
                $this->logInvoiceActivity($invoice, 'deleted', 'Invoice deleted with inventory restoration');

                // Delete the invoice (cascade will handle related records)
                $invoice->delete();

                return true;
                
            } catch (\Exception $e) {
                Log::error('Invoice deletion failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Failed to delete invoice: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get category-based invoice statistics.
     */
    public function getCategoryBasedStats(array $filters = [])
    {
        $query = Invoice::with(['items.category', 'items.mainCategory']);

        // Apply date filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        $invoices = $query->get();

        $stats = [
            'by_main_category' => [],
            'by_subcategory' => [],
            'category_revenue' => [],
            'top_categories' => [],
        ];

        // Group by main categories
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->mainCategory) {
                    $categoryName = $item->mainCategory->localized_name;
                    
                    if (!isset($stats['by_main_category'][$categoryName])) {
                        $stats['by_main_category'][$categoryName] = [
                            'count' => 0,
                            'total_amount' => 0,
                            'items_count' => 0,
                        ];
                    }
                    
                    $stats['by_main_category'][$categoryName]['count']++;
                    $stats['by_main_category'][$categoryName]['total_amount'] += $item->total_price;
                    $stats['by_main_category'][$categoryName]['items_count']++;
                }

                if ($item->category) {
                    $subcategoryName = $item->category->localized_name;
                    
                    if (!isset($stats['by_subcategory'][$subcategoryName])) {
                        $stats['by_subcategory'][$subcategoryName] = [
                            'count' => 0,
                            'total_amount' => 0,
                            'items_count' => 0,
                        ];
                    }
                    
                    $stats['by_subcategory'][$subcategoryName]['count']++;
                    $stats['by_subcategory'][$subcategoryName]['total_amount'] += $item->total_price;
                    $stats['by_subcategory'][$subcategoryName]['items_count']++;
                }
            }
        }

        // Sort by revenue
        uasort($stats['by_main_category'], function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        uasort($stats['by_subcategory'], function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        // Get top 10 categories
        $stats['top_categories'] = array_slice($stats['by_main_category'], 0, 10, true);

        return $stats;
    }

    /**
     * Get gold purity distribution statistics.
     */
    public function getGoldPurityStats(array $filters = [])
    {
        $query = InvoiceItem::with(['invoice', 'category', 'mainCategory'])
            ->whereNotNull('gold_purity');

        // Apply date filters through invoice relationship
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereHas('invoice', function ($q) use ($filters) {
                $q->byDateRange($filters['start_date'], $filters['end_date']);
            });
        }

        // Apply category filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['main_category_id'])) {
            $query->where('main_category_id', $filters['main_category_id']);
        }

        $items = $query->get();

        $stats = [
            'purity_distribution' => [],
            'average_purity' => 0,
            'total_items' => $items->count(),
            'purity_ranges' => [
                '14K-16K' => 0,
                '17K-19K' => 0,
                '20K-22K' => 0,
                '23K-24K' => 0,
            ],
        ];

        $totalPurity = 0;
        foreach ($items as $item) {
            $purity = $item->gold_purity;
            $purityKey = number_format($purity, 1) . 'K';
            
            if (!isset($stats['purity_distribution'][$purityKey])) {
                $stats['purity_distribution'][$purityKey] = [
                    'count' => 0,
                    'total_amount' => 0,
                    'percentage' => 0,
                ];
            }
            
            $stats['purity_distribution'][$purityKey]['count']++;
            $stats['purity_distribution'][$purityKey]['total_amount'] += $item->total_price;
            $totalPurity += $purity;

            // Categorize into ranges
            if ($purity >= 14 && $purity < 17) {
                $stats['purity_ranges']['14K-16K']++;
            } elseif ($purity >= 17 && $purity < 20) {
                $stats['purity_ranges']['17K-19K']++;
            } elseif ($purity >= 20 && $purity < 23) {
                $stats['purity_ranges']['20K-22K']++;
            } elseif ($purity >= 23) {
                $stats['purity_ranges']['23K-24K']++;
            }
        }

        // Calculate percentages and average
        if ($stats['total_items'] > 0) {
            $stats['average_purity'] = $totalPurity / $stats['total_items'];
            
            foreach ($stats['purity_distribution'] as $key => &$data) {
                $data['percentage'] = ($data['count'] / $stats['total_items']) * 100;
            }
        }

        return $stats;
    }

    /**
     * Create payment record for invoice.
     */
    protected function createPaymentRecord(Invoice $invoice, array $paymentData)
    {
        // This would integrate with a payment system
        // For now, we'll create a simple payment log
        $paymentRecord = [
            'invoice_id' => $invoice->id,
            'amount' => $paymentData['amount'] ?? $invoice->total_amount,
            'payment_method' => $paymentData['payment_method'] ?? 'cash',
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
        ];

        // Store payment record (would be in a payments table in real implementation)
        \Log::info('Payment recorded for invoice', $paymentRecord);
        
        return $paymentRecord;
    }

    /**
     * Update customer payment history.
     */
    protected function updateCustomerPaymentHistory(Invoice $invoice)
    {
        $customer = $invoice->customer;
        
        // Update customer's last payment date
        $customer->update([
            'last_payment_date' => now(),
            'total_paid' => $customer->invoices()->where('status', 'paid')->sum('total_amount'),
        ]);
    }

    /**
     * Log invoice activity for audit trail.
     */
    protected function logInvoiceActivity(Invoice $invoice, string $action, string $description, array $metadata = [])
    {
        try {
            // This would integrate with an audit log system
            $logData = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'action' => $action,
                'description' => $description,
                'user_id' => auth()->id(),
                'metadata' => $metadata,
                'timestamp' => now(),
            ];

            \Log::info('Invoice activity logged', $logData);
            
            // In a real implementation, this would save to an audit_logs table
            return $logData;
        } catch (\Exception $e) {
            \Log::error('Failed to log invoice activity', [
                'invoice_id' => $invoice->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process overdue invoices (can be called by scheduled job).
     */
    public function processOverdueInvoices()
    {
        $overdueInvoices = Invoice::where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled', 'overdue'])
            ->get();

        $processed = 0;
        foreach ($overdueInvoices as $invoice) {
            try {
                $this->markAsOverdue($invoice);
                $processed++;
            } catch (\Exception $e) {
                \Log::error('Failed to process overdue invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    /**
     * Create multiple invoices with inventory integration and error handling.
     */
    public function createBulkInvoices(array $invoicesData): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_processed' => count($invoicesData),
            'success_count' => 0,
            'failure_count' => 0
        ];

        foreach ($invoicesData as $index => $invoiceData) {
            try {
                $invoice = $this->createInvoice($invoiceData);
                $results['successful'][] = [
                    'index' => $index,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'Unknown'
                ];
                $results['success_count']++;

            } catch (InsufficientInventoryException $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'error_type' => 'insufficient_inventory',
                    'error_message' => $e->getMessage(),
                    'unavailable_items' => $e->getUnavailableItems(),
                    'customer_id' => $invoiceData['customer_id'] ?? null
                ];
                $results['failure_count']++;

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'error_type' => 'general_error',
                    'error_message' => $e->getMessage(),
                    'customer_id' => $invoiceData['customer_id'] ?? null
                ];
                $results['failure_count']++;
            }
        }

        Log::info('Bulk invoice creation completed', [
            'total_processed' => $results['total_processed'],
            'success_count' => $results['success_count'],
            'failure_count' => $results['failure_count']
        ]);

        return $results;
    }

    /**
     * Validate invoice data before creation with comprehensive checks.
     */
    public function validateInvoiceData(array $data): array
    {
        $errors = [];

        // Validate customer
        if (!isset($data['customer_id']) || !Customer::find($data['customer_id'])) {
            $errors['customer_id'] = 'Valid customer is required';
        }

        // Validate items
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            $errors['items'] = 'At least one item is required';
        } else {
            foreach ($data['items'] as $index => $item) {
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    $errors["items.{$index}.quantity"] = 'Quantity must be greater than zero';
                }

                if (isset($item['inventory_item_id']) && $item['inventory_item_id']) {
                    $inventoryItem = InventoryItem::find($item['inventory_item_id']);
                    if (!$inventoryItem) {
                        $errors["items.{$index}.inventory_item_id"] = 'Invalid inventory item';
                    }
                }
            }
        }

        // Validate gold pricing if provided
        if (isset($data['gold_pricing'])) {
            try {
                $this->getGoldPricingParameters($data);
            } catch (\Exception $e) {
                $errors['gold_pricing'] = $e->getMessage();
            }
        }

        // Validate dates
        if (isset($data['issue_date']) && !strtotime($data['issue_date'])) {
            $errors['issue_date'] = 'Invalid issue date format';
        }

        if (isset($data['due_date']) && !strtotime($data['due_date'])) {
            $errors['due_date'] = 'Invalid due date format';
        }

        return $errors;
    }

    /**
     * Get comprehensive invoice statistics with error handling.
     */
    public function getInvoiceStatistics(array $filters = []): array
    {
        try {
            $query = Invoice::query();

            // Apply filters
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $query->whereBetween('issue_date', [$filters['start_date'], $filters['end_date']]);
            }

            if (isset($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $invoices = $query->with(['items', 'customer'])->get();

            $stats = [
                'total_invoices' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
                'average_amount' => $invoices->avg('total_amount'),
                'by_status' => $invoices->groupBy('status')->map->count(),
                'by_month' => $invoices->groupBy(function ($invoice) {
                    return $invoice->issue_date->format('Y-m');
                })->map(function ($monthInvoices) {
                    return [
                        'count' => $monthInvoices->count(),
                        'total_amount' => $monthInvoices->sum('total_amount'),
                        'average_amount' => $monthInvoices->avg('total_amount')
                    ];
                }),
                'inventory_impact' => [
                    'total_items_sold' => $invoices->flatMap->items->sum('quantity'),
                    'unique_items_sold' => $invoices->flatMap->items->pluck('inventory_item_id')->filter()->unique()->count(),
                    'total_weight_sold' => $invoices->flatMap->items->sum('weight')
                ],
                'pricing_breakdown' => [
                    'total_base_gold_cost' => $invoices->flatMap->items->sum('base_gold_cost'),
                    'total_labor_cost' => $invoices->flatMap->items->sum('labor_cost'),
                    'total_profit' => $invoices->flatMap->items->sum('profit_amount'),
                    'total_tax' => $invoices->flatMap->items->sum('tax_amount')
                ]
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to generate invoice statistics', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to generate invoice statistics: ' . $e->getMessage());
        }
    }
}