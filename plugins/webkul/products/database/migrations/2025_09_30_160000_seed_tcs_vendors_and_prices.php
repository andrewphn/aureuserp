<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id');

        // Get actual country IDs from database (don't hardcode)
        $usaCountryId = DB::table('countries')->where('code', 'US')->value('id');
        $canadaCountryId = DB::table('countries')->where('code', 'CA')->value('id');

        // Skip if countries not seeded yet
        if (!$usaCountryId || !$canadaCountryId) {
            return;
        }

        // State IDs for USA (get from database)
        $stateIds = [
            'CA' => DB::table('states')->where('code', 'CA')->where('country_id', $usaCountryId)->value('id'),
            'NY' => DB::table('states')->where('code', 'NY')->where('country_id', $usaCountryId)->value('id'),
            'DE' => DB::table('states')->where('code', 'DE')->where('country_id', $usaCountryId)->value('id'),
            'IN' => DB::table('states')->where('code', 'IN')->where('country_id', $usaCountryId)->value('id'),
        ];

        // Vendor partners data
        $vendors = [
            [
                'name' => 'Richelieu Hardware',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '1-800-361-6000',
                'website' => 'www.richelieu.com',
                'street1' => '7900 Henri-Bourassa West',
                'city' => 'Ville Saint-Laurent',
                'state_id' => null,  // Quebec (state_id queried from database below)
                'zip' => 'H4S 1V4',
                'country_id' => $canadaCountryId,  // Canada - now using variable
            ],
            [
                'name' => 'Serious Grit',
                'sub_type' => 'supplier',
                'email' => 'help@seriousgrit.com',
                'phone' => '(760) 279-3767',
                'website' => 'www.seriousgrit.com',
                'street1' => '2796 Loker Ave W #105',
                'city' => 'Carlsbad',
                'state_id' => $stateIds['CA'],
                'zip' => '92010',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'Amana Tool Corporation',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '800-445-0077',
                'website' => 'amanatool.com',
                'street1' => '120 Carolyn Boulevard',
                'city' => 'Farmingdale',
                'state_id' => $stateIds['NY'],
                'zip' => '11735',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'YUEERIO',
                'sub_type' => 'supplier',
                'email' => null,
                'phone' => null,
                'website' => null,
                'street1' => null,
                'city' => null,
                'state_id' => null,
                'zip' => null,
                'country_id' => null,
            ],
            [
                'name' => 'Felder Group USA',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '866-792-5288',
                'website' => 'feldergroupusa.com',
                'street1' => '2 Lukens Drive, Suite 300',
                'city' => 'New Castle',
                'state_id' => $stateIds['DE'],
                'zip' => '19720',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'Festool USA',
                'sub_type' => 'supplier',
                'email' => 'customerservice@festoolusa.com',
                'phone' => '888-337-8600',
                'website' => 'www.festoolusa.com',
                'street1' => '400 North Enterprise Blvd',
                'city' => 'Lebanon',
                'state_id' => $stateIds['IN'],
                'zip' => '46052',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'Amazon',
                'sub_type' => 'supplier',
                'email' => null,
                'phone' => null,
                'website' => 'www.amazon.com',
                'street1' => null,
                'city' => null,
                'state_id' => null,
                'zip' => null,
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'TBD Supplier',
                'sub_type' => 'supplier',
                'email' => null,
                'phone' => null,
                'website' => null,
                'street1' => null,
                'city' => null,
                'state_id' => null,
                'zip' => null,
                'country_id' => null,
            ],
        ];

        // Create vendor partners
        $vendorIds = [];
        foreach ($vendors as $vendor) {
            // Check if vendor already exists
            $existingVendor = DB::table('partners_partners')->where('name', $vendor['name'])->first();

            if ($existingVendor) {
                $vendorIds[$vendor['name']] = $existingVendor->id;
            } else {
                $vendorIds[$vendor['name']] = DB::table('partners_partners')->insertGetId([
                    'account_type' => 'company',
                    'sub_type' => $vendor['sub_type'],
                    'name' => $vendor['name'],
                    'email' => $vendor['email'],
                    'phone' => $vendor['phone'],
                    'website' => $vendor['website'],
                    'street1' => $vendor['street1'],
                    'city' => $vendor['city'],
                    'state_id' => $vendor['state_id'],
                    'zip' => $vendor['zip'],
                    'country_id' => $vendor['country_id'],
                    'creator_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Vendor prices data from CSV
        $vendorPrices = [
            // Richelieu Hardware items
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'CLIP top BLUMOTION Hinge for Blind Corners',
                'product_code' => '79B959180',
                'unit_price' => 8.25,
                'currency' => 'CAD',
                'notes' => 'Product #79B959180, 110° opening angle with integrated soft-close, Order #N706532 (38 units)',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Blum 1/2 Overlay Hinges (Thick)',
                'product_code' => '71B969180',
                'unit_price' => 7.80,
                'currency' => 'CAD',
                'notes' => 'Product #71B969180, For doors up to 1-3/8" thick, 110° opening angle, Order #N032778 (50 units, $389.79)',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Rev-A-Shelf Single Bin Recycling Center',
                'product_code' => '4WCSC1535DM191',
                'unit_price' => 125.00,
                'currency' => 'CAD',
                'notes' => 'Product #4WCSC1535DM191, 15" width, 35-quart capacity, Order #N706532',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Rev-A-Shelf Soft-Close Recycling Center',
                'product_code' => '4WCTM12BBSCDM1',
                'unit_price' => 145.00,
                'currency' => 'CAD',
                'notes' => 'Product #4WCTM12BBSCDM1, Double bin system with soft-close mechanism, Order #N649288',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'LeMans II System without Soft-Close Mechanism',
                'product_code' => '6933140150',
                'unit_price' => 350.00,
                'currency' => 'CAD',
                'notes' => 'Product #6933140150, Corner cabinet pull-out system for blind corners, Order #N706532',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => '(1-1/4" #7) - Plain Wood Screw, Pan Washer Head',
                'product_code' => 'PWSXC17P8114PR',
                'unit_price' => 95.00,
                'currency' => 'CAD',
                'notes' => 'Product #PWSXC17P8114PR, Pan washer head, Square drive, Extra coarse thread, Type 17 point, Box of 8000, Order #N311146',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => '(1-1/4" #7) - Plain Wood Screw, Pan Washer Head, Fine Thread',
                'product_code' => 'PWSW17P7114PR',
                'unit_price' => 95.00,
                'currency' => 'CAD',
                'notes' => 'Product #PWSW17P7114PR, Pan washer head, Square drive, Fine thread, Type 17 point, Box of 8000, Order #N311146',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Plain Wood Screw, Flat Head With Nibs (1-1/2" #6)',
                'product_code' => 'FQ2CN17P6112PR',
                'unit_price' => 85.00,
                'currency' => 'CAD',
                'notes' => 'Product #FQ2CN17P6112PR, Flat head with nibs, Quadrex drive, Coarse thread, Type 17 point, Box of 7000, Order #HF3001A',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Black Phosphate Wood Screw (1-1/2" #8)',
                'product_code' => 'FSCN17BP8112PR',
                'unit_price' => 85.00,
                'currency' => 'CAD',
                'notes' => 'Product #FSCN17BP8112PR, Black phosphate finish, Flat head with nibs, Square drive, Coarse thread, Type 17 point, Box of 7000, Order #NZ49155',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Plain Wood Screw, Flat Head, Quadrex Drive (2" #8)',
                'product_code' => 'FQCPX8112PR',
                'unit_price' => 85.00,
                'currency' => 'CAD',
                'notes' => 'Product #FQCPX8112PR, Flat head with Quadrex drive, Coarse thread, Box of 7000, Order #N706533',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Plain Wood Screw, Flat Head, Quadrex Drive (3" #8)',
                'product_code' => 'FQCPX83PR',
                'unit_price' => 65.00,
                'currency' => 'CAD',
                'notes' => 'Product #FQCPX83PR, Flat head with Quadrex drive, Coarse thread, Box of 2000, Order #N706533',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Plain Wood Screw, Flat Head, Quadrex Drive (1-1/4" #6)',
                'product_code' => 'FQC2PX6114PR',
                'unit_price' => 90.00,
                'currency' => 'CAD',
                'notes' => 'Product #FQC2PX6114PR, Flat head with Quadrex drive, Coarse thread, Box of 8000, Order #N706534',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Plain Wood Screw, Flat Head, Quadrex Drive (1-1/2" #6)',
                'product_code' => 'FQCP84PR',
                'unit_price' => 45.00,
                'currency' => 'CAD',
                'notes' => 'Product #FQCP84PR, Flat head with Quadrex drive, Coarse thread, Box of 1200, Order #N706535',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'RELIABLE Galvanized Brad Nails - 18 Gauge (1-1/4")',
                'product_code' => '91291114',
                'unit_price' => 42.00,
                'currency' => 'CAD',
                'notes' => 'Product #91291114, 18 gauge brad nails, galvanized finish, Box of 5000, Order #N127002',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'RELIABLE Galvanized Brad Nails - 18 Gauge (1")',
                'product_code' => '912910100',
                'unit_price' => 38.00,
                'currency' => 'CAD',
                'notes' => 'Product #912910100, 18 gauge brad nails, galvanized finish, Box of 5000, Order #N127001',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Headless Pin 23 Gauge (1-3/8")',
                'product_code' => 'NAI23GS18PR',
                'unit_price' => 65.00,
                'currency' => 'CAD',
                'notes' => 'Product #NAI23GS18PR, 23 gauge headless pin nails, Box of 10000, Order #N127002',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'RELIABLE Galvanized Brad Nails - 18 Gauge (2")',
                'product_code' => '91291200',
                'unit_price' => 46.00,
                'currency' => 'CAD',
                'notes' => 'Product #91291200, 18 gauge brad nails, galvanized finish, Box of 5000, Order #N127001',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Titebond II Premium Wood Glue',
                'product_code' => '150050009',
                'unit_price' => 18.50,
                'currency' => 'CAD',
                'notes' => 'Product #150050009, Water-resistant, FDA approved for indirect food contact, Order #NZ49156',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Titebond Speed Set Wood Glue - 4366',
                'product_code' => '150043609',
                'unit_price' => 22.00,
                'currency' => 'CAD',
                'notes' => 'Product #150043609, Fast-setting, reaches 60% strength in 20 minutes, Order #NZ49156',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Jowatherm 280.3 Unfilled Granular Hot Melt Adhesive',
                'product_code' => 'JW2803044',
                'unit_price' => 65.00,
                'currency' => 'CAD',
                'notes' => 'Product #JW2803044, Unfilled granular EVA adhesive for automatic edge banding machines, Order #N311146',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Pre-glued 7/8" width (22mm) maple veneer (Finished)',
                'product_code' => '1078500',
                'unit_price' => 95.00,
                'currency' => 'CAD',
                'notes' => 'Product #1078500, Pre-glued 7/8" width (22mm) maple veneer, Order #N311146',
            ],
            [
                'partner_id' => $vendorIds['Richelieu Hardware'],
                'product_name' => 'Edgebanding - Maple (250 ft)',
                'product_code' => '1078250M5',
                'unit_price' => 58.00,
                'currency' => 'CAD',
                'notes' => 'Product #1078250M5, Pre-glued 7/8" width (22mm) maple veneer, Order #N690619',
            ],

            // Serious Grit items
            [
                'partner_id' => $vendorIds['Serious Grit'],
                'product_name' => 'Serious Grit 6-Inch 80 Grit Ceramic Multi-Hole Hook & Loop Sanding Discs',
                'product_code' => null,
                'unit_price' => 37.99,
                'currency' => 'USD',
                'notes' => '50 pack - Film-Backed Universal Fit',
            ],
            [
                'partner_id' => $vendorIds['Serious Grit'],
                'product_name' => 'Serious Grit 6-Inch 120 Grit Ceramic Multi-Hole Hook & Loop Sanding Discs',
                'product_code' => null,
                'unit_price' => 37.99,
                'currency' => 'USD',
                'notes' => '50 pack - Film-Backed Universal Fit',
            ],
            [
                'partner_id' => $vendorIds['Serious Grit'],
                'product_name' => 'Serious Grit 120 Grit Ceramic Grain PSA Sandpaper Roll',
                'product_code' => null,
                'unit_price' => 29.99,
                'currency' => 'USD',
                'notes' => '2.75" x 20 Yard Continuous, Reorder every 2-3 weeks',
            ],

            // Amana Tool items
            [
                'partner_id' => $vendorIds['Amana Tool Corporation'],
                'product_name' => 'Amana Tool Spektra Extreme Tool Life Coated Solid Carbide CNC Compression Spiral Router Bit 3/8 D x 1-1/4 CH x 3/8 Shank',
                'product_code' => '46704-K',
                'unit_price' => 84.46,
                'currency' => 'USD',
                'notes' => 'Part #46704-K, 3 Inch Long 2 Flute',
            ],
            [
                'partner_id' => $vendorIds['Amana Tool Corporation'],
                'product_name' => 'Amana Tool Spektra Extreme Tool Life Solid Carbide CNC Down-Cut Spiral Plunge Router Bit 3/8 Dia x 1 CH x 3/8 SHK',
                'product_code' => '46055-K',
                'unit_price' => 61.40,
                'currency' => 'USD',
                'notes' => 'Part #46055-K, 2-½ Inch Long 3 Flute',
            ],
            [
                'partner_id' => $vendorIds['Amana Tool Corporation'],
                'product_name' => 'Amana Tool Spektra Extreme Tool Life Coated Solid Carbide Spiral Plunge Router Bit 1/8 Dia x 13/16 CH x 1/4 Shank',
                'product_code' => '46125-K',
                'unit_price' => 43.78,
                'currency' => 'USD',
                'notes' => 'Part #46125-K, 2-1/2 Inch Long Up-Cut',
            ],
            [
                'partner_id' => $vendorIds['Amana Tool Corporation'],
                'product_name' => 'Amana Tool Spektra Extreme Tool Life Solid Carbide CNC Compression Spiral Router Bit 3/8 D x 1-5/8 CH x 3/8 Shank',
                'product_code' => '46179-K',
                'unit_price' => 102.69,
                'currency' => 'USD',
                'notes' => 'Part #46179-K, 3-1/2 Inch Long 2 Flute',
            ],

            // YUEERIO items
            [
                'partner_id' => $vendorIds['YUEERIO'],
                'product_name' => 'YUEERIO 709563 Upgraded Dust Collection Bag for JET',
                'product_code' => '709563',
                'unit_price' => 26.99,
                'currency' => 'USD',
                'notes' => 'Heavy Duty Clear Bags for DC-1100VX DC-1200VX 19.5"',
            ],
        ];

        // Insert vendor prices
        foreach ($vendorPrices as $vendorPrice) {
            DB::table('products_product_suppliers')->insert([
                'partner_id' => $vendorPrice['partner_id'],
                'product_name' => $vendorPrice['product_name'],
                'product_code' => $vendorPrice['product_code'],
                'price' => $vendorPrice['unit_price'],
                'currency_id' => $vendorPrice['currency'] === 'USD' ? 1 : 2, // Assuming 1=USD, 2=CAD
                'min_qty' => 1.0000,
                'discount' => 0.0000,
                'delay' => 0,
                'product_id' => null, // Will be linked to actual products later
                'creator_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove vendor prices
        DB::table('products_product_suppliers')
            ->whereIn('product_code', [
                '79B959180', '71B969180', '4WCSC1535DM191', '4WCTM12BBSCDM1', '6933140150',
                'PWSXC17P8114PR', 'PWSW17P7114PR', 'FQ2CN17P6112PR', 'FSCN17BP8112PR', 'FQCPX8112PR',
                'FQCPX83PR', 'FQC2PX6114PR', 'FQCP84PR', '91291114', '912910100', 'NAI23GS18PR',
                '91291200', '150050009', '150043609', 'JW2803044', '1078500', '1078250M5',
                '46704-K', '46055-K', '46125-K', '46179-K', '709563',
            ])
            ->delete();

        // Remove vendors (except Richelieu)
        DB::table('partners_partners')
            ->whereIn('name', [
                'Serious Grit', 'Amana Tool Corporation', 'YUEERIO',
                'Felder Group USA', 'Festool USA', 'Amazon', 'TBD Supplier',
            ])
            ->delete();
    }
};