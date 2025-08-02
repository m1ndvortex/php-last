<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringInvoiceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'interval' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'max_invoices' => 'nullable|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'language' => 'required|in:en,fa',
            'is_active' => 'boolean',
            'invoice_data' => 'required|array',
            'invoice_data.items' => 'required|array|min:1',
            'invoice_data.items.*.name' => 'required|string|max:255',
            'invoice_data.items.*.quantity' => 'required|numeric|min:0.001',
            'invoice_data.items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}
