<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;
use Webkul\Sale\Models\Product;
use Webkul\Sale\Services\Pricing\LineTotalsCalculator;

/**
 * Attribute Helper
 *
 * Centralized helper for product attribute field generation and management.
 * Extracted from QuotationResource for reuse across schemas.
 */
class AttributeHelper
{
    private static ?array $cachedPhysical = null;
    private static ?array $cachedPricing = null;

    /**
     * Get physical characteristic attributes for cabinets (main visible columns)
     * IDs 9-16: Construction Style, Door Style, Primary Material, etc.
     */
    public static function getPhysicalAttributes(): array
    {
        if (static::$cachedPhysical !== null) {
            return static::$cachedPhysical;
        }

        $attributes = DB::table('products_attributes')
            ->whereIn('id', [9, 10, 11, 12, 13, 14, 15, 16])
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($attr) => [$attr->id => $attr->name])
            ->toArray();

        static::$cachedPhysical = $attributes;

        return $attributes;
    }

    /**
     * Get pricing configuration attributes (toggled visible by default)
     * IDs 18-20: Pricing Level, Material Category, Finish Option
     */
    public static function getPricingAttributes(): array
    {
        if (static::$cachedPricing !== null) {
            return static::$cachedPricing;
        }

        $attributes = DB::table('products_attributes')
            ->whereIn('id', [18, 19, 20])
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($attr) => [$attr->id => $attr->name])
            ->toArray();

        static::$cachedPricing = $attributes;

        return $attributes;
    }

    /**
     * Get all configurable product attributes (physical + pricing)
     */
    public static function getAllAttributes(): array
    {
        return array_merge(
            static::getPhysicalAttributes(),
            static::getPricingAttributes()
        );
    }

    /**
     * Create an attribute select field
     */
    public static function makeAttributeField(int $attributeId, string $attributeName, bool $isPricing): Select
    {
        $field = Select::make("attribute_{$attributeId}")
            ->label($attributeName)
            ->options(function (Get $get) use ($attributeId) {
                $productId = $get('product_id');
                if (!$productId) return [];

                $hasAttr = DB::table('products_product_attributes')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attributeId)
                    ->exists();
                if (!$hasAttr) return [];

                return DB::table('products_attribute_options')
                    ->where('attribute_id', $attributeId)
                    ->orderBy('sort')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->visible(function (Get $get) use ($attributeId) {
                $productId = $get('product_id');
                if (!$productId) return false;

                $product = Product::find($productId);
                if (!$product || !$product->is_configurable) return false;

                return DB::table('products_product_attributes')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attributeId)
                    ->exists();
            })
            ->live()
            ->afterStateUpdated(fn (Set $set, Get $get) => static::updatePriceWithAttributes($set, $get));

        // Add pricing-specific features
        if ($isPricing) {
            $field = $field
                ->disabled(function (Get $get) {
                    $productId = $get('product_id');
                    return !$productId;
                })
                ->hint(function (Get $get) use ($attributeId) {
                    $optionId = $get("attribute_{$attributeId}");
                    if (!$optionId) return null;

                    $option = DB::table('products_attribute_options')
                        ->where('id', $optionId)
                        ->first(['description']);

                    return $option?->description;
                })
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('primary')
                ->helperText(function (Get $get) use ($attributeId) {
                    $optionId = $get("attribute_{$attributeId}");
                    if (!$optionId) return null;

                    $option = DB::table('products_attribute_options')
                        ->where('id', $optionId)
                        ->first(['extra_price']);

                    if ($option && $option->extra_price > 0) {
                        return '+ $' . number_format($option->extra_price, 2) . ' / LF';
                    }
                    return null;
                });
        }

        return $field;
    }

    /**
     * Update price based on selected attributes
     */
    public static function updatePriceWithAttributes(Set $set, Get $get): void
    {
        $productId = $get('product_id');
        if (!$productId) {
            return;
        }

        $product = Product::withTrashed()->find($productId);
        if (!$product || !$product->is_configurable) {
            return;
        }

        $basePrice = floatval($product->price ?? 0);
        $totalAttributePrice = 0;

        $attributes = DB::table('products_product_attributes')
            ->where('product_id', $productId)
            ->pluck('attribute_id')
            ->toArray();

        $attributeSelections = [];

        foreach ($attributes as $attributeId) {
            $selectedOptionId = $get("attribute_{$attributeId}");

            if ($selectedOptionId) {
                $option = DB::table('products_attribute_options')
                    ->where('id', $selectedOptionId)
                    ->first();

                if ($option) {
                    $totalAttributePrice += floatval($option->extra_price);

                    $attribute = DB::table('products_attributes')
                        ->where('id', $attributeId)
                        ->first();

                    $attributeSelections[] = [
                        'attribute_id' => $attributeId,
                        'attribute_name' => $attribute->name ?? '',
                        'option_id' => $option->id,
                        'option_name' => $option->name,
                        'extra_price' => floatval($option->extra_price),
                    ];
                }
            }
        }

        $finalPrice = $basePrice + $totalAttributePrice;
        $set('price_unit', round($finalPrice, 2));

        $set('attribute_selections', json_encode($attributeSelections));

        $descriptionParts = [$product->name];
        foreach ($attributeSelections as $selection) {
            $descriptionParts[] = $selection['option_name'];
        }
        $customerDescription = implode(' - ', $descriptionParts);
        $set('name', $customerDescription);

        ProductRepeaterSchema::calculateLineTotals($set, $get);
    }

    /**
     * Clear cached attributes (useful for testing)
     */
    public static function clearCache(): void
    {
        static::$cachedPhysical = null;
        static::$cachedPricing = null;
    }
}
