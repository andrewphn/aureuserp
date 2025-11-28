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
        // Add default quotation and sales order prefix settings
        DB::table('settings')->insert([
            [
                'group' => 'sales_quotation_and_orders',
                'name' => 'quotation_prefix',
                'locked' => false,
                'payload' => json_encode('Q'),
            ],
            [
                'group' => 'sales_quotation_and_orders',
                'name' => 'sales_order_prefix',
                'locked' => false,
                'payload' => json_encode('SO'),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'sales_quotation_and_orders')
            ->whereIn('name', ['quotation_prefix', 'sales_order_prefix'])
            ->delete();
    }
};
