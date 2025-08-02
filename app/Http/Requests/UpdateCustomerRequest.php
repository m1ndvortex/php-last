<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer;

class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer')->id ?? null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customerId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'preferred_language' => 'nullable|in:en,fa',
            'customer_type' => 'nullable|in:' . implode(',', array_keys(Customer::CUSTOMER_TYPES)),
            'credit_limit' => 'nullable|numeric|min:0|max:999999.99',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string|max:2000',
            'birthday' => 'nullable|date|before:today',
            'anniversary' => 'nullable|date|before_or_equal:today',
            'is_active' => 'nullable|boolean',
            'crm_stage' => 'nullable|in:' . implode(',', array_keys(Customer::CRM_STAGES)),
            'lead_source' => 'nullable|in:' . implode(',', array_keys(Customer::LEAD_SOURCES)),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'credit_limit.numeric' => 'Credit limit must be a valid number.',
            'credit_limit.min' => 'Credit limit cannot be negative.',
            'payment_terms.integer' => 'Payment terms must be a whole number.',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days.',
            'birthday.before' => 'Birthday must be in the past.',
            'anniversary.before_or_equal' => 'Anniversary cannot be in the future.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'preferred_language' => 'preferred language',
            'customer_type' => 'customer type',
            'credit_limit' => 'credit limit',
            'payment_terms' => 'payment terms',
            'is_active' => 'active status',
            'crm_stage' => 'CRM stage',
            'lead_source' => 'lead source',
        ];
    }
}
