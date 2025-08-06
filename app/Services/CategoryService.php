<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    /**
     * Create a new category with validation.
     */
    public function createCategory(array $data): Category
    {
        // Validate hierarchy if parent_id is provided
        if (isset($data['parent_id']) && $data['parent_id']) {
            $this->validateHierarchy(null, $data['parent_id']);
        }

        return DB::transaction(function () use ($data) {
            // Set sort order if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->getNextSortOrder($data['parent_id'] ?? null);
            }

            $category = Category::create($data);
            
            Log::info('Category created', ['category_id' => $category->id, 'name' => $category->name]);
            
            return $category->load(['parent', 'children', 'images']);
        });
    }

    /**
     * Update an existing category with validation.
     */
    public function updateCategory(Category $category, array $data): Category
    {
        // Validate hierarchy if parent_id is being changed
        if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
            $this->validateHierarchy($category->id, $data['parent_id']);
        }

        return DB::transaction(function () use ($category, $data) {
            $category->update($data);
            
            Log::info('Category updated', ['category_id' => $category->id, 'name' => $category->name]);
            
            return $category->load(['parent', 'children', 'images']);
        });
    }

    /**
     * Delete a category with proper validation.
     */
    public function deleteCategory(Category $category): bool
    {
        // Check if category has inventory items
        if ($category->inventoryItems()->count() > 0 || $category->mainCategoryItems()->count() > 0) {
            throw new \Exception('Cannot delete category with existing inventory items');
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            throw new \Exception('Cannot delete category with child categories');
        }

        return DB::transaction(function () use ($category) {
            // Delete associated images
            foreach ($category->images as $image) {
                app(CategoryImageService::class)->removeImage($image);
            }

            $categoryId = $category->id;
            $categoryName = $category->name;
            
            $category->delete();
            
            Log::info('Category deleted', ['category_id' => $categoryId, 'name' => $categoryName]);
            
            return true;
        });
    }

    /**
     * Get hierarchical tree structure of all categories.
     */
    public function getHierarchicalTree(): SupportCollection
    {
        // Get all categories with their relationships
        $categories = Category::with(['children', 'images', 'primaryImage'])
            ->whereNull('parent_id')
            ->ordered()
            ->get();

        return $categories->map(function ($category) {
            return $this->buildCategoryTree($category);
        });
    }

    /**
     * Recursively build category tree with children.
     */
    private function buildCategoryTree(Category $category): array
    {
        $categoryData = [
            'id' => $category->id,
            'name' => $category->name,
            'name_persian' => $category->name_persian,
            'localized_name' => $category->localized_name,
            'description' => $category->description,
            'description_persian' => $category->description_persian,
            'code' => $category->code,
            'is_active' => $category->is_active,
            'parent_id' => $category->parent_id,
            'default_gold_purity' => $category->default_gold_purity,
            'formatted_gold_purity' => $category->formatted_gold_purity,
            'image_path' => $category->image_path,
            'image_url' => $category->image_url,
            'sort_order' => $category->sort_order,
            'specifications' => $category->specifications,
            'item_count' => $category->item_count,
            'has_children' => $category->has_children,
            'children' => [],
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];

        // Recursively add children
        if ($category->children->isNotEmpty()) {
            $categoryData['children'] = $category->children->map(function ($child) {
                return $this->buildCategoryTree($child);
            })->toArray();
        }

        return $categoryData;
    }

    /**
     * Reorder categories based on provided order data.
     */
    public function reorderCategories(array $orderData): bool
    {
        return DB::transaction(function () use ($orderData) {
            foreach ($orderData as $item) {
                Category::where('id', $item['id'])
                    ->update([
                        'sort_order' => $item['sort_order'],
                        'parent_id' => $item['parent_id'] ?? null,
                    ]);
            }

            Log::info('Categories reordered', ['count' => count($orderData)]);
            
            return true;
        });
    }

    /**
     * Validate category hierarchy to prevent circular references.
     */
    public function validateHierarchy(?int $categoryId, ?int $parentId): bool
    {
        if (!$parentId) {
            return true; // No parent, no circular reference possible
        }

        if ($categoryId && $categoryId === $parentId) {
            throw new \Exception('A category cannot be its own parent');
        }

        // Check for circular reference by traversing up the parent chain
        $currentParent = Category::find($parentId);
        $visited = [];

        while ($currentParent) {
            // If we encounter the category we're trying to update, it's a circular reference
            if ($categoryId && $currentParent->id === $categoryId) {
                throw new \Exception('This would create a circular reference');
            }

            // Prevent infinite loops in case of existing circular references
            if (in_array($currentParent->id, $visited)) {
                throw new \Exception('Circular reference detected in existing hierarchy');
            }

            $visited[] = $currentParent->id;
            $currentParent = $currentParent->parent;
        }

        return true;
    }

    /**
     * Get the next sort order for a given parent.
     */
    private function getNextSortOrder(?int $parentId): int
    {
        $maxOrder = Category::where('parent_id', $parentId)
            ->max('sort_order');

        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Get categories formatted for dropdown selection.
     */
    public function getCategoriesForSelect(?int $excludeId = null): SupportCollection
    {
        $query = Category::active()
            ->with(['primaryImage'])
            ->ordered();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->localized_name,
                'code' => $category->code,
                'image_url' => $category->image_url,
                'parent_id' => $category->parent_id,
                'level' => $this->getCategoryLevel($category),
                'formatted_name' => $this->getFormattedCategoryName($category),
            ];
        });
    }

    /**
     * Get the level/depth of a category in the hierarchy.
     */
    private function getCategoryLevel(Category $category): int
    {
        $level = 0;
        $current = $category->parent;

        while ($current) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    /**
     * Get formatted category name with indentation for hierarchy display.
     */
    private function getFormattedCategoryName(Category $category): string
    {
        $level = $this->getCategoryLevel($category);
        $indent = str_repeat('— ', $level);
        
        return $indent . $category->localized_name;
    }

    /**
     * Get main categories (root level categories).
     */
    public function getMainCategories(): Collection
    {
        return Category::active()
            ->root()
            ->with(['primaryImage'])
            ->ordered()
            ->get();
    }

    /**
     * Get subcategories for a given parent category.
     */
    public function getSubcategories(int $parentId): Collection
    {
        return Category::active()
            ->where('parent_id', $parentId)
            ->with(['primaryImage'])
            ->ordered()
            ->get();
    }

    /**
     * Search categories by name (both English and Persian).
     */
    public function searchCategories(string $query): Collection
    {
        return Category::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('name_persian', 'LIKE', "%{$query}%")
                  ->orWhere('code', 'LIKE', "%{$query}%");
            })
            ->with(['parent', 'primaryImage'])
            ->ordered()
            ->get();
    }

    /**
     * Get category path (breadcrumb) for a given category.
     */
    public function getCategoryPath(Category $category): array
    {
        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->localized_name,
                'code' => $current->code,
            ]);
            $current = $current->parent;
        }

        return $path;
    }
}