<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Services\GeminiProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Tag;
use Webkul\Product\Models\Product;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\CreateProduct as BaseCreateProduct;

/**
 * Create Product class
 *
 * @see \Filament\Resources\Resource
 */
class CreateProduct extends BaseCreateProduct
{
    protected static string $resource = ProductResource::class;

    /**
     * Get pending AI data from session
     */
    protected function getPendingAiData(): ?array
    {
        return session('ai_pending_data');
    }

    /**
     * Set pending AI data in session
     */
    protected function setPendingAiData(?array $data): void
    {
        if ($data === null) {
            session()->forget('ai_pending_data');
        } else {
            session(['ai_pending_data' => $data]);
        }
    }

    /**
     * Get similar products from session
     */
    protected function getSimilarProducts(): array
    {
        return session('ai_similar_products', []);
    }

    /**
     * Set similar products in session
     */
    protected function setSimilarProducts(array $products): void
    {
        session(['ai_similar_products' => $products]);
    }

    /**
     * Get pending image path from session
     */
    protected function getPendingImagePath(): ?string
    {
        return session('ai_pending_image_path');
    }

    /**
     * Set pending image path in session
     */
    protected function setPendingImagePath(?string $path): void
    {
        if ($path === null) {
            session()->forget('ai_pending_image_path');
        } else {
            session(['ai_pending_image_path' => $path]);
        }
    }

    /**
     * Clear all pending AI session data
     */
    protected function clearPendingAiSession(): void
    {
        session()->forget(['ai_pending_data', 'ai_similar_products', 'ai_pending_image_path', 'ai_images_to_add']);
    }

    /**
     * Get AI images to add to Spatie after creation
     */
    protected function getAiImagesToAdd(): array
    {
        return session('ai_images_to_add', []);
    }

    /**
     * Set AI images to add to Spatie after creation
     */
    protected function setAiImagesToAdd(array $images): void
    {
        session(['ai_images_to_add' => $images]);
    }

    /**
     * Add an AI image path to be added after creation
     */
    protected function addAiImageToAdd(string $imagePath): void
    {
        $images = $this->getAiImagesToAdd();
        $images[] = $imagePath;
        $this->setAiImagesToAdd(array_unique($images));
    }

    /**
     * Override handleRecordCreation to add AI images to Spatie
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Remove the old images array from data - we'll use Spatie instead
        unset($data['images']);

        $record = parent::handleRecordCreation($data);

        // Add any AI-collected images to Spatie media library
        $aiImages = $this->getAiImagesToAdd();
        if (!empty($aiImages)) {
            foreach ($aiImages as $imagePath) {
                $fullPath = storage_path('app/public/' . $imagePath);
                if (file_exists($fullPath)) {
                    try {
                        $record->addMedia($fullPath)
                            ->toMediaCollection('product-images');
                        Log::info('Added AI image to Spatie media', [
                            'product_id' => $record->id,
                            'image_path' => $imagePath,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to add AI image to Spatie', [
                            'product_id' => $record->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            // Clear the session
            session()->forget('ai_images_to_add');
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return array_merge([
            Action::make('aiPopulate')
                ->label('AI Populate')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Generate Product Details with AI')
                ->modalDescription('Enter a product name first, then AI will search the web and generate product details. This may take up to 30 seconds. You can review and edit before saving.')
                ->modalSubmitActionLabel('Generate')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function () {
                    try {
                        // Get current form data WITHOUT triggering validation
                        $currentData = $this->form->getRawState();
                        $productName = $currentData['name'] ?? '';

                        if (empty($productName)) {
                            Notification::make()
                                ->title('Product name required')
                                ->body('Please enter a product name first before using AI Populate.')
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }

                        $service = new GeminiProductService();
                        // Description may be array from RichEditor - extract string value
                        $existingDesc = $currentData['description'] ?? null;
                        if (is_array($existingDesc)) {
                            $existingDesc = $existingDesc['content'] ?? ($existingDesc[0] ?? null);
                        }
                        $data = $service->generateProductDetails($productName, is_string($existingDesc) ? $existingDesc : null);

                        if (isset($data['error'])) {
                            Notification::make()
                                ->title('AI Generation Failed')
                                ->body($data['error'])
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Build updates array
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

                        // Barcode - only update if empty (use barcode or SKU from AI)
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

                        // Box/Package pricing from AI
                        if (!empty($data['box_cost']) && $data['box_cost'] > 0) {
                            $updates['box_cost'] = $data['box_cost'];
                        }
                        if (!empty($data['units_per_box']) && $data['units_per_box'] > 0) {
                            $updates['units_per_box'] = $data['units_per_box'];
                            // Auto-calculate unit cost if we have box cost
                            if (!empty($data['box_cost']) && $data['box_cost'] > 0) {
                                $unitCost = round($data['box_cost'] / $data['units_per_box'], 4);
                                $updates['cost'] = $unitCost;
                                $updates['price'] = $unitCost; // Default price to cost, user can mark up
                            }
                        }
                        if (!empty($data['package_description'])) {
                            $updates['package_description'] = $data['package_description'];
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

                        // Download product image from URL if provided - queue for Spatie
                        if (!empty($data['image_url'])) {
                            $downloadedImage = GeminiProductService::downloadProductImage(
                                $data['image_url'],
                                $data['identified_product_name'] ?? $productName
                            );
                            if ($downloadedImage) {
                                // Queue for Spatie media addition after creation
                                $downloadedPath = 'products/images/' . $downloadedImage;
                                $this->addAiImageToAdd($downloadedPath);
                                Log::info('AI Populate - Image queued for Spatie', ['path' => $downloadedPath]);
                            }
                        }

                        if (!empty($updates)) {
                            $this->form->fill(array_merge($currentData, $updates));

                            Notification::make()
                                ->title('Product details generated')
                                ->body('Review the changes below and click Create when ready.')
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

                        Log::info('AI Populate completed for new product: ' . $productName, [
                            'updates' => array_keys($updates),
                            'ai_data' => $data,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('AI Populate error: ' . $e->getMessage(), [
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
                ->modalDescription('Upload a photo and enter quantity. AI will identify the product and check for existing matches.')
                ->modalSubmitActionLabel('Identify Product')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function (array $data) {
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

                        // Store the quantity from user input
                        $aiData['_user_quantity'] = $data['quantity'] ?? 1;

                        // Search for similar existing products
                        $identifiedName = $aiData['identified_product_name'] ?? '';
                        $brand = $aiData['brand'] ?? null;
                        $sku = $aiData['sku'] ?? null;
                        $suggestedAttributes = $aiData['suggested_attributes'] ?? [];

                        $similarProducts = [];
                        if (!empty($identifiedName)) {
                            $similarProducts = GeminiProductService::findSimilarProducts(
                                $identifiedName,
                                $brand,
                                $sku,
                                $suggestedAttributes
                            );
                        }

                        // Store the data in session for the confirmation step
                        $this->setPendingAiData($aiData);
                        $this->setSimilarProducts($similarProducts);
                        $this->setPendingImagePath($permanentPath);

                        Log::info('AI Photo Identify - Searching for matches', [
                            'identified_as' => $identifiedName,
                            'similar_count' => count($similarProducts),
                        ]);

                        // Show the confirmation action or apply directly
                        if (!empty($similarProducts)) {
                            // Show notification with options - user can click the confirmation action button
                            $identifiedName = $aiData['identified_product_name'] ?? 'Unknown';
                            Notification::make()
                                ->title('Similar Products Found!')
                                ->body("Identified: {$identifiedName}. Found " . count($similarProducts) . " similar product(s). Click 'Choose Product' button to select, or the form has been pre-filled - review and Create.")
                                ->warning()
                                ->persistent()
                                ->send();

                            // Also apply the AI data to form so user can just create if they want
                            $this->applyAiDataToForm();
                        } else {
                            // No matches found, proceed directly
                            $this->applyAiDataToForm();
                        }

                    } catch (\Exception $e) {
                        Log::error('AI Photo Identify error: ' . $e->getMessage(), [
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
            // Hidden action for confirmation modal - triggered after AI identification
            Action::make('confirmProductChoice')
                ->label('Choose Product')
                ->modalHeading('Similar Products Found')
                ->modalDescription(function () {
                    $pendingData = $this->getPendingAiData();
                    if ($pendingData && !empty($pendingData['identified_product_name'])) {
                        return "AI identified: **{$pendingData['identified_product_name']}**\n\nWe found existing products that might match. Select one to update, or create a new product.";
                    }
                    return 'Select an option';
                })
                ->modalWidth('lg')
                ->form(fn () => [
                    Radio::make('product_choice')
                        ->label('What would you like to do?')
                        ->options(fn () => $this->buildProductChoiceOptions())
                        ->descriptions(fn () => $this->buildProductChoiceDescriptions())
                        ->required()
                        ->default('new'),
                ])
                ->modalSubmitActionLabel('Continue')
                ->action(function (array $data) {
                    $choice = $data['product_choice'] ?? 'new';

                    if ($choice === 'new') {
                        // Create new product with AI data
                        $this->applyAiDataToForm();
                    } elseif (str_starts_with($choice, 'existing_')) {
                        // User selected an existing product - redirect to edit it
                        $productId = (int) str_replace('existing_', '', $choice);
                        $this->redirectToExistingProduct($productId);
                    } elseif (str_starts_with($choice, 'variant_')) {
                        // User selected a configurable product to add variant
                        $parentId = (int) str_replace('variant_', '', $choice);
                        $this->showVariantSelection($parentId);
                    }
                })
                ->visible(false), // Hidden - triggered programmatically
            // Hidden action for variant selection from parent product
            Action::make('selectVariant')
                ->label('Select Variant')
                ->modalHeading('Select Product Variant')
                ->modalDescription(fn () => 'Choose the specific variant you have, or the AI-identified product details will be used.')
                ->modalWidth('lg')
                ->form(fn () => [
                    Radio::make('variant_choice')
                        ->label('Which variant do you have?')
                        ->options(fn () => $this->buildVariantOptions())
                        ->descriptions(fn () => $this->buildVariantDescriptions())
                        ->required()
                        ->default('use_ai'),
                ])
                ->modalSubmitActionLabel('Continue')
                ->action(function (array $data) {
                    $choice = $data['variant_choice'] ?? 'use_ai';

                    if ($choice === 'use_ai') {
                        // Use AI data to create/update
                        $this->applyAiDataToForm();
                    } elseif (str_starts_with($choice, 'variant_')) {
                        // User selected specific variant - redirect to edit
                        $variantId = (int) str_replace('variant_', '', $choice);
                        $this->redirectToExistingProduct($variantId);
                    }
                })
                ->visible(false), // Hidden - triggered programmatically
        ], parent::getHeaderActions());
    }

    /**
     * Build options for the product choice radio
     */
    protected function buildProductChoiceOptions(): array
    {
        $options = [
            'new' => '➕ Create as NEW product',
        ];

        $similarProducts = $this->getSimilarProducts();
        if (empty($similarProducts)) {
            return $options;
        }

        foreach ($similarProducts as $product) {
            $label = $product['name'];
            if (!empty($product['reference'])) {
                $label .= " (SKU: {$product['reference']})";
            }

            if ($product['is_configurable'] ?? false) {
                $variantCount = $product['variant_count'] ?? 0;
                $options["variant_{$product['id']}"] = "📦 Add as variant of: {$label} ({$variantCount} variants)";
            } else {
                $options["existing_{$product['id']}"] = "✏️ Update existing: {$label}";
            }
        }

        return $options;
    }

    /**
     * Build descriptions for product choice options
     */
    protected function buildProductChoiceDescriptions(): array
    {
        $descriptions = [
            'new' => 'Create a brand new product entry with the AI-identified details',
        ];

        $similarProducts = $this->getSimilarProducts();
        if (empty($similarProducts)) {
            return $descriptions;
        }

        foreach ($similarProducts as $product) {
            $confidence = $product['confidence'] ?? 0;
            $matchType = $product['match_type'] ?? 'fuzzy';
            $price = number_format($product['price'] ?? 0, 2);

            $matchLabel = match ($matchType) {
                'exact_sku' => 'Exact SKU match',
                'brand_name' => 'Brand & name match',
                'configurable_parent' => 'Product family',
                default => "{$confidence}% similar",
            };

            if ($product['is_configurable'] ?? false) {
                $descriptions["variant_{$product['id']}"] = "{$matchLabel} • This is a configurable product - select if this is a variant/size";
            } else {
                $descriptions["existing_{$product['id']}"] = "{$matchLabel} • Price: \${$price}";
            }
        }

        return $descriptions;
    }

    /**
     * Build variant options for parent product
     */
    protected function buildVariantOptions(): array
    {
        $options = [
            'use_ai' => '➕ Create NEW variant using AI data',
        ];

        $pendingData = $this->getPendingAiData();
        if (empty($pendingData['_parent_id'])) {
            return $options;
        }

        $variants = GeminiProductService::getProductVariants($pendingData['_parent_id']);

        foreach ($variants as $variant) {
            $attrStr = '';
            if (!empty($variant['attributes'])) {
                $attrStr = ' (' . implode(', ', array_map(
                    fn($k, $v) => "{$k}: {$v}",
                    array_keys($variant['attributes']),
                    array_values($variant['attributes'])
                )) . ')';
            }
            $options["variant_{$variant['id']}"] = "✏️ {$variant['name']}{$attrStr}";
        }

        return $options;
    }

    /**
     * Build descriptions for variant options
     */
    protected function buildVariantDescriptions(): array
    {
        $descriptions = [
            'use_ai' => 'Create a new variant with the AI-identified details',
        ];

        $pendingData = $this->getPendingAiData();
        if (empty($pendingData['_parent_id'])) {
            return $descriptions;
        }

        $variants = GeminiProductService::getProductVariants($pendingData['_parent_id']);

        foreach ($variants as $variant) {
            $price = number_format($variant['price'] ?? 0, 2);
            $descriptions["variant_{$variant['id']}"] = "Price: \${$price}";
        }

        return $descriptions;
    }

    /**
     * Show variant selection for a configurable parent
     */
    protected function showVariantSelection(int $parentId): void
    {
        // Store parent ID for variant selection in session
        $pendingData = $this->getPendingAiData() ?? [];
        $pendingData['_parent_id'] = $parentId;
        $this->setPendingAiData($pendingData);

        // Mount the variant selection action
        $this->mountAction('selectVariant');
    }

    /**
     * Redirect to edit an existing product
     */
    protected function redirectToExistingProduct(int $productId): void
    {
        $pendingImagePath = $this->getPendingImagePath();

        // Add the uploaded image to the existing product using Spatie
        if (!empty($pendingImagePath)) {
            $product = Product::find($productId);
            if ($product) {
                $fullPath = storage_path('app/public/' . $pendingImagePath);
                if (file_exists($fullPath)) {
                    try {
                        $product->addMedia($fullPath)
                            ->toMediaCollection('product-images');
                        Log::info('Added uploaded image to existing product via Spatie', [
                            'product_id' => $productId,
                            'image_path' => $pendingImagePath,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to add image to existing product via Spatie', [
                            'product_id' => $productId,
                            'image_path' => $pendingImagePath,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Clear session data
        $this->clearPendingAiSession();

        Notification::make()
            ->title('Redirecting to existing product')
            ->body('You chose to update an existing product. Redirecting...')
            ->info()
            ->send();

        // Redirect to the edit page
        $this->redirect(ProductResource::getUrl('edit', ['record' => $productId]));
    }

    /**
     * Apply pending AI data to the create form
     */
    protected function applyAiDataToForm(): void
    {
        $aiData = $this->getPendingAiData();
        $pendingImagePath = $this->getPendingImagePath();

        if (empty($aiData)) {
            Notification::make()
                ->title('No AI Data')
                ->body('No pending AI data to apply.')
                ->warning()
                ->send();
            return;
        }

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

        // Supplier SKU / Manufacturer Item Code - this is the manufacturer's code (e.g., FKWZ658M1)
        // Don't overwrite internal reference - that's auto-generated by the ERP
        if (empty($currentData['supplier_sku']) && !empty($aiData['sku'])) {
            $updates['supplier_sku'] = $aiData['sku'];
        }

        // Barcode - only update if empty
        if (empty($currentData['barcode']) && !empty($aiData['barcode'])) {
            $updates['barcode'] = $aiData['barcode'];
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

        // Box/Package pricing from AI
        if (!empty($aiData['box_cost']) && $aiData['box_cost'] > 0) {
            $updates['box_cost'] = $aiData['box_cost'];
        }
        if (!empty($aiData['units_per_box']) && $aiData['units_per_box'] > 0) {
            $updates['units_per_box'] = $aiData['units_per_box'];
            // Auto-calculate unit cost if we have box cost
            if (!empty($aiData['box_cost']) && $aiData['box_cost'] > 0) {
                $unitCost = round($aiData['box_cost'] / $aiData['units_per_box'], 4);
                $updates['cost'] = $unitCost;
                $updates['price'] = $unitCost;
            }
        }
        if (!empty($aiData['package_description'])) {
            $updates['package_description'] = $aiData['package_description'];
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

        // Queue images for Spatie media library (will be added after product creation)
        Log::info('AI Photo - Queueing images for Spatie', [
            'pending_image_path' => $pendingImagePath,
        ]);

        if (!empty($pendingImagePath)) {
            // Verify the file exists
            $fullImagePath = storage_path('app/public/' . $pendingImagePath);
            if (file_exists($fullImagePath)) {
                // Queue for Spatie media addition after creation
                $this->addAiImageToAdd($pendingImagePath);
                Log::info('AI Photo - Image queued for Spatie', ['path' => $pendingImagePath]);
            } else {
                Log::warning('AI Photo - Image file not found', ['expected_path' => $fullImagePath]);
            }
        }

        // Also download AI-suggested product image if available
        if (!empty($aiData['image_url'])) {
            $downloadedImage = GeminiProductService::downloadProductImage(
                $aiData['image_url'],
                $aiData['identified_product_name'] ?? null
            );
            if ($downloadedImage) {
                // Queue for Spatie media addition after creation
                $downloadedPath = 'products/images/' . $downloadedImage;
                $this->addAiImageToAdd($downloadedPath);
                Log::info('AI Photo - Downloaded AI-suggested image queued for Spatie', ['path' => $downloadedPath]);
            }
        }

        Log::info('AI Photo - Images queued for Spatie', ['count' => count($this->getAiImagesToAdd())]);

        // Quantity from user input
        if (!empty($aiData['_user_quantity']) && $aiData['_user_quantity'] > 0) {
            $updates['qty_available'] = $aiData['_user_quantity'];
        }

        if (!empty($updates)) {
            $this->form->fill(array_merge($currentData, $updates));

            $identifiedName = $aiData['identified_product_name'] ?? 'Unknown product';
            Notification::make()
                ->title('Product Identified!')
                ->body("Identified as: {$identifiedName}. Review the details and click Create when ready.")
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

        Log::info('AI Photo Identify completed for new product', [
            'identified_as' => $aiData['identified_product_name'] ?? 'unknown',
            'updates' => array_keys($updates),
        ]);

        // Clear pending session data
        $this->clearPendingAiSession();
    }
}
