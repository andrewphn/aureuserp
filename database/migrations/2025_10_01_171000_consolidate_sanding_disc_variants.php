<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private $userId;
    private $companyId;
    private $gritAttributeId;
    private $sizeAttributeId;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->initializeIds();

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║           CONSOLIDATING SANDING DISC VARIANTS (PHASE 2)                  ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Get current product 14 & 15 data
        $product14 = DB::table('products_products')->where('id', 14)->first();
        $product15 = DB::table('products_products')->where('id', 15)->first();

        if (!$product14 || !$product15) {
            echo "ERROR: Products 14 or 15 not found. Skipping migration.\n";
            return;
        }

        echo "Current products:\n";
        echo "  Product 14: {$product14->name}\n";
        echo "  Product 15: {$product15->name}\n";
        echo "\n";

        // Step 1: Convert Product 14 to parent
        $this->convertToParent($product14);

        // Step 2: Link parent to Grit attribute
        $this->linkParentToAttribute($product14->id);

        // Step 3: Create variant for 120 grit (from original Product 14)
        $variant120Id = $this->createVariant120Grit($product14);

        // Step 4: Convert Product 15 to 80 grit variant
        $this->convertProduct15ToVariant($product15);

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                 SANDING DISC CONSOLIDATION COMPLETE                       ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Initialize required IDs
     */
    private function initializeIds(): void
    {
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;
        $this->companyId = DB::table('companies')->where('name', "The Carpenter's Son LLC")->value('id') ?? 1;
        $this->gritAttributeId = DB::table('products_attributes')->where('name', 'Grit')->value('id');
        $this->sizeAttributeId = DB::table('products_attributes')->where('name', 'Size')->value('id');

        if (!$this->gritAttributeId) {
            throw new \Exception("Grit attribute not found. Please run create_product_attributes migration first.");
        }
    }

    /**
     * Convert Product 14 to parent product
     */
    private function convertToParent($product): void
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 1: CONVERTING PRODUCT 14 TO PARENT                                │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        DB::table('products_products')
            ->where('id', $product->id)
            ->update([
                'name' => 'Serious Grit 6-Inch Ceramic Sanding Discs',
                'reference' => 'SG-6IN-CERAMIC',
                'barcode' => null, // Parent doesn't have specific barcode
                'updated_at' => now(),
            ]);

        echo "  ✓ Updated Product 14 to generic parent name\n";
        echo "  ✓ Reference: SG-6IN-CERAMIC\n";
        echo "  ✓ Removed barcode (will be on variants)\n\n";
    }

    /**
     * Link parent product to Grit attribute
     */
    private function linkParentToAttribute(int $parentId): void
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 2: LINKING PARENT TO GRIT ATTRIBUTE                               │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        // Check if link already exists
        $exists = DB::table('products_product_attributes')
            ->where('product_id', $parentId)
            ->where('attribute_id', $this->gritAttributeId)
            ->exists();

        if (!$exists) {
            DB::table('products_product_attributes')->insert([
                'product_id' => $parentId,
                'attribute_id' => $this->gritAttributeId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ Linked parent product to Grit attribute\n\n";
        } else {
            echo "  ✓ Parent already linked to Grit attribute\n\n";
        }
    }

    /**
     * Create variant for 120 grit (from original Product 14 data)
     */
    private function createVariant120Grit($originalProduct): int
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 3: CREATING 120 GRIT VARIANT                                      │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        // Create variant product
        $variantId = DB::table('products_products')->insertGetId([
            'parent_id' => $originalProduct->id,
            'type' => $originalProduct->type,
            'name' => 'Serious Grit 6-Inch Ceramic Sanding Discs - 120 Grit',
            'reference' => 'AMZ-B0D4S6XP21', // Original ASIN
            'barcode' => 'B0D4S6XP21', // Original barcode
            'price' => $originalProduct->price,
            'cost' => $originalProduct->cost,
            'uom_id' => $originalProduct->uom_id,
            'uom_po_id' => $originalProduct->uom_po_id,
            'category_id' => $originalProduct->category_id,
            'enable_purchase' => $originalProduct->enable_purchase,
            'enable_sales' => $originalProduct->enable_sales,
            'company_id' => $originalProduct->company_id,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created 120 grit variant (ID: {$variantId})\n";
        echo "  ✓ Preserved original ASIN: B0D4S6XP21\n";

        // Get the product_attribute_id for linking
        $productAttributeId = DB::table('products_product_attributes')
            ->where('product_id', $originalProduct->id)
            ->where('attribute_id', $this->gritAttributeId)
            ->value('id');

        // Get the attribute_option_id for 120 grit
        $option120Id = DB::table('products_attribute_options')
            ->where('attribute_id', $this->gritAttributeId)
            ->where('name', '120')
            ->value('id');

        if ($productAttributeId && $option120Id) {
            DB::table('products_product_attribute_values')->insert([
                'product_id' => $variantId,
                'attribute_id' => $this->gritAttributeId,
                'product_attribute_id' => $productAttributeId,
                'attribute_option_id' => $option120Id,
            ]);
            echo "  ✓ Linked variant to Grit=120 attribute value\n";
        }

        // Move vendor pricing to variant
        $pricingUpdated = DB::table('products_product_suppliers')
            ->where('product_id', $originalProduct->id)
            ->update(['product_id' => $variantId]);

        if ($pricingUpdated > 0) {
            echo "  ✓ Moved vendor pricing to variant\n";
        }

        // Copy tags to variant
        $tags = DB::table('products_product_tag')
            ->where('product_id', $originalProduct->id)
            ->pluck('tag_id');

        foreach ($tags as $tagId) {
            DB::table('products_product_tag')->insert([
                'product_id' => $variantId,
                'tag_id' => $tagId,
            ]);
        }

        if ($tags->count() > 0) {
            echo "  ✓ Copied {$tags->count()} tags to variant\n";
        }

        echo "\n";
        return $variantId;
    }

    /**
     * Convert Product 15 to 80 grit variant
     */
    private function convertProduct15ToVariant($product): void
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 4: CONVERTING PRODUCT 15 TO 80 GRIT VARIANT                       │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        // Update Product 15 to be a variant of Product 14
        DB::table('products_products')
            ->where('id', $product->id)
            ->update([
                'parent_id' => 14,
                'name' => 'Serious Grit 6-Inch Ceramic Sanding Discs - 80 Grit',
                'updated_at' => now(),
            ]);

        echo "  ✓ Set parent_id=14 for Product 15\n";
        echo "  ✓ Updated name to include '80 Grit'\n";

        // Get the product_attribute_id for linking
        $productAttributeId = DB::table('products_product_attributes')
            ->where('product_id', 14)
            ->where('attribute_id', $this->gritAttributeId)
            ->value('id');

        // Get the attribute_option_id for 80 grit
        $option80Id = DB::table('products_attribute_options')
            ->where('attribute_id', $this->gritAttributeId)
            ->where('name', '80')
            ->value('id');

        if ($productAttributeId && $option80Id) {
            // Check if link already exists
            $exists = DB::table('products_product_attribute_values')
                ->where('product_id', $product->id)
                ->where('attribute_id', $this->gritAttributeId)
                ->exists();

            if (!$exists) {
                DB::table('products_product_attribute_values')->insert([
                    'product_id' => $product->id,
                    'attribute_id' => $this->gritAttributeId,
                    'product_attribute_id' => $productAttributeId,
                    'attribute_option_id' => $option80Id,
                ]);
                echo "  ✓ Linked variant to Grit=80 attribute value\n";
            } else {
                echo "  ✓ Already linked to Grit=80\n";
            }
        }

        echo "  ✓ Vendor pricing preserved on Product 15\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n";
        echo "Rolling back sanding disc variant consolidation...\n\n";

        // Get the 120 grit variant ID (should be the newest product with parent_id=14)
        $variant120 = DB::table('products_products')
            ->where('parent_id', 14)
            ->where('name', 'LIKE', '%120 Grit%')
            ->first();

        if ($variant120) {
            // Move vendor pricing back to parent
            DB::table('products_product_suppliers')
                ->where('product_id', $variant120->id)
                ->update(['product_id' => 14]);

            // Remove tag links
            DB::table('products_product_tag')
                ->where('product_id', $variant120->id)
                ->delete();

            // Remove attribute value links
            DB::table('products_product_attribute_values')
                ->where('product_id', $variant120->id)
                ->delete();

            // Delete the variant product
            DB::table('products_products')->where('id', $variant120->id)->delete();

            echo "  - Removed 120 grit variant (ID: {$variant120->id})\n";
        }

        // Convert Product 15 back to standalone
        DB::table('products_products')
            ->where('id', 15)
            ->update([
                'parent_id' => null,
                'name' => 'Serious Grit 6-Inch 80 Grit Ceramic Sanding Discs',
                'updated_at' => now(),
            ]);

        // Remove attribute value link for Product 15
        DB::table('products_product_attribute_values')
            ->where('product_id', 15)
            ->delete();

        echo "  - Converted Product 15 back to standalone\n";

        // Remove parent attribute link
        DB::table('products_product_attributes')
            ->where('product_id', 14)
            ->where('attribute_id', DB::table('products_attributes')->where('name', 'Grit')->value('id'))
            ->delete();

        // Restore Product 14 original name
        DB::table('products_products')
            ->where('id', 14)
            ->update([
                'name' => 'Serious Grit 6-Inch 120 Grit Ceramic Sanding Discs',
                'reference' => 'AMZ-B0D4S6XP21',
                'barcode' => 'B0D4S6XP21',
                'updated_at' => now(),
            ]);

        echo "  - Restored Product 14 to original state\n";
        echo "\nRollback complete\n";
    }
};
