<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Services\GeminiProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Tag;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;
use Webkul\Product\Filament\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;

/**
 * Edit Product class
 *
 * @see \Filament\Resources\Resource
 */
class EditProduct extends BaseEditProduct
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge([
            Action::make('aiPopulate')
                ->label('AI Populate')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Generate Product Details with AI')
                ->modalDescription('AI will search the web and generate product details based on the product name. This may take up to 30 seconds. You can review and edit before saving.')
                ->modalSubmitActionLabel('Generate')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function (Product $record) {
                    try {
                        // Get current form data WITHOUT triggering validation
                        $currentData = $this->form->getRawState();

                        // Use name from FORM (what user typed), not database record
                        $productName = $currentData['name'] ?? $record->name;

                        // Description may be array from RichEditor - extract string value
                        $existingDescription = $currentData['description'] ?? $record->description;
                        if (is_array($existingDescription)) {
                            $existingDescription = $existingDescription['content'] ?? ($existingDescription[0] ?? null);
                        }
                        $existingDescription = is_string($existingDescription) ? $existingDescription : null;

                        if (empty($productName)) {
                            Notification::make()
                                ->title('Product Name Required')
                                ->body('Please enter a product name first.')
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }

                        $service = new GeminiProductService();
                        $data = $service->generateProductDetails($productName, $existingDescription);

                        if (isset($data['error'])) {
                            Notification::make()
                                ->title('AI Generation Failed')
                                ->body($data['error'])
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Only update fields if AI returned valid data
                        $updates = [];

                        // Build rich description with technical specs and source
                        if (!empty($data['description'])) {
                            $fullDescription = $data['description'];

                            // Append technical specs if available
                            if (!empty($data['technical_specs'])) {
                                $fullDescription .= "\n<p><strong>Technical Specs:</strong> " . htmlspecialchars($data['technical_specs']) . "</p>";
                            }

                            // Append brand if available
                            if (!empty($data['brand'])) {
                                $fullDescription .= "\n<p><strong>Brand:</strong> " . htmlspecialchars($data['brand']) . "</p>";
                            }

                            // Append source URL if available
                            if (!empty($data['source_url'])) {
                                $fullDescription .= "\n<p><em>Source: <a href=\"" . htmlspecialchars($data['source_url']) . "\" target=\"_blank\">" . htmlspecialchars($data['source_url']) . "</a></em></p>";
                            }

                            $updates['description'] = $fullDescription;
                        }

                        // SKU (reference field) - only update if empty
                        if (empty($currentData['reference'])) {
                            if (!empty($data['sku'])) {
                                $updates['reference'] = $data['sku'];
                            }
                        }

                        // Barcode - only update if empty
                        if (empty($currentData['barcode'])) {
                            if (!empty($data['barcode'])) {
                                $updates['barcode'] = $data['barcode'];
                            }
                        }

                        // Always update price/cost/weight/volume with AI estimates
                        if (!empty($data['suggested_price']) && $data['suggested_price'] > 0) {
                            $updates['price'] = $data['suggested_price'];
                        }

                        if (!empty($data['suggested_cost']) && $data['suggested_cost'] > 0) {
                            $updates['cost'] = $data['suggested_cost'];
                        }

                        if (!empty($data['weight']) && $data['weight'] > 0) {
                            $updates['weight'] = $data['weight'];
                        }

                        if (!empty($data['volume']) && $data['volume'] > 0) {
                            $updates['volume'] = $data['volume'];
                        }

                        // Handle tags - find or create and get IDs
                        if (!empty($data['tags']) && is_array($data['tags'])) {
                            $tagIds = [];
                            foreach ($data['tags'] as $tagName) {
                                $tagName = trim($tagName);
                                if (empty($tagName)) continue;

                                // Find or create the tag
                                $tag = Tag::firstOrCreate(
                                    ['name' => $tagName],
                                    ['name' => $tagName]
                                );
                                $tagIds[] = $tag->id;
                            }

                            if (!empty($tagIds)) {
                                // Merge with existing tags
                                $existingTagIds = $currentData['tags'] ?? [];
                                $updates['tags'] = array_unique(array_merge($existingTagIds, $tagIds));
                            }
                        }

                        if (!empty($updates)) {
                            // Update form state without saving to database
                            $this->form->fill(array_merge($currentData, $updates));

                            Notification::make()
                                ->title('Product details generated')
                                ->body('Review the changes below and click Save when ready.')
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No updates made')
                                ->body('AI could not find additional information for this product.')
                                ->warning()
                                ->persistent()
                                ->send();
                        }

                        Log::info('AI Populate completed for product: ' . $record->name, [
                            'product_id' => $record->id,
                            'updates' => array_keys($updates),
                            'ai_data' => $data,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('AI Populate error: ' . $e->getMessage(), [
                            'product_id' => $record->id,
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('AI Generation Error')
                            ->body('An unexpected error occurred. Please try again.')
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('updateQuantity')
                ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.label'))
                ->modalHeading(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.modal-heading'))
                ->schema(fn (Product $record): array => [
                    Select::make('product_id')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.form.fields.product'))
                        ->required()
                        ->options($record->variants->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $product = Product::find($get('product_id'));

                            $set('quantity', $product?->on_hand_quantity ?? 0);
                        })
                        ->visible((bool) $record->is_configurable),
                    TextInput::make('quantity')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.form.fields.on-hand-qty'))
                        ->numeric()
                        ->maxValue(99999999999)
                        ->required()
                        ->live()
                        ->default(fn () => ! $record->is_configurable ? $record->on_hand_quantity : 0),
                ])
                ->modalSubmitActionLabel(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.modal-submit-action-label'))
                ->visible($this->getRecord()->is_storable)
                ->beforeFormFilled(function (
                    OperationSettings $operationSettings,
                    TraceabilitySettings $traceabilitySettings,
                    WarehouseSettings $warehouseSettings,
                    Product $record
                ) {
                    if (
                        $operationSettings->enable_packages
                        || $warehouseSettings->enable_locations
                        || (
                            $traceabilitySettings->enable_lots_serial_numbers
                            && $record->tracking != ProductTracking::QTY
                        )
                    ) {
                        return redirect()->to(ProductResource::getUrl('quantities', ['record' => $record]));
                    }
                })
                ->action(function (Product $record, array $data): void {
                    if (isset($data['product_id'])) {
                        $record = Product::find($data['product_id']);
                    }

                    $previousQuantity = $record->on_hand_quantity;

                    if ($previousQuantity == $data['quantity']) {
                        return;
                    }

                    $warehouse = Warehouse::first();

                    $adjustmentLocation = Location::where('type', LocationType::INVENTORY)
                        ->where('is_scrap', false)
                        ->first();

                    $currentQuantity = $data['quantity'] - $previousQuantity;

                    if ($currentQuantity < 0) {
                        $sourceLocationId = $data['location_id'] ?? $warehouse->lot_stock_location_id;

                        $destinationLocationId = $adjustmentLocation->id;
                    } else {
                        $sourceLocationId = $data['location_id'] ?? $adjustmentLocation->id;

                        $destinationLocationId = $warehouse->lot_stock_location_id;
                    }

                    $productQuantity = ProductQuantity::where('product_id', $record->id)
                        ->where('location_id', $data['location_id'] ?? $warehouse->lot_stock_location_id)
                        ->first();

                    if ($productQuantity) {
                        $productQuantity->update(['quantity' => $data['quantity']]);
                    } else {
                        $productQuantity = ProductQuantity::create([
                            'product_id'        => $record->id,
                            'company_id'        => $record->company_id,
                            'location_id'       => $data['location_id'] ?? $warehouse->lot_stock_location_id,
                            'package_id'        => $data['package_id'] ?? null,
                            'lot_id'            => $data['lot_id'] ?? null,
                            'quantity'          => $data['quantity'],
                            'reserved_quantity' => 0,
                            'incoming_at'       => now(),
                            'creator_id'        => Auth::id(),
                        ]);
                    }

                    ProductResource::createMove($productQuantity, $currentQuantity, $sourceLocationId, $destinationLocationId);
                }),
        ], parent::getHeaderActions());
    }
}
