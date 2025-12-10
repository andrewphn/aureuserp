<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure Administration department exists
        $department = DB::table('employees_departments')->where('name', 'Administration')->first();
        if (!$department) {
            $deptId = DB::table('employees_departments')->insertGetId([
                'name' => 'Administration',
                'company_id' => 1,
                'creator_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $deptId = $department->id;
        }

        // 2. Ensure job positions exist
        $ownerJob = DB::table('employees_job_positions')->where('name', 'Owner')->first();
        if (!$ownerJob) {
            $ownerJobId = DB::table('employees_job_positions')->insertGetId([
                'name' => 'Owner',
                'department_id' => $deptId,
                'creator_id' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $ownerJobId = $ownerJob->id;
        }

        $inventoryManagerJob = DB::table('employees_job_positions')->where('name', 'Inventory Manager')->first();
        if (!$inventoryManagerJob) {
            $inventoryManagerJobId = DB::table('employees_job_positions')->insertGetId([
                'name' => 'Inventory Manager',
                'department_id' => $deptId,
                'creator_id' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $inventoryManagerJobId = $inventoryManagerJob->id;
        }

        // 3. Create Bryan Patton employee (owner/manager) - using raw DB to avoid model events
        $bryan = DB::table('employees_employees')->where('work_email', 'info@tcswoodwork.com')->first();
        if (!$bryan) {
            $bryanId = DB::table('employees_employees')->insertGetId([
                'name' => 'Bryan Patton',
                'work_email' => 'info@tcswoodwork.com',
                'department_id' => $deptId,
                'job_id' => $ownerJobId,
                'time_zone' => 'America/New_York',
                'employee_type' => 'employee',
                'is_active' => true,
                'creator_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $bryanId = $bryan->id;
        }

        // 4. Create Aedan Ciganek user
        $aedanUser = User::where('email', 'aedantag@gmail.com')->first();
        if (!$aedanUser) {
            $aedanUser = User::create([
                'name' => 'Aedan Ciganek',
                'email' => 'aedantag@gmail.com',
                'password' => '$2y$12$jy73vjMfvpOeqJXS9Z7M5.vChO1WzChEpIEMRQ8HI0Jhnz7mLM6aS',
                'is_active' => 1,
                'default_company_id' => 1,
                'resource_permission' => 'individual',
            ]);
        }

        // 5. Create Aedan Ciganek employee - using raw DB to avoid model events
        $aedanEmployee = DB::table('employees_employees')->where('work_email', 'aedantag@gmail.com')->first();
        if (!$aedanEmployee) {
            DB::table('employees_employees')->insert([
                'name' => 'Aedan Ciganek',
                'work_email' => 'aedantag@gmail.com',
                'user_id' => $aedanUser->id,
                'department_id' => $deptId,
                'job_id' => $inventoryManagerJobId,
                'parent_id' => $bryanId,
                'coach_id' => $bryanId,
                'mobile_phone' => '8453759651',
                'time_zone' => 'America/New_York',
                'employee_type' => 'employee',
                'marital' => 'single',
                'is_active' => true,
                'creator_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Ensure Inventory role exists and assign to Aedan
        $inventoryRole = Role::where('name', 'Inventory')->where('guard_name', 'web')->first();

        if ($inventoryRole) {
            // Check if role already assigned
            $hasRole = DB::table('model_has_roles')
                ->where('role_id', $inventoryRole->id)
                ->where('model_type', User::class)
                ->where('model_id', $aedanUser->id)
                ->exists();

            if (!$hasRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $inventoryRole->id,
                    'model_type' => User::class,
                    'model_id' => $aedanUser->id,
                ]);
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
        // Remove role from user
        $user = User::where('email', 'aedantag@gmail.com')->first();
        if ($user) {
            $user->removeRole('Inventory');
        }

        // Delete Aedan employee
        DB::table('employees_employees')->where('work_email', 'aedantag@gmail.com')->delete();

        // Delete Aedan user
        User::where('email', 'aedantag@gmail.com')->forceDelete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
