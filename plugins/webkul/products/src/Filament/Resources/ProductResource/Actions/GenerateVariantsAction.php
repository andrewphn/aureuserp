<?php

namespace Webkul\Product\Filament\Resources\ProductResource\Actions;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttribute;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ManageAttributes;

class GenerateVariantsAction extends Action
{
    use CanCustomizeProcess;

    protected Model | Closure | null $record;

    public static function getDefaultName(): ?string
    {
        return 'products.generate.variants';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon('heroicon-o-cube')
            ->label(__('products::filament/resources/product/actions/generate-variants.label'))
            ->color('primary')
            ->action(function (ManageAttributes $livewire) {
                $this->record = $livewire->getRecord();

                $this->record->variants()->delete();

                $this->generateVariants();
            })
            ->hidden(fn(ManageAttributes $livewire) => $livewire->getRecord()->attributes->isEmpty());
    }

    protected function generateVariants(): void
    {
        try {
            $attributes = $this->record->attributes()
                ->with(['attribute', 'options'])
                ->get();

            if ($attributes->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title(__('products::filament/resources/product/actions/generate-variants.notification.empty.title'))
                    ->body(__('products::filament/resources/product/actions/generate-variants.notification.empty.body'))
                    ->send();

                return;
            }

            $combinations = $this->generateCombinations($attributes);

            $this->createVariants($combinations);

            Notification::make()
                ->success()
                ->title(__('products::filament/resources/product/actions/generate-variants.notification.success.title'))
                ->body(__('products::filament/resources/product/actions/generate-variants.notification.success.body'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('products::filament/resources/product/actions/generate-variants.notification.error.title'))
                ->body(__('products::filament/resources/product/actions/generate-variants.notification.error.body'))
                ->send();
        }
    }

    protected function generateCombinations(Collection $attributes): array
    {
        $arrays = $attributes->map(function (ProductAttribute $attribute) {
            return $attribute->options->map(function ($option) use ($attribute) {
                return [
                    'attribute_id' => $attribute->attribute_id,
                    'product_attribute_id' => $attribute->id,
                    'attribute_option_id' => $option->id,
                    'value' => $option->name,
                ];
            })->toArray();
        })->toArray();

        return $this->cartesianProduct($arrays);
    }

    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $array) {
            $append = [];

            foreach ($result as $product) {
                foreach ($array as $item) {
                    $append[] = array_merge($product, [$item]);
                }
            }

            $result = $append;
        }

        return $result;
    }

    protected function createVariants(array $combinations): void
    {
        $user = Auth::user();

        $parentProduct = $this->record->load([
            'tags',
            'supplierInformation',
            'priceRuleItems'
        ]);

        foreach ($combinations as $combination) {
            $variantName = $this->record->name . ' - ' . collect($combination)
                ->pluck('value')
                ->join(' / ');

            $variant = Product::firstOrNew([
                'parent_id' => $this->record->id,
                'name' => $variantName,
            ]);

            if (! $variant->exists) {
                $variant->fill([
                    'type'                 => $parentProduct->type,
                    'enable_sales'         => $parentProduct->enable_sales,
                    'enable_purchase'      => $parentProduct->enable_purchase,
                    'price'                => $parentProduct->price,
                    'cost'                 => $parentProduct->cost,
                    'volume'               => $parentProduct->volume,
                    'weight'               => $parentProduct->weight,
                    'description'          => $parentProduct->description,
                    'description_purchase' => $parentProduct->description_purchase,
                    'description_sale'     => $parentProduct->description_sale,
                    'barcode'              => null,
                    'reference'            => $parentProduct->reference . '-' . strtolower(str_replace(' ', '-', $variantName)),
                    'uom_id'               => $parentProduct->uom_id,
                    'uom_po_id'            => $parentProduct->uom_po_id,
                    'category_id'          => $parentProduct->category_id,
                    'company_id'           => $parentProduct->company_id,
                    'images'               => $parentProduct->images,
                    'creator_id'           => $user->id,
                ]);

                $variant->save();

                $variant->tags()->sync($parentProduct->tags->pluck('id'));

                foreach (
                    $parentProduct->supplierInformation
                    as $supplierInfo
                ) {
                    $variant->supplierInformation()->create([
                        'supplier_id'   => $supplierInfo->supplier_id,
                        'min_quantity'  => $supplierInfo->min_quantity,
                        'price'         => $supplierInfo->price,
                        'lead_time'     => $supplierInfo->lead_time,
                        'creator_id'    => $user->id,
                    ]);
                }

                foreach (
                    $parentProduct->priceRuleItems
                    as $priceRule
                ) {
                    $variant->priceRuleItems()->create([
                        'price_rule_id' => $priceRule->price_rule_id,
                        'min_quantity'  => $priceRule->min_quantity,
                        'price'         => $priceRule->price,
                        'creator_id'    => $user->id,
                    ]);
                }
            }
        }
    }
}
