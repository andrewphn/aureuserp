<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove sample partners (all with @example.com emails) - only if table exists
        if (Schema::hasTable('partners_partners')) {
            DB::table('partners_partners')
                ->where('email', 'like', '%@example.com')
                ->delete();
        }

        // Remove sample employees (IDs 21-30 which have no user linked) - only if table exists
        if (Schema::hasTable('employees_employees')) {
            DB::table('employees_employees')
                ->whereIn('id', [21, 22, 23, 24, 25, 26, 27, 28, 29, 30])
                ->delete();
        }

        // Log what we kept
        \Log::info('Sample data removed. Kept real data:', [
            'users' => DB::table('users')->count(),
            'companies' => DB::table('companies')->count(),
            'partners' => Schema::hasTable('partners_partners') ? DB::table('partners_partners')->count() : 0,
            'employees' => Schema::hasTable('employees_employees') ? DB::table('employees_employees')->count() : 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted sample data
        \Log::warning('Sample data removal cannot be reversed');
    }
};
