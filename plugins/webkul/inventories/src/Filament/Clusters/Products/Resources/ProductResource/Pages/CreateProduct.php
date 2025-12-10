<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Services\GeminiProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Tag;
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
                ->modalDescription('Upload a photo and enter quantity. AI will identify the product and populate all details.')
                ->modalSubmitActionLabel('Identify & Populate')
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

                        // Add image to product's images array
                        $existingImages = $currentData['images'] ?? [];
                        if (!is_array($existingImages)) {
                            $existingImages = [];
                        }
                        $existingImages[] = $permanentPath;
                        $updates['images'] = $existingImages;

                        // Quantity from user input
                        if (!empty($data['quantity']) && $data['quantity'] > 0) {
                            $updates['qty_available'] = $data['quantity'];
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
        ], parent::getHeaderActions());
    }
}
