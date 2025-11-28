<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id');
        $companyId = DB::table('companies')->where('name', "The Carpenter's Son LLC")->value('id');

        // Check if Amazon Business partner already exists
        $amazonId = DB::table('partners_partners')
            ->where('name', 'Amazon Business')
            ->value('id');

        if (!$amazonId) {
            // Create Amazon Business vendor
            $amazonId = DB::table('partners_partners')->insertGetId([
                'name' => 'Amazon Business',
                'account_type' => 'company',
                'sub_type' => 'vendor',
                'website' => 'https://business.amazon.com',
                'email' => 'business@amazon.com',
                'phone' => '1-888-281-3847',
                'street1' => '410 Terry Avenue North',
                'city' => 'Seattle',
                'state_id' => DB::table('states')->where('code', 'WA')->value('id'),
                'country_id' => DB::table('countries')->where('name', 'USA')->value('id'),
                'zip' => '98109',
                'company_id' => $companyId,
                'creator_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "Created Amazon Business partner (ID: {$amazonId})\n";
        } else {
            echo "Amazon Business partner already exists (ID: {$amazonId})\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Amazon Business partner if it was created by this migration
        DB::table('partners_partners')
            ->where('name', 'Amazon Business')
            ->where('website', 'https://business.amazon.com')
            ->delete();

        echo "Removed Amazon Business partner\n";
    }
};
