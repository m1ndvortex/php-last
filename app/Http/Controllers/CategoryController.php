<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'code' => 'required|string|max:10|unique:categories,code',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children']),
        ], 201);
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
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_persian' => 'nullable|string',
            'code' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('categories')->ignore($category->id)],
            'is_active' => 'boolean',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    // Prevent setting self as parent
                    if ($value == $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                    
                    // Prevent circular references
                    if ($value && $this->wouldCreateCircularReference($category->id, $value)) {
                        $fail('This would create a circular reference.');
                    }
                },
            ],
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has inventory items
        if ($category->inventoryItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing inventory items',
            ], 422);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with child categories',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Check if setting a parent would create a circular reference.
     */
    private function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        $parent = Category::find($parentId);
        
        while ($parent) {
            if ($parent->id === $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }
}
