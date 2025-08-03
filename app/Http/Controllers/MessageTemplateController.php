<?php

namespace App\Http\Controllers;

use App\Services\MessageTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageTemplateController extends Controller
{
    private MessageTemplateService $templateService;

    public function __construct(MessageTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Get all templates grouped by category
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->templateService->getAllTemplatesGrouped()
        ]);
    }

    /**
     * Get templates by type and category
     */
    public function getByTypeAndCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:email,sms,whatsapp',
            'category' => 'required|in:invoice,reminder,birthday,notification',
            'language' => 'string|in:en,fa'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $templates = $this->templateService->getTemplatesByTypeAndCategory(
            $request->type,
            $request->category,
            $request->language ?? 'en'
        );

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Create or update template
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,whatsapp',
            'category' => 'required|in:invoice,reminder,birthday,notification',
            'language' => 'required|in:en,fa',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = $this->templateService->saveTemplate($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Template saved successfully',
            'data' => $template
        ]);
    }

    /**
     * Render template with variables
     */
    public function render(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string',
            'variables' => 'required|array',
            'language' => 'string|in:en,fa'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $rendered = $this->templateService->renderTemplate(
            $request->template_name,
            $request->variables,
            $request->language ?? 'en'
        );

        if (!$rendered) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rendered
        ]);
    }

    /**
     * Get default variables for categories
     */
    public function getDefaultVariables(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->templateService->getDefaultVariables()
        ]);
    }
}