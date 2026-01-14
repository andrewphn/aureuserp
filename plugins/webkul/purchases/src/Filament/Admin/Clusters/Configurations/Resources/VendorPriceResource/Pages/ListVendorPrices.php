<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\VendorPriceResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\VendorPriceResource;
use Webkul\Purchase\Models\VendorPrice;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Vendor Prices class
 *
 * @see \Filament\Resources\Resource
 */
class ListVendorPrices extends ListRecords
{
    use HasTableViews;

    protected static string $resource = VendorPriceResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('purchases::filament/admin/clusters/configurations/resources/vendor-price/pages/list-vendor-prices.navigation.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkImport')
                ->label('Bulk Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Select::make('partner_id')
                        ->label('Vendor')
                        ->options(Partner::where('sub_type', 'supplier')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Select the vendor for all imported SKU mappings'),
                    FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                        ->maxSize(5120)
                        ->required()
                        ->helperText('CSV format: vendor_sku,our_product_reference,price,min_qty'),
                    Placeholder::make('csv_format')
                        ->label('CSV Format')
                        ->content('
                            Example CSV:
                            vendor_sku,our_product_reference,price,min_qty
                            FIB651,MEDEX-34,2.52,0
                            PLY123,MAPLE-PLY-34,3.25,1
                        '),
                ])
                ->action(function (array $data): void {
                    $this->processBulkImport($data);
                }),
            CreateAction::make()
                ->label(__('purchases::filament/admin/clusters/configurations/resources/vendor-price/pages/list-vendor-prices.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->default_company_id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('purchases::filament/admin/clusters/configurations/resources/vendor-price/pages/list-vendor-prices.header-actions.create.notification.title'))
                        ->body(__('purchases::filament/admin/clusters/configurations/resources/vendor-price/pages/list-vendor-prices.header-actions.create.notification.body')),
                ),
        ];
    }

    /**
     * Process bulk import from CSV file
     */
    protected function processBulkImport(array $data): void
    {
        $partnerId = $data['partner_id'];
        $csvFile = $data['csv_file'];
        
        // Get file path from storage
        $filePath = storage_path('app/public/' . $csvFile);
        
        if (!file_exists($filePath)) {
            // Try livewire-tmp path
            $filePath = storage_path('app/livewire-tmp/' . $csvFile);
        }
        
        if (!file_exists($filePath)) {
            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body('Could not read uploaded file.')
                ->send();
            return;
        }

        try {
            $handle = fopen($filePath, 'r');
            $header = fgetcsv($handle);
            
            // Validate header
            $expectedHeaders = ['vendor_sku', 'our_product_reference', 'price', 'min_qty'];
            $headerLower = array_map('strtolower', array_map('trim', $header));
            
            if (array_diff($expectedHeaders, $headerLower)) {
                Notification::make()
                    ->danger()
                    ->title('Import Failed')
                    ->body('Invalid CSV header. Expected: ' . implode(', ', $expectedHeaders))
                    ->send();
                fclose($handle);
                return;
            }

            // Map header positions
            $vendorSkuCol = array_search('vendor_sku', $headerLower);
            $refCol = array_search('our_product_reference', $headerLower);
            $priceCol = array_search('price', $headerLower);
            $qtyCol = array_search('min_qty', $headerLower);

            $created = 0;
            $skipped = 0;
            $errors = [];
            $currencyId = DB::table('currencies')->where('code', 'USD')->value('id') ?? 1;

            while (($row = fgetcsv($handle)) !== false) {
                $vendorSku = trim($row[$vendorSkuCol] ?? '');
                $productRef = trim($row[$refCol] ?? '');
                $price = floatval($row[$priceCol] ?? 0);
                $minQty = intval($row[$qtyCol] ?? 0);

                if (empty($vendorSku) || empty($productRef)) {
                    $skipped++;
                    continue;
                }

                // Find product by reference
                $product = Product::where('reference', $productRef)->first();
                
                if (!$product) {
                    // Try fuzzy match
                    $product = Product::where('reference', 'like', '%' . $productRef . '%')->first();
                }
                
                if (!$product) {
                    $errors[] = "Product not found: {$productRef}";
                    $skipped++;
                    continue;
                }

                // Check if mapping exists
                $existing = ProductSupplier::where('product_id', $product->id)
                    ->where('partner_id', $partnerId)
                    ->where('product_code', $vendorSku)
                    ->exists();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Create mapping
                ProductSupplier::create([
                    'product_id' => $product->id,
                    'partner_id' => $partnerId,
                    'product_code' => $vendorSku,
                    'product_name' => $product->name,
                    'price' => $price,
                    'min_qty' => $minQty,
                    'delay' => 1,
                    'currency_id' => $currencyId,
                    'company_id' => Auth::user()->default_company_id ?? 1,
                    'creator_id' => Auth::id(),
                    'ai_created' => false,
                ]);

                $created++;
            }

            fclose($handle);

            // Clean up temp file
            @unlink($filePath);

            $message = "Created {$created} vendor SKU mappings. Skipped {$skipped}.";
            if (count($errors) > 0) {
                $message .= ' Errors: ' . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= '... and ' . (count($errors) - 3) . ' more.';
                }
            }

            Log::info('VendorPrice bulk import completed', [
                'partner_id' => $partnerId,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => count($errors),
            ]);

            Notification::make()
                ->success()
                ->title('Import Complete')
                ->body($message)
                ->send();

        } catch (\Exception $e) {
            Log::error('VendorPrice bulk import failed', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('purchases::filament/admin/clusters/configurations/resources/vendor-price/pages/list-vendor-prices.tabs.all'))
                ->icon('heroicon-s-currency-dollar')
                ->favorite()
                ->setAsDefault()
                ->badge(VendorPrice::count()),
        ];
    }
}
