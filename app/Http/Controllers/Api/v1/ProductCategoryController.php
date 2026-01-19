<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Product\Models\Category;

/**
 * Product Category Controller for V1 API
 *
 * Handles product categories for organizing products.
 * Categories form a hierarchy with parent/child relationships.
 */
class ProductCategoryController extends BaseResourceController
{
    protected string $modelClass = Category::class;

    protected array $searchableFields = [
        'name',
        'complete_name',
    ];

    protected array $filterableFields = [
        'id',
        'parent_id',
        'company_id',
        'removal_strategy_id',
        'property_valuation',
        'property_cost_method',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'complete_name',
        'parent_path',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'parent',
        'children',
        'company',
        'removalStrategy',
        'products',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:products_categories,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'removal_strategy_id' => 'nullable|integer|exists:inventories_removal_strategies,id',
            'property_valuation' => 'nullable|string|in:manual_periodic,real_time',
            'property_cost_method' => 'nullable|string|in:standard,fifo,average',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|integer|exists:products_categories,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'removal_strategy_id' => 'nullable|integer|exists:inventories_removal_strategies,id',
            'property_valuation' => 'nullable|string|in:manual_periodic,real_time',
            'property_cost_method' => 'nullable|string|in:standard,fifo,average',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Update complete_name based on parent hierarchy
        $this->updateCompleteName($model);
    }

    protected function afterUpdate(Model $model, Request $request): void
    {
        // Update complete_name if parent changed
        if ($request->has('parent_id') || $request->has('name')) {
            $this->updateCompleteName($model);
            // Update children's complete_name
            $this->updateChildrenCompleteName($model);
        }
    }

    /**
     * Update the complete_name field based on parent hierarchy
     */
    protected function updateCompleteName(Category $category): void
    {
        $names = [$category->name];
        $parent = $category->parent;

        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }

        $category->updateQuietly([
            'complete_name' => implode(' / ', $names),
            'parent_path' => $this->buildParentPath($category),
        ]);
    }

    /**
     * Build parent path string
     */
    protected function buildParentPath(Category $category): string
    {
        $ids = [$category->id];
        $parent = $category->parent;

        while ($parent) {
            array_unshift($ids, $parent->id);
            $parent = $parent->parent;
        }

        return '/' . implode('/', $ids) . '/';
    }

    /**
     * Update children's complete_name recursively
     */
    protected function updateChildrenCompleteName(Category $category): void
    {
        foreach ($category->children as $child) {
            $this->updateCompleteName($child);
            $this->updateChildrenCompleteName($child);
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['depth'] = $model->parent_path ? substr_count($model->parent_path, '/') - 1 : 0;
        $data['has_children'] = $model->children()->exists();
        $data['product_count'] = $model->products()->count();

        return $data;
    }

    /**
     * GET /product-categories/tree - Get full category tree
     */
    public function tree(): JsonResponse
    {
        $categories = Category::with(['children' => function ($query) {
            $query->orderBy('name');
        }])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $tree = $this->buildTree($categories);

        return $this->success($tree, 'Category tree retrieved');
    }

    /**
     * Build tree structure from categories
     */
    protected function buildTree($categories): array
    {
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'complete_name' => $category->complete_name,
                'product_count' => $category->products()->count(),
                'children' => $category->children->isNotEmpty()
                    ? $this->buildTree($category->children)
                    : [],
            ];
        })->toArray();
    }
}
