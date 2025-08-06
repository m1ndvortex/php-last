<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use App\Services\CategoryImageService;
use App\Services\GoldPurityService;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\CategoryImageUploadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;
    protected CategoryImageService $categoryImageService;
    protected GoldPurityService $goldPurityService;

    public function __construct(
        CategoryService $categoryService,
        CategoryImageService $categoryImageService,
        GoldPurityService $goldPurityService
    ) {
        $this->categoryService = $categoryService;
        $this->categoryImageService = $categoryImageService;
        $this->goldPurityService = $goldPurityService;
    }
    /**
     * Get all categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by parent category
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Include children if requested
        if ($request->boolean('include_children')) {
            $query->with('children');
        }

        // Include parent if requested
        if ($request->boolean('include_parent')) {
            $query->with('parent');
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $category = $this->categoryService->createCategory($validated);

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $this->categoryImageService->uploadImage($category, $request->file('image'), [
                    'alt_text' => $validated['alt_text'] ?? null,
                    'alt_text_persian' => $validated['alt_text_persian'] ?? null,
                    'is_primary' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category->fresh()->load(['parent', 'children', 'images', 'primaryImage']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get a specific category.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $category->load(['parent', 'children', 'inventoryItems']),
        ]);
    }

    /**
     * Update a category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        try {
            $updatedCategory = $this->categoryService->updateCategory($category, $validated);

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $this->categoryImageService->uploadImage($updatedCategory, $request->file('image'), [
                    'alt_text' => $validated['alt_text'] ?? null,
                    'alt_text_persian' => $validated['alt_text_persian'] ?? null,
                    'is_primary' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $updatedCategory->fresh()->load(['parent', 'children', 'images', 'primaryImage']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get hierarchical tree structure of all categories.
     */
    public function getHierarchy(): JsonResponse
    {
        try {
            $hierarchy = $this->categoryService->getHierarchicalTree();

            return response()->json([
                'success' => true,
                'data' => $hierarchy,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an image for a category.
     */
    public function uploadImage(CategoryImageUploadRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        try {
            $categoryImage = $this->categoryImageService->uploadImage($category, $request->file('image'), [
                'alt_text' => $validated['alt_text'] ?? null,
                'alt_text_persian' => $validated['alt_text_persian'] ?? null,
                'is_primary' => $validated['is_primary'] ?? true,
                'sort_order' => $validated['sort_order'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $categoryImage,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove an image from a category.
     */
    public function removeImage(Category $category): JsonResponse
    {
        try {
            $this->categoryImageService->removeCategoryImage($category);

            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reorder categories based on drag-and-drop.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
            'categories.*.parent_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $this->categoryService->reorderCategories($validated['categories']);

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get standard gold purity options.
     */
    public function getGoldPurityOptions(): JsonResponse
    {
        try {
            $options = $this->goldPurityService->getStandardPurities();

            return response()->json([
                'success' => true,
                'data' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories formatted for selection dropdowns.
     */
    public function getForSelect(Request $request): JsonResponse
    {
        try {
            $excludeId = $request->get('exclude_id');
            $categories = $this->categoryService->getCategoriesForSelect($excludeId);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get main categories (root level).
     */
    public function getMainCategories(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getMainCategories();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subcategories for a parent category.
     */
    public function getSubcategories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'required|exists:categories,id',
        ]);

        try {
            $subcategories = $this->categoryService->getSubcategories($validated['parent_id']);

            return response()->json([
                'success' => true,
                'data' => $subcategories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search categories by name.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        try {
            $categories = $this->categoryService->searchCategories($validated['query']);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category path (breadcrumb) for a category.
     */
    public function getCategoryPath(Category $category): JsonResponse
    {
        try {
            $path = $this->categoryService->getCategoryPath($category);

            return response()->json([
                'success' => true,
                'data' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
