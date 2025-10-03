<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    private $userId;
    private $companyId;
    private $uomId;
    private $categoryMap = [];
    private $tagMap = [];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize IDs
        $this->initializeIds();

        // Create temporary mapping table
        $this->createTempMappingTable();

        // Get all vendor pricing records without product links
        $vendorPricing = DB::table('products_product_suppliers')
            ->whereNull('product_id')
            ->get();

        $productsCreated = 0;
        $pricingLinked = 0;
        $tagsLinked = 0;

        foreach ($vendorPricing as $pricing) {
            try {
                // Get vendor name for tag assignment
                $vendorName = DB::table('partners_partners')
                    ->where('id', $pricing->partner_id)
                    ->value('name');

                // Determine category based on product name
                $categoryId = $this->determineCategory($pricing->product_name ?? 'Unknown');

                // Generate reference code
                $reference = "TC-" . ($pricing->product_code ?? uniqid());

                // Create product
                $productId = DB::table('products_products')->insertGetId([
                    'type' => 'goods',
                    'name' => mb_substr($pricing->product_name ?? 'Unknown Product', 0, 255),
                    'reference' => $reference,
                    'barcode' => $pricing->product_code,
                    'price' => $pricing->price,
                    'cost' => $pricing->price,
                    'uom_id' => $this->uomId,
                    'uom_po_id' => $this->uomId,
                    'category_id' => $categoryId,
                    'enable_purchase' => true,
                    'enable_sales' => true,
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $productsCreated++;

                // Store in temp mapping
                DB::table('temp_migration_mapping')->insert([
                    'pricing_id' => $pricing->id,
                    'product_id' => $productId,
                    'vendor_name' => $vendorName,
                    'product_code' => $pricing->product_code,
                    'product_name' => $pricing->product_name,
                    'price' => $pricing->price,
                    'created_at' => now(),
                ]);

                // Link vendor pricing to product
                DB::table('products_product_suppliers')
                    ->where('id', $pricing->id)
                    ->update([
                        'product_id' => $productId,
                        'starts_at' => $pricing->starts_at ?? now()->format('Y-m-d'),
                        'ends_at' => $pricing->ends_at ?? now()->addYear()->format('Y-m-d'),
                        'vendor_url' => $this->constructVendorUrl($vendorName, $pricing->product_code),
                        'updated_at' => now(),
                    ]);

                $pricingLinked++;

                // Assign tags
                $tags = $this->determineTags($pricing->product_name ?? '', $vendorName, $categoryId);
                foreach ($tags as $tagName) {
                    if (isset($this->tagMap[$tagName])) {
                        DB::table('products_product_tag')->insert([
                            'product_id' => $productId,
                            'tag_id' => $this->tagMap[$tagName],
                        ]);
                        $tagsLinked++;
                    }
                }

                echo "Imported: {$pricing->product_name} from {$vendorName}\n";

            } catch (\Exception $e) {
                echo "ERROR on pricing ID {$pricing->id}: " . $e->getMessage() . "\n";
                continue;
            }
        }

        echo "\n========== MIGRATION IMPORT SUMMARY ==========\n";
        echo "Products created: {$productsCreated}\n";
        echo "Vendor pricing records linked: {$pricingLinked}\n";
        echo "Tag links created: {$tagsLinked}\n";
        echo "==============================================\n\n";
    }

    /**
     * Initialize required IDs and maps
     */
    private function initializeIds(): void
    {
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;
        $this->companyId = DB::table('companies')->where('name', "The Carpenter's Son LLC")->value('id') ?? 1;
        $this->uomId = DB::table('unit_of_measures')->where('name', 'Each')->value('id') ?? 1;

        // Build category map
        $categories = DB::table('products_categories')->get(['id', 'full_name']);
        foreach ($categories as $cat) {
            $this->categoryMap[$cat->full_name] = $cat->id;
        }

        // Build tag map
        $tags = DB::table('products_tags')->get(['id', 'name']);
        foreach ($tags as $tag) {
            $this->tagMap[$tag->name] = $tag->id;
        }
    }

    /**
     * Create temporary mapping table
     */
    private function createTempMappingTable(): void
    {
        if (!Schema::hasTable('temp_migration_mapping')) {
            Schema::create('temp_migration_mapping', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('pricing_id');
                $table->bigInteger('product_id');
                $table->string('vendor_name');
                $table->string('product_code')->nullable();
                $table->text('product_name')->nullable();
                $table->decimal('price', 15, 4);
                $table->timestamps();
            });
        }
    }

    /**
     * Determine category based on product name
     */
    private function determineCategory(string $productName): int
    {
        $nameLower = strtolower($productName);

        // Hinges
        if (str_contains($nameLower, 'hinge')) {
            return $this->getCategoryId('Hardware / Hinges');
        }

        // Clips
        if (str_contains($nameLower, 'clip')) {
            return $this->getCategoryId('Hardware / Clips');
        }

        // Drawer Slides
        if (str_contains($nameLower, 'drawer slide') ||
            str_contains($nameLower, 'tandem') ||
            str_contains($nameLower, 'movento')) {
            return $this->getCategoryId('Hardware / Drawer Slides');
        }

        // Storage Systems
        if (str_contains($nameLower, 'rev-a-shelf') ||
            str_contains($nameLower, 'lemans') ||
            str_contains($nameLower, 'recycling center')) {
            return $this->getCategoryId('Hardware / Storage Systems');
        }

        // Screws
        if (str_contains($nameLower, 'screw')) {
            return $this->getCategoryId('Fasteners / Screws');
        }

        // Nails
        if (str_contains($nameLower, 'nail') ||
            str_contains($nameLower, 'brad') ||
            str_contains($nameLower, 'pin')) {
            return $this->getCategoryId('Fasteners / Nails');
        }

        // Glue
        if (str_contains($nameLower, 'glue') || str_contains($nameLower, 'titebond')) {
            return $this->getCategoryId('Adhesives / Glue');
        }

        // Epoxy
        if (str_contains($nameLower, 'epoxy')) {
            return $this->getCategoryId('Adhesives / Epoxy');
        }

        // Edge Banding Adhesive
        if (str_contains($nameLower, 'hot melt') || str_contains($nameLower, 'jowatherm')) {
            return $this->getCategoryId('Adhesives / Edge Banding Adhesive');
        }

        // Edge Banding
        if (str_contains($nameLower, 'edge band') || str_contains($nameLower, 'edgebanding')) {
            return $this->getCategoryId('Edge Banding / Wood Veneer');
        }

        // Sanding Discs
        if (str_contains($nameLower, 'sanding disc')) {
            return $this->getCategoryId('Sanding / Discs');
        }

        // Sanding Sheets
        if (str_contains($nameLower, 'sanding sheet')) {
            return $this->getCategoryId('Sanding / Sheets');
        }

        // Sanding Rolls
        if (str_contains($nameLower, 'sanding roll')) {
            return $this->getCategoryId('Sanding / Rolls');
        }

        // Router Bits
        if (str_contains($nameLower, 'router bit')) {
            return $this->getCategoryId('CNC / Router Bits');
        }

        // Dust Collection
        if (str_contains($nameLower, 'dust') && str_contains($nameLower, 'bag')) {
            return $this->getCategoryId('Shop Supplies / Dust Collection');
        }

        // Default fallback
        return $this->getCategoryId('Shop Supplies / Cleaning Supplies');
    }

    /**
     * Determine tags for a product
     */
    private function determineTags(string $productName, string $vendorName, int $categoryId): array
    {
        $tags = [];
        $nameLower = strtolower($productName);

        // Vendor tags
        if (str_contains($vendorName, 'Richelieu')) $tags[] = 'Richelieu';
        if (str_contains($vendorName, 'Serious Grit')) $tags[] = 'Serious Grit';
        if (str_contains($vendorName, 'Amana Tool')) $tags[] = 'Amana Tool';
        if (str_contains($vendorName, 'YUEERIO')) $tags[] = 'YUEERIO';

        // Product type tags
        if (str_contains($nameLower, 'hinge')) $tags[] = 'Hinge';
        if (str_contains($nameLower, 'clip')) $tags[] = 'Clip';
        if (str_contains($nameLower, 'drawer slide')) $tags[] = 'Drawer Slide';
        if (str_contains($nameLower, 'screw')) $tags[] = 'Screw';
        if (str_contains($nameLower, 'nail')) $tags[] = 'Nail';
        if (str_contains($nameLower, 'glue')) $tags[] = 'Glue';
        if (str_contains($nameLower, 'sanding disc')) $tags[] = 'Sanding Disc';
        if (str_contains($nameLower, 'sanding sheet')) $tags[] = 'Sanding Sheet';
        if (str_contains($nameLower, 'sanding roll')) $tags[] = 'Sanding Roll';
        if (str_contains($nameLower, 'edge band')) $tags[] = 'Edge Banding';
        if (str_contains($nameLower, 'router bit')) $tags[] = 'Router Bit';

        // Material tags
        if (str_contains($nameLower, 'steel')) $tags[] = 'Steel';
        if (str_contains($nameLower, 'aluminum')) $tags[] = 'Aluminum';
        if (str_contains($nameLower, 'metal')) $tags[] = 'Metal';
        if (str_contains($nameLower, 'wood')) $tags[] = 'Wood';

        // Characteristic tags
        if (str_contains($nameLower, 'sanding') ||
            str_contains($nameLower, 'glue') ||
            str_contains($nameLower, 'screw') ||
            str_contains($nameLower, 'nail')) {
            $tags[] = 'Consumable';
        }

        // Application tags
        if (str_contains($vendorName, 'Richelieu')) {
            $tags[] = 'Cabinet Hardware';
        }

        if (str_contains($nameLower, 'cnc') || str_contains($nameLower, 'router bit')) {
            $tags[] = 'CNC';
            $tags[] = 'Woodworking';
        }

        if (str_contains($nameLower, 'wood') || str_contains($nameLower, 'sanding')) {
            $tags[] = 'Woodworking';
        }

        // Always reorderable
        $tags[] = 'Reorderable';

        return array_unique($tags);
    }

    /**
     * Construct vendor URL based on vendor and product code
     */
    private function constructVendorUrl(?string $vendorName, ?string $productCode): ?string
    {
        if (empty($productCode)) {
            return null;
        }

        if (str_contains($vendorName ?? '', 'Richelieu')) {
            return "https://www.richelieu.com/us/en/category/cabinet-hardware/{$productCode}";
        }

        // For other vendors, return null (can be added manually)
        return null;
    }

    /**
     * Get category ID by full name path
     */
    private function getCategoryId(string $fullName): int
    {
        if (isset($this->categoryMap[$fullName])) {
            return $this->categoryMap[$fullName];
        }

        // Fallback to a default category if not found
        return $this->categoryMap['Shop Supplies / Cleaning Supplies'] ?? 1;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all product IDs from temp mapping
        $productIds = DB::table('temp_migration_mapping')->pluck('product_id');

        // Unlink vendor pricing (set product_id to NULL)
        $pricingIds = DB::table('temp_migration_mapping')->pluck('pricing_id');
        DB::table('products_product_suppliers')
            ->whereIn('id', $pricingIds)
            ->update(['product_id' => null]);

        // Remove tag links
        DB::table('products_product_tag')->whereIn('product_id', $productIds)->delete();

        // Remove products
        DB::table('products_products')->whereIn('id', $productIds)->delete();

        // Drop temp table
        Schema::dropIfExists('temp_migration_mapping');

        echo "Rolled back migration product import\n";
    }
};
