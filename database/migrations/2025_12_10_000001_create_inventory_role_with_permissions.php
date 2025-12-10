<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Inventory role
        $role = Role::firstOrCreate(
            ['name' => 'Inventory', 'guard_name' => 'web']
        );

        // All permissions for the Inventory role
        $permissions = [
            // Bills & Invoices
            'view_any_bill', 'view_bill', 'create_bill', 'update_bill', 'delete_bill', 'delete_any_bill',
            'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'delete_invoice', 'delete_any_invoice',
            'view_any_credit::notes', 'view_credit::notes', 'create_credit::notes', 'update_credit::notes', 'delete_credit::notes', 'delete_any_credit::notes',
            'view_any_refund', 'view_refund', 'create_refund', 'update_refund', 'delete_refund', 'delete_any_refund',

            // Payments
            'view_any_payments', 'view_payments', 'create_payments', 'update_payments', 'delete_payments', 'delete_any_payments',
            'view_any_payment::term', 'view_payment::term', 'create_payment::term', 'update_payment::term', 'delete_payment::term', 'restore_payment::term', 'delete_any_payment::term', 'force_delete_payment::term', 'force_delete_any_payment::term', 'restore_any_payment::term',

            // Taxes
            'view_any_tax::group', 'view_tax::group', 'create_tax::group', 'update_tax::group', 'delete_tax::group', 'delete_any_tax::group',
            'view_any_tax', 'view_tax', 'create_tax', 'update_tax', 'delete_tax', 'delete_any_tax', 'reorder_tax',

            // Incoterms
            'view_any_inco::term', 'view_inco::term', 'create_inco::term', 'update_inco::term', 'delete_inco::term', 'restore_inco::term', 'delete_any_inco::term', 'force_delete_inco::term', 'force_delete_any_inco::term', 'restore_any_inco::term',

            // Partners/Contacts
            'view_any_partner', 'view_partner', 'create_partner', 'update_partner', 'delete_partner', 'restore_partner', 'delete_any_partner', 'force_delete_partner', 'force_delete_any_partner', 'restore_any_partner',

            // Vendors
            'view_any_vendor', 'view_vendor', 'create_vendor', 'update_vendor', 'delete_vendor', 'restore_vendor', 'delete_any_vendor', 'force_delete_vendor', 'force_delete_any_vendor', 'restore_any_vendor',

            // Inventory - Locations
            'view_any_location', 'view_location', 'create_location', 'update_location', 'delete_location', 'restore_location', 'delete_any_location', 'force_delete_location', 'force_delete_any_location', 'restore_any_location',

            // Inventory - Operation Types
            'view_any_operation::type', 'view_operation::type', 'create_operation::type', 'update_operation::type', 'delete_operation::type', 'restore_operation::type', 'delete_any_operation::type', 'force_delete_operation::type', 'force_delete_any_operation::type', 'restore_any_operation::type',

            // Inventory - Packaging
            'view_any_packaging', 'view_packaging', 'create_packaging', 'update_packaging', 'delete_packaging', 'delete_any_packaging',

            // Inventory - Product Attributes
            'view_any_product::attribute', 'view_product::attribute', 'create_product::attribute', 'update_product::attribute', 'delete_product::attribute', 'restore_product::attribute', 'delete_any_product::attribute', 'force_delete_product::attribute', 'force_delete_any_product::attribute', 'restore_any_product::attribute',

            // Inventory - Product Categories
            'view_any_inventory_product::category', 'view_inventory_product::category', 'create_inventory_product::category', 'update_inventory_product::category', 'delete_inventory_product::category', 'delete_any_inventory_product::category',

            // Inventory - Storage Categories
            'view_any_storage::category', 'view_storage::category', 'create_storage::category', 'update_storage::category', 'delete_storage::category', 'delete_any_storage::category',

            // Inventory - Warehouse
            'view_any_warehouse', 'view_warehouse', 'create_warehouse', 'update_warehouse', 'delete_warehouse', 'restore_warehouse', 'delete_any_warehouse', 'force_delete_warehouse', 'force_delete_any_warehouse', 'restore_any_warehouse',

            // Inventory - Deliveries
            'view_any_delivery', 'view_delivery', 'create_delivery', 'update_delivery', 'delete_delivery', 'delete_any_delivery',

            // Inventory - Internal Transfers
            'view_any_internal', 'view_internal', 'create_internal', 'update_internal', 'delete_internal', 'delete_any_internal',

            // Inventory - Operations
            'view_any_operation', 'view_operation', 'create_operation', 'update_operation', 'delete_operation', 'restore_operation', 'delete_any_operation', 'force_delete_operation', 'force_delete_any_operation', 'restore_any_operation', 'reorder_operation',

            // Inventory - Quantity
            'view_any_quantity', 'create_quantity',

            // Inventory - Receipts
            'view_any_receipt', 'view_receipt', 'create_receipt', 'update_receipt', 'delete_receipt', 'delete_any_receipt',

            // Inventory - Replenishment/Order Points
            'view_any_replenishment', 'view_replenishment', 'create_replenishment', 'update_replenishment', 'delete_replenishment', 'restore_replenishment', 'delete_any_replenishment', 'force_delete_replenishment', 'force_delete_any_replenishment', 'restore_any_replenishment', 'reorder_replenishment',

            // Inventory - Scrap
            'view_any_scrap', 'view_scrap', 'create_scrap', 'update_scrap', 'delete_scrap', 'delete_any_scrap',

            // Inventory - Products
            'view_any_inventory_product', 'view_inventory_product', 'create_inventory_product', 'update_inventory_product', 'delete_inventory_product', 'restore_inventory_product', 'delete_any_inventory_product', 'force_delete_inventory_product', 'force_delete_any_inventory_product', 'restore_any_inventory_product', 'reorder_inventory_product',

            // Inventory - Woodworking Material Categories
            'view_any_woodworking::material::category', 'view_woodworking::material::category', 'create_woodworking::material::category', 'update_woodworking::material::category', 'delete_woodworking::material::category', 'restore_woodworking::material::category', 'delete_any_woodworking::material::category', 'force_delete_woodworking::material::category', 'force_delete_any_woodworking::material::category', 'restore_any_woodworking::material::category', 'reorder_woodworking::material::category',

            // Invoice - Bank Accounts
            'view_any_invoice_bank::account', 'view_invoice_bank::account', 'create_invoice_bank::account', 'update_invoice_bank::account', 'delete_invoice_bank::account', 'restore_invoice_bank::account', 'delete_any_invoice_bank::account', 'force_delete_invoice_bank::account', 'force_delete_any_invoice_bank::account', 'restore_any_invoice_bank::account',

            // Invoice - Product Categories
            'view_any_invoice_product::category', 'view_invoice_product::category', 'create_invoice_product::category', 'update_invoice_product::category', 'delete_invoice_product::category', 'delete_any_invoice_product::category',

            // Products (General)
            'view_any_product', 'view_product', 'create_product', 'update_product', 'delete_product', 'restore_product', 'delete_any_product', 'force_delete_product', 'force_delete_any_product', 'restore_any_product', 'reorder_product',

            // Invoice Products
            'view_any_invoice_product', 'view_invoice_product', 'create_invoice_product', 'update_invoice_product', 'delete_invoice_product', 'restore_invoice_product', 'delete_any_invoice_product', 'force_delete_invoice_product', 'force_delete_any_invoice_product', 'restore_any_invoice_product', 'reorder_invoice_product',

            // Product Attributes & Categories (General)
            'view_any_attribute', 'view_attribute', 'create_attribute', 'update_attribute', 'delete_attribute', 'restore_attribute', 'delete_any_attribute', 'force_delete_attribute', 'force_delete_any_attribute', 'restore_any_attribute',
            'view_any_category', 'view_category', 'create_category', 'update_category', 'delete_category', 'delete_any_category',

            // Price Lists
            'view_any_price::list', 'view_price::list', 'create_price::list', 'update_price::list', 'delete_price::list', 'restore_price::list', 'delete_any_price::list', 'force_delete_price::list', 'force_delete_any_price::list', 'restore_any_price::list', 'reorder_price::list',

            // Activity Plans
            'view_any_activity::plan', 'view_activity::plan', 'create_activity::plan', 'update_activity::plan', 'delete_activity::plan', 'restore_activity::plan', 'delete_any_activity::plan', 'force_delete_activity::plan', 'force_delete_any_activity::plan', 'restore_any_activity::plan',
        ];

        // Create permissions if they don't exist and sync to role
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );

            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'Inventory')->where('guard_name', 'web')->first();

        if ($role) {
            $role->syncPermissions([]);
            $role->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
