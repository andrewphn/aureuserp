<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Services\GeminiProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

                        // Barcode - only update if empty (use SKU or barcode from AI)
                        if (empty($currentData['barcode'])) {
                            if (!empty($data['barcode'])) {
                                $updates['barcode'] = $data['barcode'];
                            } elseif (!empty($data['sku'])) {
                                $updates['barcode'] = $data['sku'];
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
            Action::make('aiIdentifyPhoto')
                ->label('AI from Photo')
                ->icon('heroicon-o-camera')
                ->color('info')
                ->form([
                    FileUpload::make('product_image')
                        ->label('Product Photo')
                        ->image()
                        ->imageEditor()
                        ->required()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240) // 10MB
                        ->helperText('Take a photo or upload an image of the product'),
                    Textarea::make('additional_context')
                        ->label('Additional Context (optional)')
                        ->placeholder('e.g., "16oz bottle", "for outdoor use", "bought from Home Depot"')
                        ->rows(2)
                        ->helperText('Any additional info to help identify the product'),
                ])
                ->modalHeading('Identify Product from Photo')
                ->modalDescription('Upload a photo of the product. AI will identify it and populate the form with product details.')
                ->modalSubmitActionLabel('Identify & Populate')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function (array $data, Product $record) {
                    try {
                        // Get the uploaded file
                        $imagePath = $data['product_image'] ?? null;
                        if (empty($imagePath)) {
                            Notification::make()
                                ->title('No Image')
                                ->body('Please upload a product image.')
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Get full path - handle both string and array cases
                        if (is_array($imagePath)) {
                            $imagePath = reset($imagePath);
                        }
                        $fullPath = storage_path('app/public/' . $imagePath);

                        if (!file_exists($fullPath)) {
                            Notification::make()
                                ->title('Image Not Found')
                                ->body('Could not find the uploaded image.')
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Read and encode the image
                        $imageData = file_get_contents($fullPath);
                        $base64Image = base64_encode($imageData);
                        $mimeType = mime_content_type($fullPath);

                        // Call AI service
                        $service = new GeminiProductService();
                        $aiData = $service->generateProductDetailsFromImage(
                            $base64Image,
                            $mimeType,
                            $data['additional_context'] ?? null
                        );

                        // Clean up uploaded file
                        @unlink($fullPath);

                        if (isset($aiData['error'])) {
                            Notification::make()
                                ->title('AI Identification Failed')
                                ->body($aiData['error'])
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Get current form data WITHOUT triggering validation
                        $currentData = $this->form->getRawState();
                        $updates = [];

                        // If AI identified the product, update the name
                        if (!empty($aiData['identified_product_name'])) {
                            $updates['name'] = $aiData['identified_product_name'];
                        }

                        // Build rich description with technical specs and source
                        if (!empty($aiData['description'])) {
                            $fullDescription = $aiData['description'];

                            if (!empty($aiData['technical_specs'])) {
                                $fullDescription .= "\n<p><strong>Technical Specs:</strong> " . htmlspecialchars($aiData['technical_specs']) . "</p>";
                            }

                            if (!empty($aiData['brand'])) {
                                $fullDescription .= "\n<p><strong>Brand:</strong> " . htmlspecialchars($aiData['brand']) . "</p>";
                            }

                            if (!empty($aiData['source_url'])) {
                                $fullDescription .= "\n<p><strong>Source:</strong> <a href=\"" . htmlspecialchars($aiData['source_url']) . "\" target=\"_blank\">" . htmlspecialchars($aiData['source_url']) . "</a></p>";
                            }

                            $updates['description'] = $fullDescription;
                        }

                        // Barcode - only update if empty (use barcode or SKU from AI)
                        if (empty($currentData['barcode'])) {
                            if (!empty($aiData['barcode'])) {
                                $updates['barcode'] = $aiData['barcode'];
                            } elseif (!empty($aiData['sku'])) {
                                $updates['barcode'] = $aiData['sku'];
                            }
                        }

                        // Always update price/cost/weight/volume with AI estimates
                        if (!empty($aiData['suggested_price']) && $aiData['suggested_price'] > 0) {
                            $updates['price'] = $aiData['suggested_price'];
                        }

                        if (!empty($aiData['suggested_cost']) && $aiData['suggested_cost'] > 0) {
                            $updates['cost'] = $aiData['suggested_cost'];
                        }

                        if (!empty($aiData['weight']) && $aiData['weight'] > 0) {
                            $updates['weight'] = $aiData['weight'];
                        }

                        if (!empty($aiData['volume']) && $aiData['volume'] > 0) {
                            $updates['volume'] = $aiData['volume'];
                        }

                        // Handle tags
                        if (!empty($aiData['tags']) && is_array($aiData['tags'])) {
                            $tagIds = [];
                            foreach ($aiData['tags'] as $tagName) {
                                $tagName = trim($tagName);
                                if (empty($tagName)) continue;
                                $tag = Tag::firstOrCreate(['name' => $tagName], ['name' => $tagName]);
                                $tagIds[] = $tag->id;
                            }

                            if (!empty($tagIds)) {
                                $existingTagIds = $currentData['tags'] ?? [];
                                $updates['tags'] = array_unique(array_merge($existingTagIds, $tagIds));
                            }
                        }

                        if (!empty($updates)) {
                            $this->form->fill(array_merge($currentData, $updates));

                            $identifiedName = $aiData['identified_product_name'] ?? 'Unknown product';
                            Notification::make()
                                ->title('Product Identified!')
                                ->body("Identified as: {$identifiedName}. Review the details and click Save when ready.")
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No Information Found')
                                ->body('AI could not identify or find information for this product.')
                                ->warning()
                                ->persistent()
                                ->send();
                        }

                        Log::info('AI Photo Identify completed', [
                            'product_id' => $record->id,
                            'identified_as' => $aiData['identified_product_name'] ?? 'unknown',
                            'updates' => array_keys($updates),
                        ]);

                    } catch (\Exception $e) {
                        Log::error('AI Photo Identify error: ' . $e->getMessage(), [
                            'product_id' => $record->id,
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('AI Identification Error')
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
