<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additional categories needed for full TCS woodworking coverage
     */
    private array $newCategories = [
        63 => ['name' => 'Lumber & Sheet Goods', 'code' => 'LBR'],
        64 => ['name' => 'Finishes', 'code' => 'FIN'],
        65 => ['name' => 'Safety Equipment', 'code' => 'SAFE'],
        66 => ['name' => 'Clamps & Fixtures', 'code' => 'CLAMP'],
        67 => ['name' => 'Measuring & Layout', 'code' => 'MEAS'],
        68 => ['name' => 'Electrical', 'code' => 'ELEC'],
        69 => ['name' => 'Packaging', 'code' => 'PACK'],
    ];

    /**
     * Update existing categories with codes
     */
    private array $existingCategoryCodes = [
        1 => 'ALL',      // All
        3 => 'EXP',      // Expenses
        4 => 'HOME',     // Home Construction
        5 => 'INT',      // Internal
        6 => 'SALE',     // Saleable
        7 => 'OFURN',    // Office Furniture
        8 => 'OUTF',     // Outdoor furniture
        9 => 'SVC',      // Services
    ];

    /**
     * Comprehensive type codes for all categories
     */
    private array $typeCodeMap = [
        // Lumber & Sheet Goods (LBR)
        'LBR' => [
            'PLY' => 'Plywood',
            'MDF' => 'MDF',
            'MEDEX' => 'Medex',
            'PART' => 'Particle Board',
            'MAPLE' => 'Maple Lumber',
            'OAK' => 'Oak Lumber',
            'CHERRY' => 'Cherry Lumber',
            'WALNUT' => 'Walnut Lumber',
            'POPLAR' => 'Poplar Lumber',
            'BIRCH' => 'Birch Lumber',
            'ASH' => 'Ash Lumber',
            'HICKORY' => 'Hickory Lumber',
            'SAPELE' => 'Sapele Lumber',
            'TEAK' => 'Teak Lumber',
            'QSWO' => 'Quarter Sawn White Oak',
            'RWO' => 'Rift White Oak',
            'WO' => 'White Oak',
            'RO' => 'Red Oak',
        ],
        // Finishes (FIN)
        'FIN' => [
            'STAIN' => 'Stains',
            'POLY' => 'Polyurethane',
            'LAC' => 'Lacquer',
            'OIL' => 'Oil Finish',
            'WAX' => 'Wax',
            'SEAL' => 'Sealers',
            'PAINT' => 'Paint',
            'PRIME' => 'Primer',
            'THIN' => 'Thinners/Solvents',
        ],
        // Safety Equipment (SAFE)
        'SAFE' => [
            'RESP' => 'Respirators',
            'GLASS' => 'Safety Glasses',
            'GLOVE' => 'Gloves',
            'EAR' => 'Hearing Protection',
            'APRON' => 'Aprons',
            'FIRST' => 'First Aid',
        ],
        // Clamps & Fixtures (CLAMP)
        'CLAMP' => [
            'BAR' => 'Bar Clamps',
            'PIPE' => 'Pipe Clamps',
            'SPRING' => 'Spring Clamps',
            'QUICK' => 'Quick Clamps',
            'BAND' => 'Band Clamps',
            'CORNER' => 'Corner Clamps',
            'TOGGLE' => 'Toggle Clamps',
            'JIG' => 'Jigs & Fixtures',
        ],
        // Measuring & Layout (MEAS)
        'MEAS' => [
            'TAPE' => 'Tape Measures',
            'SQUARE' => 'Squares',
            'LEVEL' => 'Levels',
            'GAUGE' => 'Gauges',
            'RULER' => 'Rulers/Straightedges',
            'MARK' => 'Marking Tools',
            'LASER' => 'Laser Measures',
        ],
        // Electrical (ELEC)
        'ELEC' => [
            'LED' => 'LED Lighting',
            'WIRE' => 'Wiring',
            'OUTLET' => 'Outlets/Receptacles',
            'SWITCH' => 'Switches',
            'TRANS' => 'Transformers',
            'SENSOR' => 'Sensors',
        ],
        // Packaging (PACK)
        'PACK' => [
            'BOX' => 'Boxes',
            'WRAP' => 'Wrap/Padding',
            'TAPE' => 'Packing Tape',
            'LABEL' => 'Labels',
            'BLANK' => 'Blankets/Covers',
        ],
        // Additional for existing categories
        'ADH' => [
            'SPRAY' => 'Spray Adhesive',
            'TAPE' => 'Adhesive Tape',
            'CA' => 'CA Glue (Super Glue)',
            'CONTACT' => 'Contact Cement',
        ],
        'BLADE' => [
            'BAND' => 'Bandsaw Blade',
            'JIG' => 'Jigsaw Blade',
            'ROUTER' => 'Router Bit',
            'PLANER' => 'Planer Blade',
        ],
        'CNC' => [
            'END' => 'End Mill',
            'BALL' => 'Ball Nose',
            'VBIT' => 'V-Bit',
            'COMP' => 'Compression Bit',
            'SPOIL' => 'Spoilboard Cutter',
        ],
        'EDGE' => [
            'PVC' => 'PVC Edgebanding',
            'ABS' => 'ABS Edgebanding',
            'SOLID' => 'Solid Wood Edge',
            'IRON' => 'Iron-On Edge',
        ],
        'FAST' => [
            'BRAD' => 'Brad Nails',
            'STAPLE' => 'Staples',
            'POCKET' => 'Pocket Screws',
            'DECK' => 'Deck Screws',
            'DRYWALL' => 'Drywall Screws',
            'BOLT' => 'Bolts',
            'NUT' => 'Nuts',
            'WASHER' => 'Washers',
            'ANCHOR' => 'Anchors',
            'DOWEL' => 'Dowels',
            'BISCUIT' => 'Biscuits',
            'DOMINO' => 'Domino Tenons',
        ],
        'HW' => [
            'PULL' => 'Drawer Pulls',
            'KNOB' => 'Knobs',
            'CATCH' => 'Catches/Latches',
            'LOCK' => 'Locks',
            'SHELF' => 'Shelf Supports',
            'LIFT' => 'Lid/Door Lifts',
            'CASTER' => 'Casters',
            'LEVELER' => 'Levelers',
            'BRACKET' => 'Brackets',
            'BUMPER' => 'Bumpers',
            'INSERT' => 'Threaded Inserts',
        ],
        'MAINT' => [
            'CLEAN' => 'Cleaners',
            'WAX' => 'Machine Wax',
            'BELT' => 'Belts',
            'BRUSH' => 'Brushes',
            'FILTER' => 'Filters',
        ],
        'SAND' => [
            'SPONGE' => 'Sanding Sponges',
            'BELT' => 'Sanding Belts',
            'PAD' => 'Sanding Pads',
            'HAND' => 'Hand Sanding Blocks',
        ],
        'SHOP' => [
            'VAC' => 'Vacuum/Hoses',
            'AIR' => 'Air Compressor/Lines',
            'LIGHT' => 'Lighting',
            'BENCH' => 'Workbench Supplies',
            'STORAGE' => 'Storage/Organization',
        ],
        'TOOL' => [
            'HAND' => 'Hand Tools',
            'POWER' => 'Power Tools',
            'BLADE' => 'Tool Blades',
            'ACCESS' => 'Tool Accessories',
            'SHARPEN' => 'Sharpening',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if products tables don't exist yet
        if (!Schema::hasTable('products_categories')) {
            return;
        }

        $parentId = DB::table('products_categories')->where('name', 'All')->value('id') ?? 1;

        // Create new categories
        foreach ($this->newCategories as $id => $data) {
            $exists = DB::table('products_categories')->where('id', $id)->exists();

            if (!$exists) {
                DB::table('products_categories')->insert([
                    'id' => $id,
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'full_name' => 'All / ' . $data['name'],
                    'parent_path' => '/' . $parentId . '/',
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Update existing categories with codes
        foreach ($this->existingCategoryCodes as $id => $code) {
            DB::table('products_categories')
                ->where('id', $id)
                ->whereNull('code')
                ->update(['code' => $code]);
        }

        // Add type codes
        foreach ($this->typeCodeMap as $catCode => $types) {
            $categoryId = DB::table('products_categories')
                ->where('code', $catCode)
                ->value('id');

            if (!$categoryId) {
                continue;
            }

            foreach ($types as $code => $name) {
                $exists = DB::table('products_reference_type_codes')
                    ->where('category_id', $categoryId)
                    ->where('code', $code)
                    ->exists();

                if (!$exists) {
                    DB::table('products_reference_type_codes')->insert([
                        'code' => $code,
                        'name' => $name,
                        'category_id' => $categoryId,
                        'is_active' => true,
                        'sort' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products_categories')) {
            return;
        }

        // Remove new categories
        DB::table('products_categories')
            ->whereIn('id', array_keys($this->newCategories))
            ->delete();

        // Clear codes from existing categories
        DB::table('products_categories')
            ->whereIn('id', array_keys($this->existingCategoryCodes))
            ->update(['code' => null]);
    }
};
