<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'template_id' => 'nullable|exists:invoice_templates,id',
            'invoice_number' => 'nullable|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'language' => 'required|in:en,fa',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.gold_purity' => 'nullable|numeric|min:0|max:24',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.serial_number' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            // Gold pricing parameters for dynamic pricing
            'gold_pricing' => 'nullable|array',
            'gold_pricing.gold_price_per_gram' => 'nullable|numeric|min:0',
            'gold_pricing.labor_percentage' => 'nullable|numeric|min:0|max:100',
            'gold_pricing.profit_percentage' => 'nullable|numeric|min:0|max:100',
            'gold_pricing.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
