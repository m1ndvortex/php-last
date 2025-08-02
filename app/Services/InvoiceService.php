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
        $query = Invoice::with(['customer', 'items', 'tags', 'template']);

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

        // Filter by tags
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tag', $filters['tags']);
            });
        }

        // Search by invoice number or customer name
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
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
}