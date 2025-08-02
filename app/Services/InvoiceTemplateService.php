<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use Illuminate\Support\Facades\DB;

class InvoiceTemplateService
{
    /**
     * Create a new invoice template.
     */
    public function createTemplate(array $data)
    {
        return DB::transaction(function () use ($data) {
            // If this is set as default, unset other defaults for the same language
            if ($data['is_default'] ?? false) {
                InvoiceTemplate::where('language', $data['language'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return InvoiceTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'template_data' => $data['template_data'],
                'language' => $data['language'] ?? 'en',
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(InvoiceTemplate $template, array $data)
    {
        return DB::transaction(function () use ($template, $data) {
            // If this is set as default, unset other defaults for the same language
            if (($data['is_default'] ?? false) && !$template->is_default) {
                InvoiceTemplate::where('language', $data['language'] ?? $template->language)
                    ->where('is_default', true)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            $template->update([
                'name' => $data['name'] ?? $template->name,
                'description' => $data['description'] ?? $template->description,
                'template_data' => $data['template_data'] ?? $template->template_data,
                'language' => $data['language'] ?? $template->language,
                'is_default' => $data['is_default'] ?? $template->is_default,
                'is_active' => $data['is_active'] ?? $template->is_active,
            ]);

            return $template;
        });
    }

    /**
     * Get templates with filtering.
     */
    public function getTemplatesWithFilters(array $filters = [])
    {
        $query = InvoiceTemplate::query();

        // Filter by language
        if (isset($filters['language'])) {
            $query->byLanguage($filters['language']);
        }

        // Filter by active status
        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Search by name
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get default template structure for drag-drop builder.
     */
    public function getDefaultTemplateStructure($language = 'en')
    {
        $structure = [
            'header' => [
                'type' => 'header',
                'position' => ['x' => 0, 'y' => 0],
                'size' => ['width' => 100, 'height' => 15],
                'fields' => [
                    'company_logo' => ['x' => 5, 'y' => 2, 'width' => 20, 'height' => 10],
                    'company_name' => ['x' => 30, 'y' => 2, 'width' => 40, 'height' => 5],
                    'company_address' => ['x' => 30, 'y' => 7, 'width' => 40, 'height' => 8],
                ]
            ],
            'invoice_info' => [
                'type' => 'invoice_info',
                'position' => ['x' => 0, 'y' => 15],
                'size' => ['width' => 100, 'height' => 20],
                'fields' => [
                    'invoice_number' => ['x' => 5, 'y' => 2, 'width' => 40, 'height' => 5],
                    'issue_date' => ['x' => 5, 'y' => 7, 'width' => 40, 'height' => 5],
                    'due_date' => ['x' => 5, 'y' => 12, 'width' => 40, 'height' => 5],
                    'customer_info' => ['x' => 55, 'y' => 2, 'width' => 40, 'height' => 15],
                ]
            ],
            'items_table' => [
                'type' => 'items_table',
                'position' => ['x' => 0, 'y' => 35],
                'size' => ['width' => 100, 'height' => 40],
                'columns' => [
                    'name' => ['width' => 30, 'label' => $language === 'fa' ? 'نام کالا' : 'Item Name'],
                    'quantity' => ['width' => 15, 'label' => $language === 'fa' ? 'تعداد' : 'Quantity'],
                    'unit_price' => ['width' => 20, 'label' => $language === 'fa' ? 'قیمت واحد' : 'Unit Price'],
                    'total_price' => ['width' => 20, 'label' => $language === 'fa' ? 'قیمت کل' : 'Total Price'],
                    'gold_purity' => ['width' => 15, 'label' => $language === 'fa' ? 'عیار طلا' : 'Gold Purity'],
                ]
            ],
            'totals' => [
                'type' => 'totals',
                'position' => ['x' => 60, 'y' => 75],
                'size' => ['width' => 35, 'height' => 15],
                'fields' => [
                    'subtotal' => ['x' => 0, 'y' => 0, 'width' => 35, 'height' => 3],
                    'discount' => ['x' => 0, 'y' => 3, 'width' => 35, 'height' => 3],
                    'tax' => ['x' => 0, 'y' => 6, 'width' => 35, 'height' => 3],
                    'total' => ['x' => 0, 'y' => 9, 'width' => 35, 'height' => 6],
                ]
            ],
            'footer' => [
                'type' => 'footer',
                'position' => ['x' => 0, 'y' => 90],
                'size' => ['width' => 100, 'height' => 10],
                'fields' => [
                    'notes' => ['x' => 5, 'y' => 0, 'width' => 90, 'height' => 5],
                    'qr_code' => ['x' => 80, 'y' => 5, 'width' => 15, 'height' => 5],
                ]
            ]
        ];

        return $structure;
    }

    /**
     * Validate template structure.
     */
    public function validateTemplateStructure(array $templateData)
    {
        $requiredSections = ['header', 'invoice_info', 'items_table', 'totals'];
        $errors = [];

        foreach ($requiredSections as $section) {
            if (!isset($templateData[$section])) {
                $errors[] = "Missing required section: {$section}";
            }
        }

        // Validate positions and sizes
        foreach ($templateData as $sectionName => $section) {
            if (!isset($section['position']) || !isset($section['size'])) {
                $errors[] = "Section {$sectionName} missing position or size";
            }

            if (isset($section['position'])) {
                $pos = $section['position'];
                if (!isset($pos['x']) || !isset($pos['y']) || $pos['x'] < 0 || $pos['y'] < 0) {
                    $errors[] = "Invalid position for section {$sectionName}";
                }
            }

            if (isset($section['size'])) {
                $size = $section['size'];
                if (!isset($size['width']) || !isset($size['height']) || $size['width'] <= 0 || $size['height'] <= 0) {
                    $errors[] = "Invalid size for section {$sectionName}";
                }
            }
        }

        return $errors;
    }

    /**
     * Duplicate a template.
     */
    public function duplicateTemplate(InvoiceTemplate $template, array $overrides = [])
    {
        $newTemplateData = [
            'name' => ($overrides['name'] ?? $template->name) . ' (Copy)',
            'description' => $template->description,
            'template_data' => $template->template_data,
            'language' => $template->language,
            'is_default' => false, // Copies are never default
            'is_active' => $overrides['is_active'] ?? true,
        ];

        // Apply overrides
        $newTemplateData = array_merge($newTemplateData, $overrides);

        return $this->createTemplate($newTemplateData);
    }

    /**
     * Delete a template.
     */
    public function deleteTemplate(InvoiceTemplate $template)
    {
        // Check if template is being used by any invoices
        if ($template->invoices()->count() > 0) {
            throw new \Exception('Cannot delete template that is being used by invoices');
        }

        return $template->delete();
    }

    /**
     * Set template as default for its language.
     */
    public function setAsDefault(InvoiceTemplate $template)
    {
        return DB::transaction(function () use ($template) {
            // Unset other defaults for the same language
            InvoiceTemplate::where('language', $template->language)
                ->where('is_default', true)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);

            // Set this template as default
            $template->update(['is_default' => true, 'is_active' => true]);

            return $template;
        });
    }
}