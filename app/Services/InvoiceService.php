<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\BusinessConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Create a new invoice with real data and inventory deduction.
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

                // Validate inventory availability before creating invoice
                if (isset($data['items']) && is_array($data['items'])) {
                    $this->validateInventoryAvailability($data['items']);
                }

                // Create the invoice with real customer data
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
                ]);

                // Add invoice items with real inventory data and deduction
                if (isset($data['items']) && is_array($data['items'])) {
                    $this->addInvoiceItemsWithInventoryDeduction($invoice, $data['items']);
                }

                // Add tags
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->addInvoiceTags($invoice, $data['tags']);
                }

                // Calculate totals with real business tax rates
                $this->calculateInvoiceTotals($invoice);

                // Log invoice creation for audit trail
                $this->logInvoiceActivity($invoice, 'created', 'Invoice created successfully');

                return $invoice->load(['items.inventoryItem', 'tags', 'customer', 'template']);
                
            } catch (\Exception $e) {
                // Log error for debugging
                \Log::error('Invoice creation failed', [
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Failed to create invoice: ' . $e->getMessage());
            }
        });
    }

    /**
     * Update an existing invoice.
     */
    public function updateInvoice(Invoice $invoice, array $data)
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Update invoice basic data
            $invoice->update([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'template_id' => $data['template_id'] ?? $invoice->template_id,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'language' => $data['language'] ?? $invoice->language,
                'notes' => $data['notes'] ?? $invoice->notes,
                'internal_notes' => $data['internal_notes'] ?? $invoice->internal_notes,
                'status' => $data['status'] ?? $invoice->status,
            ]);

            // Update items if provided
            if (isset($data['items'])) {
                $invoice->items()->delete();
                $this->addInvoiceItems($invoice, $data['items']);
            }

            // Update tags if provided
            if (isset($data['tags'])) {
                $invoice->tags()->delete();
                $this->addInvoiceTags($invoice, $data['tags']);
            }

            // Recalculate totals
            $this->calculateInvoiceTotals($invoice);

            return $invoice->load(['items', 'tags', 'customer', 'template']);
        });
    }

    /**
     * Validate inventory availability before creating invoice.
     */
    protected function validateInventoryAvailability(array $items)
    {
        foreach ($items as $itemData) {
            if (isset($itemData['inventory_item_id']) && $itemData['inventory_item_id']) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                
                if (!$inventoryItem) {
                    throw new \Exception("Inventory item with ID {$itemData['inventory_item_id']} not found");
                }
                
                if (!$inventoryItem->is_active) {
                    throw new \Exception("Inventory item '{$inventoryItem->name}' is not active");
                }
                
                $requestedQuantity = $itemData['quantity'];
                if ($inventoryItem->quantity < $requestedQuantity) {
                    throw new \Exception("Insufficient stock for '{$inventoryItem->name}'. Available: {$inventoryItem->quantity}, Requested: {$requestedQuantity}");
                }
            }
        }
    }

    /**
     * Add items to an invoice with real inventory data and deduction.
     */
    protected function addInvoiceItemsWithInventoryDeduction(Invoice $invoice, array $items)
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
                    
                    // Deduct inventory quantity
                    $requestedQuantity = $itemData['quantity'];
                    $inventoryItem->decrement('quantity', $requestedQuantity);
                    
                    // Create inventory movement record
                    $inventoryItem->movements()->create([
                        'type' => 'sale',
                        'quantity' => -$requestedQuantity,
                        'movement_date' => now(),
                        'reference_type' => 'invoice',
                        'reference_id' => $invoice->id,
                        'notes' => "Sold via invoice #{$invoice->invoice_number}",
                        'user_id' => auth()->id() ?? 1, // Default to user ID 1 for tests
                    ]);
                    
                    // Use real inventory data for invoice item
                    $itemName = $itemData['name'] ?? $inventoryItem->name;
                    $unitPrice = $itemData['unit_price'] ?? $inventoryItem->unit_price;
                    $goldPurity = $itemData['gold_purity'] ?? $inventoryItem->gold_purity;
                    $weight = $itemData['weight'] ?? $inventoryItem->weight;
                } else {
                    // Fallback to provided data if inventory item not found
                    $itemName = $itemData['name'];
                    $unitPrice = $itemData['unit_price'];
                    $goldPurity = $itemData['gold_purity'] ?? null;
                    $weight = $itemData['weight'] ?? null;
                }
            } else {
                // Use provided data for non-inventory items
                $itemName = $itemData['name'];
                $unitPrice = $itemData['unit_price'];
                $goldPurity = $itemData['gold_purity'] ?? null;
                $weight = $itemData['weight'] ?? null;
            }

            $totalPrice = $itemData['quantity'] * $unitPrice;

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
            ]);
        }
    }

    /**
     * Add items to an invoice (legacy method for backward compatibility).
     */
    protected function addInvoiceItems(Invoice $invoice, array $items)
    {
        return $this->addInvoiceItemsWithInventoryDeduction($invoice, $items);
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
     * Cancel an invoice and restore inventory.
     */
    public function cancelInvoice(Invoice $invoice, string $reason = '')
    {
        return DB::transaction(function () use ($invoice, $reason) {
            try {
                // Restore inventory quantities
                foreach ($invoice->items as $item) {
                    if ($item->inventory_item_id && $item->inventoryItem) {
                        $item->inventoryItem->increment('quantity', $item->quantity);
                        
                        // Create inventory movement record
                        $item->inventoryItem->movements()->create([
                            'type' => 'return',
                            'quantity' => $item->quantity,
                            'movement_date' => now(),
                            'reference_type' => 'invoice_cancellation',
                            'reference_id' => $invoice->id,
                            'notes' => "Returned due to invoice cancellation: {$reason}",
                            'user_id' => auth()->id() ?? 1, // Default to user ID 1 for tests
                        ]);
                    }
                }

                $invoice->update([
                    'status' => 'cancelled',
                    'internal_notes' => ($invoice->internal_notes ? $invoice->internal_notes . "\n" : '') . 
                                      "Cancelled on " . now()->format('Y-m-d H:i:s') . ": {$reason}"
                ]);

                $this->logInvoiceActivity($invoice, 'cancelled', "Invoice cancelled: {$reason}");

                // Trigger cancelled event
                event(new \App\Events\InvoiceCancelled($invoice, $reason));

                return $invoice;
            } catch (\Exception $e) {
                \Log::error('Failed to cancel invoice', [
                    'invoice_id' => $invoice->id,
                    'reason' => $reason,
                    'error' => $e->getMessage()
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

            // Delete the invoice (cascade will handle related records)
            $invoice->delete();

            return true;
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
}