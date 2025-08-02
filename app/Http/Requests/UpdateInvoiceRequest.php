<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
        $invoiceId = $this->route('invoice')->id ?? null;
        
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'template_id' => 'nullable|exists:invoice_templates,id',
            'invoice_number' => 'sometimes|string|unique:invoices,invoice_number,' . $invoiceId,
            'issue_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:issue_date',
            'language' => 'sometimes|in:en,fa',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.gold_purity' => 'nullable|numeric|min:0|max:24',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.serial_number' => 'nullable|string|max:255',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
