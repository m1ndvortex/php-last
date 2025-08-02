<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringInvoiceRequest extends FormRequest
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
            'customer_id' => 'sometimes|exists:customers,id',
            'template_id' => 'nullable|exists:invoice_templates,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly',
            'interval' => 'sometimes|integer|min:1',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_invoices' => 'nullable|integer|min:1',
            'amount' => 'sometimes|numeric|min:0',
            'language' => 'sometimes|in:en,fa',
            'is_active' => 'boolean',
            'invoice_data' => 'sometimes|array',
            'invoice_data.items' => 'required_with:invoice_data|array|min:1',
            'invoice_data.items.*.name' => 'required_with:invoice_data.items|string|max:255',
            'invoice_data.items.*.quantity' => 'required_with:invoice_data.items|numeric|min:0.001',
            'invoice_data.items.*.unit_price' => 'required_with:invoice_data.items|numeric|min:0',
        ];
    }
}
