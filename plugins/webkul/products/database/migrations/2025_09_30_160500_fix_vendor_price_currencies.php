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
        // Update all Richelieu Hardware items to CAD (currency_id = 3)
        DB::table('products_product_suppliers')
            ->join('partners_partners', 'products_product_suppliers.partner_id', '=', 'partners_partners.id')
            ->where('partners_partners.name', 'Richelieu Hardware')
            ->update(['products_product_suppliers.currency_id' => 3]); // CAD

        // Update all Serious Grit items to USD (currency_id = 1)
        DB::table('products_product_suppliers')
            ->join('partners_partners', 'products_product_suppliers.partner_id', '=', 'partners_partners.id')
            ->where('partners_partners.name', 'Serious Grit')
            ->update(['products_product_suppliers.currency_id' => 1]); // USD

        // Update all Amana Tool items to USD (currency_id = 1)
        DB::table('products_product_suppliers')
            ->join('partners_partners', 'products_product_suppliers.partner_id', '=', 'partners_partners.id')
            ->where('partners_partners.name', 'Amana Tool Corporation')
            ->update(['products_product_suppliers.currency_id' => 1]); // USD

        // Update all YUEERIO items to USD (currency_id = 1)
        DB::table('products_product_suppliers')
            ->join('partners_partners', 'products_product_suppliers.partner_id', '=', 'partners_partners.id')
            ->where('partners_partners.name', 'YUEERIO')
            ->update(['products_product_suppliers.currency_id' => 1]); // USD
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse currency updates
    }
};