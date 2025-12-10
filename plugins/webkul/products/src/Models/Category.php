<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Product\Database\Factories\CategoryFactory;
use Webkul\Security\Models\User;

/**
 * Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $full_name
 * @property string|null $parent_path
 * @property int $parent_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $children
 * @property-read \Illuminate\Database\Eloquent\Collection $products
 * @property-read \Illuminate\Database\Eloquent\Collection $priceRuleItems
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Category extends Model
{
    use HasChatter, HasFactory, HasLogActivity;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_categories';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'full_name',
        'parent_path',
        'parent_id',
        'creator_id',
    ];

    protected $logAttributes = [
        'name',
        'code',
        'full_name',
        'parent_path',
        'parent.name'  => 'Parent Category',
        'creator.name' => 'Creator',
    ];

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Children
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Products
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Price Rule Items
     *
     * @return HasMany
     */
    public function priceRuleItems(): HasMany
    {
        return $this->hasMany(PriceRuleItem::class);
    }

    /**
     * Reference Type Codes
     *
     * @return HasMany
     */
    public function referenceTypeCodes(): HasMany
    {
        return $this->hasMany(ReferenceTypeCode::class);
    }

    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($productCategory) {
            if (! static::validateNoRecursion($productCategory)) {
                throw new InvalidArgumentException('Circular reference detected in product category hierarchy');
            }

            static::handleProductCategoryData($productCategory);
        });

        static::updating(function ($productCategory) {
            if (! static::validateNoRecursion($productCategory)) {
                throw new InvalidArgumentException('Circular reference detected in product category hierarchy');
            }

            static::handleProductCategoryData($productCategory);
        });
    }

    /**
     * Validate No Recursion
     *
     * @param mixed $productCategory
     */
    protected static function validateNoRecursion($productCategory)
    {
        if (! $productCategory->parent_id) {
            return true;
        }

        if (
            $productCategory->exists
            && $productCategory->id == $productCategory->parent_id
        ) {
            return false;
        }

        $visitedIds = [$productCategory->exists ? $productCategory->id : -1];
        $currentParentId = $productCategory->parent_id;

        while ($currentParentId) {
            if (in_array($currentParentId, $visitedIds)) {
                return false;
            }

            $visitedIds[] = $currentParentId;
            $parent = static::find($currentParentId);

            if (! $parent) {
                break;
            }

            $currentParentId = $parent->parent_id;
        }

        return true;
    }

    /**
     * Handle Product Category Data
     *
     * @param mixed $productCategory
     */
    protected static function handleProductCategoryData($productCategory)
    {
        if ($productCategory->parent_id) {
            $parent = static::find($productCategory->parent_id);

            if ($parent) {
                $productCategory->parent_path = $parent->parent_path.$parent->id.'/';
            } else {
                $productCategory->parent_path = '/';
                $productCategory->parent_id = null;
            }
        } else {
            $productCategory->parent_path = '/';
        }

        $productCategory->full_name = static::getCompleteName($productCategory);
    }

    /**
     * Get Complete Name
     *
     * @param mixed $productCategory
     */
    protected static function getCompleteName($productCategory)
    {
        $names = [];
        $names[] = $productCategory->name;

        $currentProductCategory = $productCategory;

        while ($currentProductCategory->parent_id) {
            $currentProductCategory = static::find($currentProductCategory->parent_id);

            if ($currentProductCategory) {
                array_unshift($names, $currentProductCategory->name);
            } else {
                break;
            }
        }

        return implode(' / ', $names);
    }

    /**
     * New Factory
     *
     * @return CategoryFactory
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
