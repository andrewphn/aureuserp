<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Product\Database\Factories\ProductFactory;
use Webkul\Product\Enums\ProductType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Product Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property mixed $type
 * @property string|null $name
 * @property string|null $service_tracking
 * @property string|null $reference
 * @property string|null $barcode
 * @property float $price
 * @property float $cost
 * @property string|null $volume
 * @property string|null $weight
 * @property string|null $description
 * @property string|null $description_purchase
 * @property string|null $description_sale
 * @property bool $enable_sales
 * @property bool $enable_purchase
 * @property bool $is_favorite
 * @property bool $is_configurable
 * @property array $images
 * @property string|null $sort
 * @property int $parent_id
 * @property int $uom_id
 * @property int $uom_po_id
 * @property int $category_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $attributes
 * @property-read \Illuminate\Database\Eloquent\Collection $attribute_values
 * @property-read \Illuminate\Database\Eloquent\Collection $variants
 * @property-read \Illuminate\Database\Eloquent\Collection $combinations
 * @property-read \Illuminate\Database\Eloquent\Collection $priceRuleItems
 * @property-read \Illuminate\Database\Eloquent\Collection $supplierInformation
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $uomPO
 * @property-read \Illuminate\Database\Eloquent\Model|null $category
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 *
 */
class Product extends Model implements Sortable
{
    use HasChatter, HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_products';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'service_tracking',
        'reference',
        'type_code',
        'reference_type_code_id',
        'barcode',
        'price',
        'cost',
        'box_cost',
        'units_per_box',
        'package_description',
        'supplier_sku',
        'volume',
        'weight',
        'description',
        'description_purchase',
        'description_sale',
        'enable_sales',
        'enable_purchase',
        'is_favorite',
        'is_configurable',
        'images',
        'sort',
        'parent_id',
        'uom_id',
        'uom_po_id',
        'category_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'type'            => ProductType::class,
        'enable_sales'    => 'boolean',
        'enable_purchase' => 'boolean',
        'is_favorite'     => 'boolean',
        'is_configurable' => 'boolean',
        'images'          => 'array',
        'cost'            => 'float',
        'price'           => 'float',
        'box_cost'        => 'float',
        'units_per_box'   => 'integer',
    ];

    protected array $logAttributes = [
        'type',
        'name',
        'service_tracking',
        'reference',
        'barcode',
        'price',
        'cost',
        'volume',
        'weight',
        'description',
        'description_purchase',
        'description_sale',
        'enable_sales',
        'enable_purchase',
        'is_favorite',
        'is_configurable',
        'parent.name'   => 'Parent',
        'category.name' => 'Category',
        'company.name'  => 'Company',
        'creator.name'  => 'Creator',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Uom
     *
     * @return BelongsTo
     */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    /**
     * Uom P O
     *
     * @return BelongsTo
     */
    public function uomPO(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    /**
     * Category
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Reference Type Code
     *
     * @return BelongsTo
     */
    public function referenceTypeCode(): BelongsTo
    {
        return $this->belongsTo(ReferenceTypeCode::class);
    }

    /**
     * Tags
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'products_product_tag', 'product_id', 'tag_id');
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
     * Attributes
     *
     * @return HasMany
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Attribute Values
     *
     * @return HasMany
     */
    public function attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id');
    }

    /**
     * Variants
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Combinations
     *
     * @return HasMany
     */
    public function combinations(): HasMany
    {
        return $this->hasMany(ProductCombination::class, 'product_id');
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
     * Supplier Information
     *
     * @return HasMany
     */
    public function supplierInformation(): HasMany
    {
        if ($this->is_configurable) {
            return $this->hasMany(ProductSupplier::class)
                ->orWhereIn('product_id', $this->variants()->pluck('id'));
        } else {
            return $this->hasMany(ProductSupplier::class);
        }
    }

    /**
     * New Factory
     *
     * @return ProductFactory
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
