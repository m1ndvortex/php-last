<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryImageUploadRequest extends FormRequest
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
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:2048', // 2MB max
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
                function ($attribute, $value, $fail) {
                    // Additional custom validation for image security
                    if (!$this->isSecureImage($value)) {
                        $fail('The uploaded image contains potentially harmful content.');
                    }
                },
            ],
            'alt_text' => 'nullable|string|max:255',
            'alt_text_persian' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'An image file is required.',
            'image.image' => 'The file must be a valid image.',
            'image.mimes' => 'Image must be JPEG, PNG, JPG, WebP, or GIF format.',
            'image.max' => 'Image size must not exceed 2MB.',
            'image.dimensions' => 'Image dimensions must be between 100x100 and 2000x2000 pixels.',
            'alt_text.max' => 'Alt text must not exceed 255 characters.',
            'alt_text_persian.max' => 'Persian alt text must not exceed 255 characters.',
            'sort_order.integer' => 'Sort order must be a number.',
            'sort_order.min' => 'Sort order must be at least 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'image' => 'category image',
            'alt_text' => 'image alt text',
            'alt_text_persian' => 'Persian image alt text',
            'is_primary' => 'primary image flag',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Perform additional security validation on the image.
     */
    private function isSecureImage($image): bool
    {
        // Get image info
        $imageInfo = getimagesize($image->getPathname());
        
        if (!$imageInfo) {
            return false;
        }

        // Check if it's a valid image type
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            return false;
        }

        // Check file signature matches extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $image->getPathname());
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }

        // Additional security checks can be added here
        // For example, scanning for embedded scripts or malicious content

        return true;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here
            if ($this->hasFile('image')) {
                $image = $this->file('image');
                
                // Check if the image can be processed
                try {
                    $imageResource = imagecreatefromstring(file_get_contents($image->getPathname()));
                    if (!$imageResource) {
                        $validator->errors()->add('image', 'The image file is corrupted or invalid.');
                    } else {
                        imagedestroy($imageResource);
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('image', 'The image file cannot be processed.');
                }
            }
        });
    }
}