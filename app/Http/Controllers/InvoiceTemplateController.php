<?php

namespace App\Http\Controllers;

use App\Models\InvoiceTemplate;
use App\Services\InvoiceTemplateService;
use App\Http\Requests\StoreInvoiceTemplateRequest;
use App\Http\Requests\UpdateInvoiceTemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceTemplateController extends Controller
{
    protected $templateService;

    public function __construct(InvoiceTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display a listing of invoice templates.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'language', 'active', 'search', 'sort_by', 'sort_order', 'per_page'
            ]);

            $templates = $this->templateService->getTemplatesWithFilters($filters);

            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created template.
     */
    public function store(StoreInvoiceTemplateRequest $request): JsonResponse
    {
        try {
            $template = $this->templateService->createTemplate($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully',
                'data' => $template,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified template.
     */
    public function show(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $invoiceTemplate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified template.
     */
    public function update(UpdateInvoiceTemplateRequest $request, InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        try {
            $updatedTemplate = $this->templateService->updateTemplate($invoiceTemplate, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully',
                'data' => $updatedTemplate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified template.
     */
    public function destroy(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        try {
            $this->templateService->deleteTemplate($invoiceTemplate);

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(InvoiceTemplate $invoiceTemplate, Request $request): JsonResponse
    {
        try {
            $overrides = $request->only(['name', 'language', 'is_active']);
            $duplicatedTemplate = $this->templateService->duplicateTemplate($invoiceTemplate, $overrides);

            return response()->json([
                'success' => true,
                'message' => 'Template duplicated successfully',
                'data' => $duplicatedTemplate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set template as default.
     */
    public function setAsDefault(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        try {
            $updatedTemplate = $this->templateService->setAsDefault($invoiceTemplate);

            return response()->json([
                'success' => true,
                'message' => 'Template set as default successfully',
                'data' => $updatedTemplate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set template as default',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get default template structure for drag-drop builder.
     */
    public function getDefaultStructure(Request $request): JsonResponse
    {
        try {
            $language = $request->get('language', 'en');
            $structure = $this->templateService->getDefaultTemplateStructure($language);

            return response()->json([
                'success' => true,
                'data' => $structure,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get default structure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate template structure.
     */
    public function validateStructure(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'template_data' => 'required|array',
            ]);

            $errors = $this->templateService->validateTemplateStructure($request->template_data);

            return response()->json([
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate structure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
