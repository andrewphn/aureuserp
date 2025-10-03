<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private $companyId;
    private $userId;
    private $uomId;
    private $attributeIds = [];
    private $brandAttributeId;
    private $brandValues = [];

    public function up(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║         RESTRUCTURING PRODUCTS WITH VARIANTS AND BRANDS                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->loadReferences();
        $this->createBrandAttribute();
        $this->createAdditionalAttributes();
        $this->deleteDuplicateParents();
        $this->createVariantGroups();
        $this->simplifyProductNames();
        $this->linkProductsToBrands();

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                    RESTRUCTURING COMPLETE                                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    public function down(): void
    {
        echo "\nRolling back product restructuring...\n";

        // Get Brand attribute ID
        $brandAttr = DB::table('products_attributes')->where('name', 'Brand')->first();

        if ($brandAttr) {
            // Remove brand attribute links
            DB::table('products_product_attribute_values')
                ->whereIn('attribute_option_id', function ($query) use ($brandAttr) {
                    $query->select('id')
                        ->from('products_attribute_options')
                        ->where('attribute_id', $brandAttr->id);
                })
                ->delete();
        }

        // Remove new attributes
        $newAttributes = ['Brand', 'Overlay Type', 'Model', 'Finish', 'Drive'];
        DB::table('products_attributes')->whereIn('name', $newAttributes)->delete();

        echo "Rollback complete\n\n";
    }

    private function loadReferences(): void
    {
        $this->companyId = DB::table('companies')->where('name', 'TCS Woodwork')->value('id') ?? 1;
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;
        $this->uomId = DB::table('unit_of_measures')->where('name', 'Unit')->value('id') ?? 1;

        echo "✓ Loaded references (Company: {$this->companyId}, User: {$this->userId}, UOM: {$this->uomId})\n\n";
    }

    private function createBrandAttribute(): void
    {
        echo "Creating Brand attribute...\n";

        // Check if Brand attribute already exists
        $existingBrand = DB::table('products_attributes')->where('name', 'Brand')->first();

        if ($existingBrand) {
            $this->brandAttributeId = $existingBrand->id;
            echo "  ✓ Brand attribute already exists (ID: {$this->brandAttributeId})\n";
        } else {
            $this->brandAttributeId = DB::table('products_attributes')->insertGetId([
                'name' => 'Brand',
                'type' => 'select',
                'sort' => 0,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ Created Brand attribute (ID: {$this->brandAttributeId})\n";
        }

        // Create brand values
        $brands = [
            'Amana Tool',
            'RELIABLE',
            'Blum',
            'Serious Grit',
            'Titebond',
            'West Systems',
            'Jowatherm',
            'Rev-A-Shelf',
            'Kreg',
            'YUEERIO',
            'Felder',
            'DEWALT',
            'Regency',
            'SST',
            'HOZLY',
            'EPSON',
            'Labelife',
            'PAMAZY',
            'VBEST',
            'O\'SKOOL',
            'Real',
            'Generic',
        ];

        foreach ($brands as $brandName) {
            $existing = DB::table('products_attribute_options')
                ->where('attribute_id', $this->brandAttributeId)
                ->where('name', $brandName)
                ->first();

            if ($existing) {
                $this->brandValues[$brandName] = $existing->id;
            } else {
                $this->brandValues[$brandName] = DB::table('products_attribute_options')->insertGetId([
                    'attribute_id' => $this->brandAttributeId,
                    'name' => $brandName,
                    'color' => null,
                    'extra_price' => 0,
                    'sort' => 0,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        echo "  ✓ Created " . count($brands) . " brand values\n\n";
    }

    private function createAdditionalAttributes(): void
    {
        echo "Creating additional attributes...\n";

        $attributes = [
            ['name' => 'Overlay Type', 'slug' => 'overlay-type', 'type' => 'select'],
            ['name' => 'Model', 'slug' => 'model', 'type' => 'text'],
            ['name' => 'Finish', 'slug' => 'finish', 'type' => 'select'],
            ['name' => 'Drive', 'slug' => 'drive', 'type' => 'select'],
        ];

        foreach ($attributes as $attr) {
            $existing = DB::table('products_attributes')->where('name', $attr['name'])->first();

            if ($existing) {
                $this->attributeIds[$attr['name']] = $existing->id;
                echo "  ✓ {$attr['name']} attribute already exists\n";
            } else {
                $this->attributeIds[$attr['name']] = DB::table('products_attributes')->insertGetId([
                    'name' => $attr['name'],
                    'type' => $attr['type'],
                    'sort' => 0,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "  ✓ Created {$attr['name']} attribute\n";
            }
        }

        // Create Overlay Type options
        $overlayTypes = ['1/2 Overlay', 'Full Overlay', 'Inset Overlay'];
        foreach ($overlayTypes as $type) {
            DB::table('products_attribute_options')->insertGetId([
                'attribute_id' => $this->attributeIds['Overlay Type'],
                'name' => $type,
                'color' => null,
                'extra_price' => 0,
                'sort' => 0,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Finish options
        $finishes = ['Galvanized', 'Black Phosphate', 'Plain'];
        foreach ($finishes as $finish) {
            DB::table('products_attribute_options')->insertGetId([
                'attribute_id' => $this->attributeIds['Finish'],
                'name' => $finish,
                'color' => null,
                'extra_price' => 0,
                'sort' => 0,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Drive options
        $drives = ['Quadrex', 'Phillips', 'Square'];
        foreach ($drives as $drive) {
            DB::table('products_attribute_options')->insertGetId([
                'attribute_id' => $this->attributeIds['Drive'],
                'name' => $drive,
                'color' => null,
                'extra_price' => 0,
                'sort' => 0,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "\n";
    }

    private function deleteDuplicateParents(): void
    {
        echo "Deleting duplicate parent products...\n";

        $duplicates = [35, 38, 43, 47]; // Duplicate IDs from Amazon import

        foreach ($duplicates as $id) {
            // Check if this parent has variants
            $variantCount = DB::table('products_products')->where('parent_id', $id)->count();

            if ($variantCount > 0) {
                echo "  ⚠️  Skipping ID {$id} - has {$variantCount} variants\n";
                continue;
            }

            DB::table('products_products')->where('id', $id)->delete();
            echo "  ✓ Deleted duplicate parent ID {$id}\n";
        }

        echo "\n";
    }

    private function createVariantGroups(): void
    {
        echo "Creating new variant groups...\n\n";

        // 1. CNC Router Bits - Amana Tool
        $this->createCNCRouterBitVariants();

        // 2. Brad Nails - RELIABLE
        $this->createBradNailVariants();

        // 3. Blum Hinges (Thick)
        $this->createBlumHingeVariants();

        // 4. Wood Screws #8
        $this->createWoodScrewVariants();

        // 5. Saw Blades
        $this->createSawBladeVariants();

        // 6. Dust Collection Bags
        $this->createDustBagVariants();

        echo "\n";
    }

    private function createCNCRouterBitVariants(): void
    {
        echo "Creating CNC Router Bit variants...\n";

        // Create parent
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'CNC Router Bit',
            'reference' => 'TCS-CNC-BIT-PARENT',
            'price' => 109.7980,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('CNC / Router Bits'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Get Type attribute
        $typeAttributeId = DB::table('products_attributes')->where('name', 'Type')->value('id');

        // Link parent to Type attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $typeAttributeId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            119 => 'Standard',
            120 => 'Compression',
            121 => 'Down-cut',
            122 => 'TBD',
            123 => 'Up-cut',
            124 => 'Spiral',
        ];

        foreach ($variants as $productId => $typeName) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'CNC Router Bit - ' . $typeName,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$typeName})\n";
        }
    }

    private function createBradNailVariants(): void
    {
        echo "\nCreating Brad Nail variants...\n";

        // Create parent
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'Galvanized Brad Nails',
            'reference' => 'TCS-FAST-NAIL-PARENT',
            'price' => 54.60,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('Fasteners / Nails'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Get Length attribute
        $lengthAttributeId = DB::table('products_attributes')->where('name', 'Length')->value('id');

        // Link parent to Length attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $lengthAttributeId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            130 => '1-1/4"',
            131 => '1"',
            133 => '2"',
        ];

        foreach ($variants as $productId => $length) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'Galvanized Brad Nails - ' . $length,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$length})\n";
        }
    }

    private function createBlumHingeVariants(): void
    {
        echo "\nCreating Blum Hinge variants...\n";

        // Create parent
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'Cabinet Hinge (Thick)',
            'reference' => 'TCS-HW-HINGE-THICK-PARENT',
            'price' => 10.14,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('Hardware / Hinges'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Link parent to Overlay Type attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $this->attributeIds['Overlay Type'],
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            137 => '1/2 Overlay',
            138 => 'Full Overlay',
            139 => 'Inset Overlay',
        ];

        foreach ($variants as $productId => $overlayType) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'Cabinet Hinge (Thick) - ' . $overlayType,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$overlayType})\n";
        }
    }

    private function createWoodScrewVariants(): void
    {
        echo "\nCreating Wood Screw variants (#8)...\n";

        // Create parent for #8 screws
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'Wood Screw - Flat Head (#8)',
            'reference' => 'TCS-FAST-SCREW-8-PARENT',
            'price' => 110.50,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('Fasteners / Screws'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Get Length attribute
        $lengthAttributeId = DB::table('products_attributes')->where('name', 'Length')->value('id');

        // Link parent to Length attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $lengthAttributeId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            134 => '2"',
            135 => '3"',
        ];

        foreach ($variants as $productId => $length) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'Wood Screw - Flat Head (#8) - ' . $length,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$length})\n";
        }
    }

    private function createSawBladeVariants(): void
    {
        echo "\nCreating Saw Blade variants...\n";

        // Create parent
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'Saw Blade',
            'reference' => 'TCS-BLADE-SAW-PARENT',
            'price' => 0,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('Blades / Saw Blades'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Get Size attribute
        $sizeAttributeId = DB::table('products_attributes')->where('name', 'Size')->value('id');

        // Link parent to Size attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $sizeAttributeId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            117 => '12"',
            118 => '10"',
        ];

        foreach ($variants as $productId => $size) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'Saw Blade - ' . $size,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$size})\n";
        }
    }

    private function createDustBagVariants(): void
    {
        echo "\nCreating Dust Collection Bag variants...\n";

        // Create parent
        $parentId = DB::table('products_products')->insertGetId([
            'type' => 'goods',
            'name' => 'Dust Collection Bag',
            'reference' => 'TCS-SHOP-DUST-PARENT',
            'price' => 35.09,
            'cost' => 0,
            'uom_id' => $this->uomId,
            'uom_po_id' => $this->uomId,
            'category_id' => $this->getCategoryId('Shop Supplies / Dust Collection'),
            'enable_purchase' => true,
            'enable_sales' => true,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  ✓ Created parent (ID: {$parentId})\n";

        // Link parent to Model attribute
        DB::table('products_product_attributes')->insert([
            'product_id' => $parentId,
            'attribute_id' => $this->attributeIds['Model'],
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing products to be variants
        $variants = [
            150 => 'JET 709563',
            151 => 'Felder AF22',
            152 => 'Floor Unit',
        ];

        foreach ($variants as $productId => $model) {
            DB::table('products_products')->where('id', $productId)->update([
                'parent_id' => $parentId,
                'name' => 'Dust Collection Bag - ' . $model,
                'updated_at' => now(),
            ]);
            echo "  ✓ Updated ID {$productId} as variant ({$model})\n";
        }
    }

    private function simplifyProductNames(): void
    {
        echo "Simplifying product names...\n";

        $nameUpdates = [
            // Adhesives
            113 => 'Epoxy Resin',
            114 => 'Premium Wood Glue',
            115 => 'Speed Set Wood Glue',
            116 => 'Hot Melt Adhesive Pellets',

            // Edge Banding
            125 => 'Edge Banding - Unfinished',
            126 => 'Edge Banding - Maple (Roll)',
            127 => 'Edge Banding - Maple (Pre-glued)',

            // Fasteners
            128 => 'Wood Screw - Flat Head with Nibs',
            129 => 'Wood Screw - Black Phosphate',
            132 => 'Headless Pin (23 Gauge)',

            // Hardware
            1 => 'CLIP top BLUMOTION Hinge',
            136 => 'CLIP top BLUMOTION Hinge',
            140 => 'LeMans II Corner System',
            141 => 'Drawer Paddle (Left)',
            142 => 'Drawer Paddle (Right)',
            143 => 'Inserta Plate',
            144 => 'Trash Pull Out - 35qt',
            145 => 'Soft-Close Recycling Center',

            // Sanding
            148 => 'PSA Sandpaper Roll',
            149 => 'Rectangular Sandpaper',

            // Maintenance
            146 => 'White Lithium Grease',
            147 => 'Machine Oil',

            // Tools
            153 => 'Jig Drill Bits',
            154 => 'Caliper',
            155 => 'Collet',
            156 => 'Rabbit Router Bits',

            // Amazon Products (simplified)
            19 => 'Bungee Cords with Carabiner',
            22 => 'Ceramic Sanding Discs',
            25 => '812 DURABrite Ultra Ink Cartridge',
            26 => 'PSA Sandpaper Roll',
            27 => 'Mill & Lathe Tramming System',
            28 => 'Label Tape (TZe-251)',
            29 => 'Table Stiffener',
            30 => 'Sub Base Clamp',
            31 => 'ISO30 Tool Holder Clamp',
            32 => 'Steel D-Ring Tie Down Anchors',
            33 => 'Extra Long Bungee Cords',
            34 => 'Wireless Dust Collector Switch',
        ];

        foreach ($nameUpdates as $productId => $newName) {
            $updated = DB::table('products_products')->where('id', $productId)->update([
                'name' => $newName,
                'updated_at' => now(),
            ]);

            if ($updated) {
                echo "  ✓ Updated ID {$productId}: {$newName}\n";
            }
        }

        echo "\n";
    }

    private function linkProductsToBrands(): void
    {
        echo "Linking products to brands...\n";

        $productBrands = [
            // Adhesives
            113 => 'West Systems',
            114 => 'Titebond',
            115 => 'Titebond',
            116 => 'Jowatherm',

            // CNC Bits (now variants of parent)
            119 => 'Amana Tool',
            120 => 'Amana Tool',
            121 => 'Amana Tool',
            122 => 'Amana Tool',
            123 => 'Amana Tool',
            124 => 'Amana Tool',

            // Brad Nails (now variants of parent)
            130 => 'RELIABLE',
            131 => 'RELIABLE',
            133 => 'RELIABLE',

            // Hinges
            1 => 'Blum',
            136 => 'Blum',
            137 => 'Blum',
            138 => 'Blum',
            139 => 'Blum',

            // Hardware
            140 => 'Rev-A-Shelf',
            145 => 'Rev-A-Shelf',

            // Sanding
            148 => 'Serious Grit',

            // Tools
            153 => 'Kreg',

            // Dust Collection
            150 => 'YUEERIO',
            151 => 'Felder',

            // Amazon Products
            25 => 'EPSON',
            27 => 'SST',
            28 => 'Labelife',
            29 => 'Regency',
            30 => 'DEWALT',
            31 => 'HOZLY',
            32 => 'PAMAZY',
            33 => 'VBEST',
            34 => 'O\'SKOOL',
        ];

        foreach ($productBrands as $productId => $brandName) {
            // Check if product exists
            $productExists = DB::table('products_products')->where('id', $productId)->exists();
            if (!$productExists) {
                echo "  ⚠️  Product ID {$productId} no longer exists, skipping\n";
                continue;
            }

            if (!isset($this->brandValues[$brandName])) {
                echo "  ⚠️  Brand '{$brandName}' not found, skipping ID {$productId}\n";
                continue;
            }

            // Check if already linked
            $existing = DB::table('products_product_attribute_values')
                ->where('product_id', $productId)
                ->where('attribute_option_id', $this->brandValues[$brandName])
                ->exists();

            if ($existing) {
                continue;
            }

            // Need product_attribute_id - get it from products_product_attributes
            $productAttribute = DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->where('attribute_id', $this->brandAttributeId)
                ->first();

            if (!$productAttribute) {
                // Create product_attribute link first
                $productAttributeId = DB::table('products_product_attributes')->insertGetId([
                    'product_id' => $productId,
                    'attribute_id' => $this->brandAttributeId,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $productAttributeId = $productAttribute->id;
            }

            DB::table('products_product_attribute_values')->insert([
                'product_id' => $productId,
                'attribute_id' => $this->brandAttributeId,
                'product_attribute_id' => $productAttributeId,
                'attribute_option_id' => $this->brandValues[$brandName],
                'extra_price' => 0,
            ]);

            echo "  ✓ Linked ID {$productId} to brand: {$brandName}\n";
        }

        echo "\n";
    }

    private function getCategoryId(string $fullCategoryPath): int
    {
        $category = DB::table('products_categories')
            ->where('full_name', $fullCategoryPath)
            ->first();

        return $category ? $category->id : 1;
    }
};
