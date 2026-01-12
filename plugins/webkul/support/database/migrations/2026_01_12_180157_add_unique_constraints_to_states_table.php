<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration:
     * 1. Removes duplicate states (keeps the one with lowest ID)
     * 2. Adds unique constraints to prevent future duplicates
     */
    public function up(): void
    {
        // First, clean up existing duplicates
        // Keep the state with the lowest ID for each unique name+country_id combination
        $duplicates = DB::select("
            SELECT name, country_id, GROUP_CONCAT(id ORDER BY id) as ids
            FROM states
            GROUP BY name, country_id
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->ids);
            $keepId = (int) $ids[0]; // Keep the lowest ID
            $deleteIds = array_map('intval', array_slice($ids, 1)); // Delete the rest
            
            // Update foreign key references to point to the kept record
            // Include all tables that have state_id foreign keys
            $tablesWithStateId = [
                'companies',
                'partners_partners',
                'banks',
                'employees_employees',
                'projects_project_addresses',
            ];
            
            foreach ($tablesWithStateId as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'state_id')) {
                    DB::table($table)
                        ->whereIn('state_id', $deleteIds)
                        ->update(['state_id' => $keepId]);
                }
            }
            
            // Also check for private_state_id in employees_employees
            if (Schema::hasTable('employees_employees') && Schema::hasColumn('employees_employees', 'private_state_id')) {
                DB::table('employees_employees')
                    ->whereIn('private_state_id', $deleteIds)
                    ->update(['private_state_id' => $keepId]);
            }
            
            // Delete duplicate states
            DB::table('states')->whereIn('id', $deleteIds)->delete();
        }

        // Now add unique constraints to prevent future duplicates
        Schema::table('states', function (Blueprint $table) {
            // Add unique constraint on name + country_id combination
            $table->unique(['name', 'country_id'], 'states_name_country_unique');
            
            // Also add unique constraint on code + country_id (optional, but recommended)
            // Note: Some countries might have duplicate codes, so we'll make this conditional
            // For now, we'll just ensure name+country_id is unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropUnique('states_name_country_unique');
        });
    }
};
