<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Services\GeminiProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
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

        Log::info('handleRecordCreation: Checking AI images', [
            'product_id' => $record->id,
            'product_exists' => $record->exists,
            'ai_images_count' => count($aiImages),
            'ai_images' => $aiImages,
        ]);

        if (!empty($aiImages)) {
            foreach ($aiImages as $imagePath) {
                $fullPath = storage_path('app/public/' . $imagePath);
                $fileExists = file_exists($fullPath);
                $fileSize = $fileExists ? filesize($fullPath) : 0;

                Log::info('handleRecordCreation: Processing image', [
                    'product_id' => $record->id,
                    'image_path' => $imagePath,
                    'full_path' => $fullPath,
                    'file_exists' => $fileExists,
                    'file_size' => $fileSize,
                ]);

                if ($fileExists) {
                    try {
                        $media = $record->addMedia($fullPath)
                            ->toMediaCollection('product-images');

                        Log::info('Added AI image to Spatie media', [
                            'product_id' => $record->id,
                            'image_path' => $imagePath,
                            'media_id' => $media ? $media->id : 'null',
                            'media_file' => $media ? $media->file_name : 'null',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to add AI image to Spatie', [
                            'product_id' => $record->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    Log::warning('handleRecordCreation: Image file not found', [
                        'product_id' => $record->id,
                        'image_path' => $imagePath,
                        'full_path' => $fullPath,
                    ]);
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
                ->modalDescription('AI will search using any data you\'ve entered (name, supplier SKU, barcode, etc.) to find product information. Richelieu products are prioritized. This may take up to 30 seconds.')
                ->modalSubmitActionLabel('Generate')
                ->visible(fn () => (new GeminiProductService())->isConfigured())
                ->action(function () {
                    try {
                        // Get current form data WITHOUT triggering validation
                        $currentData = $this->form->getRawState();

                        // Build search context from ALL available fields
                        $searchContext = [];
                        if (!empty($currentData['supplier_sku'])) {
                            $searchContext['supplier_sku'] = $currentData['supplier_sku'];
                        }
                        if (!empty($currentData['barcode'])) {
                            $searchContext['barcode'] = $currentData['barcode'];
                        }
                        if (!empty($currentData['reference'])) {
                            $searchContext['reference'] = $currentData['reference'];
                        }
                        // Try to get brand from tags or other fields if available

                        // Determine primary search term - prioritize supplier_sku for Richelieu lookup
                        $productName = $currentData['name'] ?? '';

                        // If no name but we have supplier SKU, use that as primary search
                        if (empty($productName) && !empty($searchContext['supplier_sku'])) {
                            $productName = $searchContext['supplier_sku'];
                        }

                        // If still nothing, check barcode
                        if (empty($productName) && !empty($searchContext['barcode'])) {
                            $productName = $searchContext['barcode'];
                        }

                        if (empty($productName) && empty($searchContext)) {
                            Notification::make()
                                ->title('No search data')
                                ->body('Please enter a product name, supplier SKU, or barcode before using AI Populate.')
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
                        $data = $service->generateProductDetails($productName, is_string($existingDesc) ? $existingDesc : null, $searchContext);

                        if (isset($data['error'])) {
                            Notification::make()
                                ->title('AI Generation Failed')
                                ->body($data['error'])
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Build AI updates and detect conflicts with existing data
                        $aiUpdates = [];
                        $conflicts = [];

                        // Helper to check if field has existing non-empty value
                        $hasExistingValue = function ($field) use ($currentData) {
                            $value = $currentData[$field] ?? null;
                            if ($value === null || $value === '' || $value === 0 || $value === 0.0) {
                                return false;
                            }
                            if (is_string($value) && trim(strip_tags($value)) === '') {
                                return false;
                            }
                            return true;
                        };

                        // Build rich description with technical specs and source
                        if (!empty($data['description'])) {
                            $fullDescription = $data['description'];

                            if (!empty($data['technical_specs'])) {
                                $fullDescription .= "\n<p><strong>Technical Specs:</strong> " . htmlspecialchars($data['technical_specs']) . "</p>";
                            }
                            if (!empty($data['brand'])) {
                                $fullDescription .= "\n<p><strong>Brand:</strong> " . htmlspecialchars($data['brand']) . "</p>";
                            }
                            if (!empty($data['source_url'])) {
                                $fullDescription .= "\n<p><em>Source: <a href=\"" . htmlspecialchars($data['source_url']) . "\" target=\"_blank\">" . htmlspecialchars($data['source_url']) . "</a></em></p>";
                            }

                            if ($hasExistingValue('description')) {
                                // Handle RichEditor array format
                                $currentDesc = $currentData['description'];
                                if (is_array($currentDesc)) {
                                    $currentDesc = $currentDesc['content'] ?? ($currentDesc[0] ?? '');
                                }
                                if (is_string($currentDesc) && !empty(trim(strip_tags($currentDesc)))) {
                                    $conflicts['description'] = ['current' => substr(strip_tags($currentDesc), 0, 100) . '...', 'ai' => substr(strip_tags($fullDescription), 0, 100) . '...'];
                                }
                            }
                            $aiUpdates['description'] = $fullDescription;
                        }

                        // Barcode/SKU
                        $aiBarcode = $data['barcode'] ?? ($data['sku'] ?? null);
                        if (!empty($aiBarcode)) {
                            if ($hasExistingValue('barcode')) {
                                $conflicts['barcode'] = ['current' => $currentData['barcode'], 'ai' => $aiBarcode];
                            }
                            $aiUpdates['barcode'] = $aiBarcode;
                        }

                        // Supplier SKU from AI
                        if (!empty($data['sku'])) {
                            if ($hasExistingValue('supplier_sku')) {
                                $conflicts['supplier_sku'] = ['current' => $currentData['supplier_sku'], 'ai' => $data['sku']];
                            }
                            $aiUpdates['supplier_sku'] = $data['sku'];
                        }

                        // Price and Cost
                        $costValue = $data['suggested_cost'] ?? $data['suggested_price'] ?? 0;
                        if ($costValue > 0) {
                            if ($hasExistingValue('price')) {
                                $conflicts['price'] = ['current' => '$' . number_format($currentData['price'], 2), 'ai' => '$' . number_format($costValue, 2)];
                            }
                            if ($hasExistingValue('cost')) {
                                $conflicts['cost'] = ['current' => '$' . number_format($currentData['cost'], 2), 'ai' => '$' . number_format($costValue, 2)];
                            }
                            $aiUpdates['price'] = $costValue;
                            $aiUpdates['cost'] = $costValue;
                        }

                        // Weight
                        if (!empty($data['weight']) && $data['weight'] > 0) {
                            if ($hasExistingValue('weight')) {
                                $conflicts['weight'] = ['current' => $currentData['weight'] . ' kg', 'ai' => $data['weight'] . ' kg'];
                            }
                            $aiUpdates['weight'] = $data['weight'];
                        }

                        // Volume
                        if (!empty($data['volume']) && $data['volume'] > 0) {
                            if ($hasExistingValue('volume')) {
                                $conflicts['volume'] = ['current' => $currentData['volume'] . ' L', 'ai' => $data['volume'] . ' L'];
                            }
                            $aiUpdates['volume'] = $data['volume'];
                        }

                        // Box/Package pricing
                        if (!empty($data['box_cost']) && $data['box_cost'] > 0) {
                            $aiUpdates['box_cost'] = $data['box_cost'];
                        }
                        if (!empty($data['units_per_box']) && $data['units_per_box'] > 0) {
                            $aiUpdates['units_per_box'] = $data['units_per_box'];
                            if (!empty($data['box_cost']) && $data['box_cost'] > 0) {
                                $unitCost = round($data['box_cost'] / $data['units_per_box'], 4);
                                $aiUpdates['cost'] = $unitCost;
                                $aiUpdates['price'] = $unitCost;
                            }
                        }
                        if (!empty($data['package_description'])) {
                            $aiUpdates['package_description'] = $data['package_description'];
                        }

                        // Category
                        if (!empty($data['category_id']) && $data['category_id'] > 0) {
                            if ($hasExistingValue('category_id')) {
                                $conflicts['category_id'] = ['current' => 'ID: ' . $currentData['category_id'], 'ai' => 'ID: ' . $data['category_id']];
                            }
                            $aiUpdates['category_id'] = $data['category_id'];
                        }

                        // Reference Type Code
                        if (!empty($data['reference_type_code_id']) && $data['reference_type_code_id'] > 0) {
                            if ($hasExistingValue('reference_type_code_id')) {
                                $conflicts['reference_type_code_id'] = ['current' => 'ID: ' . $currentData['reference_type_code_id'], 'ai' => 'ID: ' . $data['reference_type_code_id']];
                            }
                            $aiUpdates['reference_type_code_id'] = $data['reference_type_code_id'];
                        }

                        // Handle tags - always merge, never conflict
                        if (!empty($data['tags']) && is_array($data['tags'])) {
                            $tagIds = [];
                            foreach ($data['tags'] as $tagName) {
                                $tagName = trim($tagName);
                                if (empty($tagName)) continue;
                                $tag = Tag::firstOrCreate(['name' => $tagName], ['name' => $tagName]);
                                $tagIds[] = $tag->id;
                            }
                            if (!empty($tagIds)) {
                                $existingTagIds = $currentData['tags'] ?? [];
                                $aiUpdates['tags'] = array_unique(array_merge($existingTagIds, $tagIds));
                            }
                        }

                        // Download product image from URL
                        // For Richelieu products, extract the real image URL from the product page
                        $imageUrl = $data['image_url'] ?? null;
                        $sourceUrl = $data['source_url'] ?? null;

                        if (!empty($sourceUrl) && str_contains($sourceUrl, 'richelieu.com')) {
                            Log::info('AI Populate - Attempting to extract image from Richelieu page', ['source_url' => $sourceUrl]);
                            $extractedImageUrl = GeminiProductService::extractRichelieuImageUrl($sourceUrl);
                            if ($extractedImageUrl) {
                                $imageUrl = $extractedImageUrl;
                                Log::info('AI Populate - Using extracted Richelieu image', ['image_url' => $imageUrl]);
                            }
                        }

                        if (!empty($imageUrl)) {
                            $downloadedImage = GeminiProductService::downloadProductImage(
                                $imageUrl,
                                $data['identified_product_name'] ?? $productName
                            );
                            if ($downloadedImage) {
                                $downloadedPath = 'products/images/' . $downloadedImage;
                                $this->addAiImageToAdd($downloadedPath);
                                Log::info('AI Populate - Image queued for Spatie', ['path' => $downloadedPath]);

                                // Also add to form state so it shows in preview
                                // The SpatieMediaLibraryFileUpload expects file paths relative to storage/app/public
                                $aiUpdates['product-images'] = [$downloadedPath];
                            }
                        }

                        // If there are conflicts, store data and show confirmation modal
                        if (!empty($conflicts)) {
                            $this->setPendingAiData([
                                'ai_updates' => $aiUpdates,
                                'conflicts' => $conflicts,
                                'current_data' => $currentData,
                            ]);

                            $conflictFields = array_keys($conflicts);
                            Notification::make()
                                ->title('AI found data - Review conflicts')
                                ->body('Fields with existing data: ' . implode(', ', $conflictFields) . '. Click "Review AI Conflicts" to choose which to update.')
                                ->warning()
                                ->persistent()
                                ->send();

                            // Apply only non-conflicting updates
                            $safeUpdates = array_diff_key($aiUpdates, $conflicts);
                            if (!empty($safeUpdates)) {
                                $this->form->fill(array_merge($currentData, $safeUpdates));
                            }

                            Log::info('AI Populate - Conflicts detected', [
                                'conflicts' => $conflictFields,
                                'safe_updates' => array_keys($safeUpdates),
                            ]);
                        } else {
                            // No conflicts - apply all updates
                            if (!empty($aiUpdates)) {
                                $this->form->fill(array_merge($currentData, $aiUpdates));
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
                                'updates' => array_keys($aiUpdates),
                                'ai_data' => $data,
                            ]);
                        }

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
            // Conflict Review Action - shown when AI found data that conflicts with existing values
            Action::make('reviewAiConflicts')
                ->label('Review AI Conflicts')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->visible(fn () => !empty($this->getPendingAiData()['conflicts'] ?? []))
                ->modalHeading('Review AI Data Conflicts')
                ->modalDescription('AI found new data for fields that already have values. Select which fields you want to update with AI data.')
                ->modalWidth('lg')
                ->form(fn () => $this->buildConflictReviewForm())
                ->modalSubmitActionLabel('Apply Selected Updates')
                ->action(function (array $data) {
                    try {
                        $pendingData = $this->getPendingAiData();
                        if (empty($pendingData)) {
                            Notification::make()
                                ->title('No pending AI data')
                                ->warning()
                                ->send();
                            return;
                        }

                        $aiUpdates = $pendingData['ai_updates'] ?? [];
                        $conflicts = $pendingData['conflicts'] ?? [];
                        $selectedFields = $data['fields_to_update'] ?? [];

                        $currentData = $this->form->getRawState();
                        $updates = [];

                        // Apply only the selected conflicting fields
                        foreach ($selectedFields as $field) {
                            if (isset($aiUpdates[$field])) {
                                $updates[$field] = $aiUpdates[$field];
                            }
                        }

                        if (!empty($updates)) {
                            $this->form->fill(array_merge($currentData, $updates));

                            Notification::make()
                                ->title('Fields updated')
                                ->body('Updated: ' . implode(', ', array_keys($updates)))
                                ->success()
                                ->send();

                            Log::info('AI Conflict Resolution - Applied updates', [
                                'updated_fields' => array_keys($updates),
                            ]);
                        }

                        // Clear pending data
                        $this->clearPendingAiSession();

                    } catch (\Exception $e) {
                        Log::error('AI Conflict Resolution error: ' . $e->getMessage());
                        Notification::make()
                            ->title('Error applying updates')
                            ->body($e->getMessage())
                            ->danger()
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
     * Build the form for reviewing AI field conflicts
     */
    protected function buildConflictReviewForm(): array
    {
        $pendingData = $this->getPendingAiData();
        $conflicts = $pendingData['conflicts'] ?? [];

        if (empty($conflicts)) {
            return [
                Placeholder::make('no_conflicts')
                    ->content('No conflicts to review.')
            ];
        }

        // Build comparison table as HTML
        $tableHtml = '<table class="w-full text-sm border-collapse">';
        $tableHtml .= '<thead><tr class="bg-gray-100 dark:bg-gray-800">';
        $tableHtml .= '<th class="p-2 border text-left">Field</th>';
        $tableHtml .= '<th class="p-2 border text-left">Current Value</th>';
        $tableHtml .= '<th class="p-2 border text-left">AI Suggested</th>';
        $tableHtml .= '</tr></thead><tbody>';

        $fieldLabels = [
            'description' => 'Description',
            'barcode' => 'Barcode',
            'supplier_sku' => 'Supplier SKU',
            'price' => 'Price',
            'cost' => 'Cost',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'category_id' => 'Category',
            'reference_type_code_id' => 'Reference Type',
        ];

        foreach ($conflicts as $field => $values) {
            $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $current = htmlspecialchars($values['current'] ?? 'N/A');
            $ai = htmlspecialchars($values['ai'] ?? 'N/A');

            $tableHtml .= '<tr class="border-b">';
            $tableHtml .= "<td class=\"p-2 border font-medium\">{$label}</td>";
            $tableHtml .= "<td class=\"p-2 border text-gray-600 dark:text-gray-400\">{$current}</td>";
            $tableHtml .= "<td class=\"p-2 border text-primary-600 dark:text-primary-400 font-medium\">{$ai}</td>";
            $tableHtml .= '</tr>';
        }

        $tableHtml .= '</tbody></table>';

        // Build checkbox options
        $options = [];
        foreach ($conflicts as $field => $values) {
            $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $options[$field] = $label . ': ' . ($values['ai'] ?? 'AI value');
        }

        return [
            Placeholder::make('comparison_table')
                ->label('Field Comparison')
                ->content(new HtmlString($tableHtml)),

            CheckboxList::make('fields_to_update')
                ->label('Select fields to update with AI data')
                ->options($options)
                ->descriptions(
                    collect($conflicts)->mapWithKeys(function ($values, $field) use ($fieldLabels) {
                        return [$field => 'Replace "' . ($values['current'] ?? '') . '" with "' . ($values['ai'] ?? '') . '"'];
                    })->toArray()
                )
                ->columns(1)
                ->bulkToggleable()
                ->helperText('Check the fields you want to replace with AI-suggested values. Unchecked fields will keep their current values.'),
        ];
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
        // For Richelieu products, extract the real image URL from the product page
        $imageUrl = $aiData['image_url'] ?? null;
        $sourceUrl = $aiData['source_url'] ?? null;

        if (!empty($sourceUrl) && str_contains($sourceUrl, 'richelieu.com')) {
            Log::info('AI Photo - Attempting to extract image from Richelieu page', ['source_url' => $sourceUrl]);
            $extractedImageUrl = GeminiProductService::extractRichelieuImageUrl($sourceUrl);
            if ($extractedImageUrl) {
                $imageUrl = $extractedImageUrl;
                Log::info('AI Photo - Using extracted Richelieu image', ['image_url' => $imageUrl]);
            }
        }

        if (!empty($imageUrl)) {
            $downloadedImage = GeminiProductService::downloadProductImage(
                $imageUrl,
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
