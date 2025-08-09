<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'sku' => 'nullable|string|unique:inventory_items,sku',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
            // Make unit_price and cost_price nullable (optional)
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gold_purity' => 'nullable|numeric|min:0|max:24',
            'weight' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|unique:inventory_items,serial_number',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date|after:today',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'location_id.required' => 'Please select a location.',
            'location_id.exists' => 'The selected location is invalid.',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity cannot be negative.',
            'unit_price.numeric' => 'Unit price must be a number.',
            'unit_price.min' => 'Unit price cannot be negative.',
            'cost_price.numeric' => 'Cost price must be a number.',
            'cost_price.min' => 'Cost price cannot be negative.',
            'sku.unique' => 'This SKU is already in use.',
            'serial_number.unique' => 'This serial number is already in use.',
        ];
    }
}