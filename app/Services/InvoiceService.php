<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use App\Models\Customer;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Create a new invoice.
     */
    public function createInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate invoice number if not provided
            if (!isset($data['invoice_number'])) {
                $data['invoice_number'] = Invoice::generateInvoiceNumber();
            }

            // Create the invoice
            $invoice = Invoice::create([
                'customer_id' => $data['customer_id'],
                'template_id' => $data['template_id'] ?? null,
                'invoice_number' => $data['invoice_number'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'language' => $data['language'] ?? 'en',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Add invoice items
            if (isset($data['items']) && is_array($data['items'])) {
                $this->addInvoiceItems($invoice, $data['items']);
            }

            // Add tags
            if (isset($data['tags']) && is_array($data['tags'])) {
                $this->addInvoiceTags($invoice, $data['tags']);
            }

            // Calculate totals
            $this->calculateInvoiceTotals($invoice);

            return $invoice->load(['items', 'tags', 'customer', 'template']);
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
     * Add items to an invoice.
     */
    protected function addInvoiceItems(Invoice $invoice, array $items)
    {
        foreach ($items as $itemData) {
            $totalPrice = $itemData['quantity'] * $itemData['unit_price'];

            // Get category information from inventory item if not provided
            $categoryId = $itemData['category_id'] ?? null;
            $mainCategoryId = $itemData['main_category_id'] ?? null;
            $categoryPath = $itemData['category_path'] ?? null;

            if (isset($itemData['inventory_item_id']) && $itemData['inventory_item_id']) {
                $inventoryItem = InventoryItem::with(['category', 'mainCategory'])->find($itemData['inventory_item_id']);
                if ($inventoryItem) {
                    $categoryId = $categoryId ?? $inventoryItem->category_id;
                    $mainCategoryId = $mainCategoryId ?? $inventoryItem->main_category_id;
                    
                    // Build category path if not provided
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
                }
            }

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                'name' => $itemData['name'],
                'description' => $itemData['description'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $totalPrice,
                'gold_purity' => $itemData['gold_purity'] ?? null,
                'weight' => $itemData['weight'] ?? null,
                'serial_number' => $itemData['serial_number'] ?? null,
                'category_id' => $categoryId,
                'main_category_id' => $mainCategoryId,
                'category_path' => $categoryPath,
            ]);
        }
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
     * Calculate invoice totals.
     */
    public function calculateInvoiceTotals(Invoice $invoice)
    {
        $subtotal = $invoice->items()->sum('total_price');
        $discountAmount = $invoice->discount_amount ?? 0;
        $taxAmount = ($subtotal - $discountAmount) * 0.09; // 9% tax rate
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        return $invoice;
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
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $invoice;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $invoice;
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
}