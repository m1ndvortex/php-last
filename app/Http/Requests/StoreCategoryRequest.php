<?php

namespace App\Http\Requests;

use App\Services\GoldPurityService;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $goldPurityService = app(GoldPurityService::class);

        return [
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'description_persian' => 'nullable|string|max:1000',
            'code' => 'required|string|max:10|unique:categories,code',
            'is_active' => 'boolean',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->validateParentCategory($value)) {
                        $fail('Invalid parent category selection.');
                    }
                },
            ],
            'default_gold_purity' => array_merge(['nullable'], $goldPurityService->getPurityValidationRules()),
            'sort_order' => 'nullable|integer|min:0',
            'specifications' => 'nullable|array',
            'specifications.*' => 'string|max:255',
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:2048', // 2MB max
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
            'alt_text' => 'nullable|string|max:255',
            'alt_text_persian' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name must not exceed 255 characters.',
            'code.required' => 'Category code is required.',
            'code.unique' => 'This category code is already taken.',
            'code.max' => 'Category code must not exceed 10 characters.',
            'parent_id.exists' => 'Selected parent category does not exist.',
            'default_gold_purity.numeric' => 'Gold purity must be a number.',
            'default_gold_purity.min' => 'Gold purity must be at least 1 karat.',
            'default_gold_purity.max' => 'Gold purity must not exceed 24 karats.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be JPEG, PNG, JPG, WebP, or GIF format.',
            'image.max' => 'Image size must not exceed 2MB.',
            'image.dimensions' => 'Image dimensions must be between 100x100 and 2000x2000 pixels.',
            'alt_text.max' => 'Alt text must not exceed 255 characters.',
            'alt_text_persian.max' => 'Persian alt text must not exceed 255 characters.',
        ];
    }

    /**
     * Validate parent category selection.
     */
    private function validateParentCategory(int $parentId): bool
    {
        // Additional validation logic can be added here
        // For now, just check if the category exists and is active
        $parentCategory = \App\Models\Category::find($parentId);
        return $parentCategory && $parentCategory->is_active;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'category name',
            'name_persian' => 'Persian category name',
            'description' => 'description',
            'description_persian' => 'Persian description',
            'code' => 'category code',
            'parent_id' => 'parent category',
            'default_gold_purity' => 'default gold purity',
            'sort_order' => 'sort order',
            'image' => 'category image',
            'alt_text' => 'image alt text',
            'alt_text_persian' => 'Persian image alt text',
        ];
    }
}