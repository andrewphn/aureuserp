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
use Webkul\Product\Models\Attribute;
use Webkul\Product\Models\AttributeOption;
use Webkul\Product\Models\ProductAttribute;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Product as InventoryProduct;
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
                ->action(function (InventoryProduct $record) {
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

                        // Gather all user-entered form data to help Gemini identify the product
                        $searchContext = [
                            'source_url' => $currentData['source_url'] ?? null,
                            'barcode' => $currentData['barcode'] ?? null,
                            'supplier_sku' => $currentData['supplier_sku'] ?? null,
                            'reference' => $currentData['reference'] ?? null,
                        ];
                        // Get user-entered source URL for image extraction
                        $userSourceUrl = $currentData['source_url'] ?? null;

                        $service = new GeminiProductService();
                        // Pass all form context to Gemini for better product identification
                        $data = $service->generateProductDetails($productName, $existingDescription, $searchContext);

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

                        // Set both price and cost to the COST value (user can override with markup later)
                        $costValue = $data['suggested_cost'] ?? $data['suggested_price'] ?? 0;
                        if ($costValue > 0) {
                            $updates['price'] = $costValue;
                            $updates['cost'] = $costValue;
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

                        // Category and Reference Type Code from AI
                        if (!empty($data['category_id']) && $data['category_id'] > 0) {
                            $updates['category_id'] = $data['category_id'];
                        }
                        if (!empty($data['reference_type_code_id']) && $data['reference_type_code_id'] > 0) {
                            $updates['reference_type_code_id'] = $data['reference_type_code_id'];
                        }

                        // Download product image - prioritize user's source_url over AI's
                        $imageDownloaded = false;

                        // Use user-entered source_url first, then AI's source_url as fallback
                        $sourceUrlForImage = $userSourceUrl ?: ($data['source_url'] ?? null);

                        // PRIORITY 1: If source_url is Richelieu, extract image directly (highest quality)
                        if (!empty($sourceUrlForImage) && str_contains($sourceUrlForImage, 'richelieu.com')) {
                            Log::info('AI Populate - Extracting image from Richelieu source', ['source_url' => $sourceUrlForImage]);
                            $richelieuImageUrl = GeminiProductService::extractRichelieuImageUrl($sourceUrlForImage);
                            if ($richelieuImageUrl) {
                                $downloadedImage = GeminiProductService::downloadProductImage(
                                    $richelieuImageUrl,
                                    $data['identified_product_name'] ?? $productName
                                );
                                if ($downloadedImage) {
                                    $downloadedFullPath = storage_path('app/public/products/images/' . $downloadedImage);
                                    if (file_exists($downloadedFullPath)) {
                                        try {
                                            $record->addMedia($downloadedFullPath)
                                                ->toMediaCollection('product-images');
                                            Log::info('AI Populate - Added Richelieu image to Spatie', [
                                                'product_id' => $record->id,
                                                'image_url' => $richelieuImageUrl,
                                            ]);
                                            $imageDownloaded = true;
                                        } catch (\Exception $e) {
                                            Log::error('AI Populate - Failed to add Richelieu image', [
                                                'product_id' => $record->id,
                                                'error' => $e->getMessage(),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        // PRIORITY 2: If source_url is Amazon, extract image from Amazon
                        if (!$imageDownloaded && !empty($sourceUrlForImage) && str_contains($sourceUrlForImage, 'amazon.com')) {
                            Log::info('AI Populate - Extracting image from Amazon source', ['source_url' => $sourceUrlForImage]);
                            $amazonImageUrl = GeminiProductService::extractAmazonImageUrl($sourceUrlForImage);
                            if ($amazonImageUrl) {
                                $downloadedImage = GeminiProductService::downloadProductImage(
                                    $amazonImageUrl,
                                    $data['identified_product_name'] ?? $productName
                                );
                                if ($downloadedImage) {
                                    $downloadedFullPath = storage_path('app/public/products/images/' . $downloadedImage);
                                    if (file_exists($downloadedFullPath)) {
                                        try {
                                            $record->addMedia($downloadedFullPath)
                                                ->toMediaCollection('product-images');
                                            Log::info('AI Populate - Added Amazon image to Spatie', [
                                                'product_id' => $record->id,
                                                'image_url' => $amazonImageUrl,
                                            ]);
                                            $imageDownloaded = true;
                                        } catch (\Exception $e) {
                                            Log::error('AI Populate - Failed to add Amazon image', [
                                                'product_id' => $record->id,
                                                'error' => $e->getMessage(),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        // PRIORITY 3: Only use AI image if source is NOT a known vendor (Richelieu, Amazon)
                        // If source is a known vendor but extraction failed, don't fallback to AI's random image
                        $isKnownVendorSource = !empty($sourceUrlForImage) && (
                            str_contains($sourceUrlForImage, 'richelieu.com') ||
                            str_contains($sourceUrlForImage, 'amazon.com')
                        );
                        if (!$imageDownloaded && !empty($data['image_url']) && !$isKnownVendorSource) {
                            Log::info('AI Populate - Using AI-provided image URL', ['image_url' => $data['image_url']]);
                            $downloadedImage = GeminiProductService::downloadProductImage(
                                $data['image_url'],
                                $data['identified_product_name'] ?? $productName
                            );
                            if ($downloadedImage) {
                                $downloadedFullPath = storage_path('app/public/products/images/' . $downloadedImage);
                                if (file_exists($downloadedFullPath)) {
                                    try {
                                        $record->addMedia($downloadedFullPath)
                                            ->toMediaCollection('product-images');
                                        Log::info('AI Populate - Added AI image to Spatie media', [
                                            'product_id' => $record->id,
                                            'image_path' => $downloadedFullPath,
                                        ]);
                                        $imageDownloaded = true;
                                    } catch (\Exception $e) {
                                        Log::error('AI Populate - Failed to add AI image to Spatie', [
                                            'product_id' => $record->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                        } elseif (!$imageDownloaded && $isKnownVendorSource) {
                            Log::info('AI Populate - Skipping AI image (known vendor source but extraction failed)', [
                                'source_url' => $sourceUrlForImage,
                            ]);
                        }

                        // Process suggested attributes from AI
                        $attributesCreated = 0;
                        if (!empty($data['suggested_attributes']) && is_array($data['suggested_attributes'])) {
                            Log::info('AI Populate - Processing suggested attributes', [
                                'product_id' => $record->id,
                                'attributes' => $data['suggested_attributes'],
                            ]);

                            foreach ($data['suggested_attributes'] as $suggestedAttr) {
                                try {
                                    // Find the attribute by ID or name
                                    $attribute = null;
                                    if (!empty($suggestedAttr['attribute_id'])) {
                                        $attribute = Attribute::find($suggestedAttr['attribute_id']);
                                    }
                                    if (!$attribute && !empty($suggestedAttr['attribute_name'])) {
                                        $attribute = Attribute::where('name', $suggestedAttr['attribute_name'])->first();
                                    }

                                    if (!$attribute) {
                                        Log::warning('AI Populate - Attribute not found', [
                                            'suggested' => $suggestedAttr,
                                        ]);
                                        continue;
                                    }

                                    // Check if product already has this attribute
                                    $existingProductAttr = ProductAttribute::where('product_id', $record->id)
                                        ->where('attribute_id', $attribute->id)
                                        ->first();

                                    if ($existingProductAttr) {
                                        Log::info('AI Populate - Product already has this attribute', [
                                            'product_id' => $record->id,
                                            'attribute_id' => $attribute->id,
                                            'attribute_name' => $attribute->name,
                                        ]);
                                        continue;
                                    }

                                    // Create ProductAttribute record
                                    $productAttribute = ProductAttribute::create([
                                        'product_id' => $record->id,
                                        'attribute_id' => $attribute->id,
                                        'creator_id' => Auth::id(),
                                    ]);

                                    // Create ProductAttributeValue based on attribute type
                                    if ($attribute->isNumeric()) {
                                        // For NUMBER/DIMENSION types, parse the numeric value
                                        $numericValue = null;
                                        $valueString = $suggestedAttr['value'] ?? $suggestedAttr['option_name'] ?? null;
                                        if ($valueString !== null) {
                                            // Extract numeric value from string like "21 inch" or "533mm"
                                            if (preg_match('/[\d.]+/', (string) $valueString, $matches)) {
                                                $numericValue = (float) $matches[0];
                                            }
                                        }

                                        if ($numericValue !== null) {
                                            ProductAttributeValue::create([
                                                'product_id' => $record->id,
                                                'attribute_id' => $attribute->id,
                                                'product_attribute_id' => $productAttribute->id,
                                                'numeric_value' => $numericValue,
                                                'extra_price' => 0,
                                            ]);
                                            $attributesCreated++;
                                            Log::info('AI Populate - Created numeric attribute value', [
                                                'product_id' => $record->id,
                                                'attribute_name' => $attribute->name,
                                                'numeric_value' => $numericValue,
                                            ]);
                                        }
                                    } else {
                                        // For SELECT/RADIO/COLOR types, find or create the option
                                        $optionName = $suggestedAttr['option_name'] ?? $suggestedAttr['value'] ?? null;
                                        if (!empty($optionName)) {
                                            $option = AttributeOption::where('attribute_id', $attribute->id)
                                                ->where('name', $optionName)
                                                ->first();

                                            if (!$option) {
                                                // Create new option
                                                $option = AttributeOption::create([
                                                    'attribute_id' => $attribute->id,
                                                    'name' => $optionName,
                                                    'extra_price' => 0,
                                                ]);
                                                Log::info('AI Populate - Created new attribute option', [
                                                    'attribute_id' => $attribute->id,
                                                    'option_name' => $optionName,
                                                ]);
                                            }

                                            ProductAttributeValue::create([
                                                'product_id' => $record->id,
                                                'attribute_id' => $attribute->id,
                                                'product_attribute_id' => $productAttribute->id,
                                                'attribute_option_id' => $option->id,
                                                'extra_price' => $option->extra_price ?? 0,
                                            ]);
                                            $attributesCreated++;
                                            Log::info('AI Populate - Created option attribute value', [
                                                'product_id' => $record->id,
                                                'attribute_name' => $attribute->name,
                                                'option_name' => $optionName,
                                            ]);
                                        }
                                    }
                                } catch (\Exception $attrException) {
                                    Log::error('AI Populate - Error creating attribute', [
                                        'product_id' => $record->id,
                                        'suggested' => $suggestedAttr,
                                        'error' => $attrException->getMessage(),
                                    ]);
                                }
                            }

                            if ($attributesCreated > 0) {
                                Log::info('AI Populate - Successfully created attributes', [
                                    'product_id' => $record->id,
                                    'count' => $attributesCreated,
                                ]);
                            }
                        }

                        if (!empty($updates) || $attributesCreated > 0) {
                            // Update form state without saving to database
                            if (!empty($updates)) {
                                $this->form->fill(array_merge($currentData, $updates));
                            }

                            $successBody = 'Review the changes below and click Save when ready.';
                            if ($attributesCreated > 0) {
                                $successBody .= " {$attributesCreated} product attribute(s) added.";
                            }

                            Notification::make()
                                ->title('Product details generated')
                                ->body($successBody)
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
                        ->disk('public')
                        ->directory('products/images')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240) // 10MB
                        ->helperText('Take a photo or upload an image of the product'),
                    \Filament\Forms\Components\TextInput::make('quantity')
                        ->label('Quantity on Hand')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->helperText('How many do you have in stock?'),
                    Textarea::make('additional_context')
                        ->label('Additional Context (optional)')
                        ->placeholder('e.g., "16oz bottle", "for outdoor use", "bought from Home Depot"')
                        ->rows(2)
                        ->helperText('Any additional info to help identify the product'),
                ])
                ->modalHeading('Identify Product from Photo')
                ->modalDescription('Upload a photo and enter quantity. AI will identify the product and populate all details.')
                ->modalSubmitActionLabel('Identify & Populate')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function (array $data, InventoryProduct $record) {
                    try {
                        // Get the uploaded file path
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

                        // Handle array or string path
                        if (is_array($imagePath)) {
                            $imagePath = reset($imagePath);
                        }

                        // Try multiple possible file locations
                        $possiblePaths = [
                            storage_path('app/public/' . $imagePath),
                            storage_path('app/public/products/images/' . basename($imagePath)),
                            storage_path('app/livewire-tmp/' . $imagePath),
                            storage_path('app/' . $imagePath),
                        ];

                        $fullPath = null;
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $fullPath = $path;
                                break;
                            }
                        }

                        if (!$fullPath) {
                            Log::error('AI Photo: Image not found', [
                                'imagePath' => $imagePath,
                                'tried' => $possiblePaths,
                            ]);
                            Notification::make()
                                ->title('Image Not Found')
                                ->body('Could not find the uploaded image. Path: ' . $imagePath)
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Read and encode the image for AI
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

                        // Move image to permanent location if it's in temp
                        // Use 'products/images/' directory to match FileUpload component configuration
                        $permanentPath = 'products/images/' . basename($imagePath);
                        $permanentFullPath = storage_path('app/public/' . $permanentPath);

                        if ($fullPath !== $permanentFullPath) {
                            // Ensure directory exists
                            $dir = dirname($permanentFullPath);
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            // Move or copy to permanent location
                            if (!file_exists($permanentFullPath)) {
                                copy($fullPath, $permanentFullPath);
                            }
                            // Clean up temp file
                            if (strpos($fullPath, 'livewire-tmp') !== false) {
                                @unlink($fullPath);
                            }
                        }

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

                        // Set both price and cost to the COST value (user can override with markup later)
                        $costValue = $aiData['suggested_cost'] ?? $aiData['suggested_price'] ?? 0;
                        if ($costValue > 0) {
                            $updates['price'] = $costValue;
                            $updates['cost'] = $costValue;
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

                        // Category and Reference Type Code from AI
                        if (!empty($aiData['category_id']) && $aiData['category_id'] > 0) {
                            $updates['category_id'] = $aiData['category_id'];
                        }
                        if (!empty($aiData['reference_type_code_id']) && $aiData['reference_type_code_id'] > 0) {
                            $updates['reference_type_code_id'] = $aiData['reference_type_code_id'];
                        }

                        // Add uploaded image to Spatie media library
                        if (file_exists($permanentFullPath)) {
                            try {
                                $record->addMedia($permanentFullPath)
                                    ->toMediaCollection('product-images');
                                Log::info('AI Photo - Added uploaded image to Spatie media', [
                                    'product_id' => $record->id,
                                    'image_path' => $permanentFullPath,
                                ]);
                            } catch (\Exception $e) {
                                Log::error('AI Photo - Failed to add uploaded image to Spatie', [
                                    'product_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // Also download AI-suggested product image if available
                        if (!empty($aiData['image_url'])) {
                            $downloadedImage = GeminiProductService::downloadProductImage(
                                $aiData['image_url'],
                                $aiData['identified_product_name'] ?? null
                            );
                            if ($downloadedImage) {
                                $downloadedFullPath = storage_path('app/public/products/images/' . $downloadedImage);
                                if (file_exists($downloadedFullPath)) {
                                    try {
                                        $record->addMedia($downloadedFullPath)
                                            ->toMediaCollection('product-images');
                                        Log::info('AI Photo - Added AI-suggested image to Spatie media', [
                                            'product_id' => $record->id,
                                            'image_path' => $downloadedFullPath,
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error('AI Photo - Failed to add AI-suggested image to Spatie', [
                                            'product_id' => $record->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                        }

                        // Process suggested attributes from AI (same logic as aiPopulate)
                        $attributesCreated = 0;
                        if (!empty($aiData['suggested_attributes']) && is_array($aiData['suggested_attributes'])) {
                            Log::info('AI Photo - Processing suggested attributes', [
                                'product_id' => $record->id,
                                'attributes' => $aiData['suggested_attributes'],
                            ]);

                            foreach ($aiData['suggested_attributes'] as $suggestedAttr) {
                                try {
                                    // Find the attribute by ID or name
                                    $attribute = null;
                                    if (!empty($suggestedAttr['attribute_id'])) {
                                        $attribute = Attribute::find($suggestedAttr['attribute_id']);
                                    }
                                    if (!$attribute && !empty($suggestedAttr['attribute_name'])) {
                                        $attribute = Attribute::where('name', $suggestedAttr['attribute_name'])->first();
                                    }

                                    if (!$attribute) {
                                        continue;
                                    }

                                    // Check if product already has this attribute
                                    $existingProductAttr = ProductAttribute::where('product_id', $record->id)
                                        ->where('attribute_id', $attribute->id)
                                        ->first();

                                    if ($existingProductAttr) {
                                        continue;
                                    }

                                    // Create ProductAttribute record
                                    $productAttribute = ProductAttribute::create([
                                        'product_id' => $record->id,
                                        'attribute_id' => $attribute->id,
                                        'creator_id' => Auth::id(),
                                    ]);

                                    // Create ProductAttributeValue based on attribute type
                                    if ($attribute->isNumeric()) {
                                        $numericValue = null;
                                        $valueString = $suggestedAttr['value'] ?? $suggestedAttr['option_name'] ?? null;
                                        if ($valueString !== null) {
                                            if (preg_match('/[\d.]+/', (string) $valueString, $matches)) {
                                                $numericValue = (float) $matches[0];
                                            }
                                        }

                                        if ($numericValue !== null) {
                                            ProductAttributeValue::create([
                                                'product_id' => $record->id,
                                                'attribute_id' => $attribute->id,
                                                'product_attribute_id' => $productAttribute->id,
                                                'numeric_value' => $numericValue,
                                                'extra_price' => 0,
                                            ]);
                                            $attributesCreated++;
                                        }
                                    } else {
                                        $optionName = $suggestedAttr['option_name'] ?? $suggestedAttr['value'] ?? null;
                                        if (!empty($optionName)) {
                                            $option = AttributeOption::where('attribute_id', $attribute->id)
                                                ->where('name', $optionName)
                                                ->first();

                                            if (!$option) {
                                                $option = AttributeOption::create([
                                                    'attribute_id' => $attribute->id,
                                                    'name' => $optionName,
                                                    'extra_price' => 0,
                                                ]);
                                            }

                                            ProductAttributeValue::create([
                                                'product_id' => $record->id,
                                                'attribute_id' => $attribute->id,
                                                'product_attribute_id' => $productAttribute->id,
                                                'attribute_option_id' => $option->id,
                                                'extra_price' => $option->extra_price ?? 0,
                                            ]);
                                            $attributesCreated++;
                                        }
                                    }
                                } catch (\Exception $attrException) {
                                    Log::error('AI Photo - Error creating attribute', [
                                        'product_id' => $record->id,
                                        'error' => $attrException->getMessage(),
                                    ]);
                                }
                            }
                        }

                        // Quantity from user input
                        if (!empty($data['quantity']) && $data['quantity'] > 0) {
                            $updates['qty_available'] = $data['quantity'];
                        }

                        if (!empty($updates) || $attributesCreated > 0) {
                            if (!empty($updates)) {
                                $this->form->fill(array_merge($currentData, $updates));
                            }

                            $identifiedName = $aiData['identified_product_name'] ?? 'Unknown product';
                            $successBody = "Identified as: {$identifiedName}. Review the details and click Save when ready.";
                            if ($attributesCreated > 0) {
                                $successBody .= " {$attributesCreated} attribute(s) added.";
                            }
                            Notification::make()
                                ->title('Product Identified!')
                                ->body($successBody)
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
                ->schema(fn (InventoryProduct $record): array => [
                    Select::make('product_id')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.form.fields.product'))
                        ->required()
                        ->options($record->variants->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $product = InventoryProduct::find($get('product_id'));

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
                    InventoryProduct $record
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
                ->action(function (InventoryProduct $record, array $data): void {
                    if (isset($data['product_id'])) {
                        $record = InventoryProduct::find($data['product_id']);
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
