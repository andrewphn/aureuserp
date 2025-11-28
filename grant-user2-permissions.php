<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Set MySQL connection
config(['database.default' => 'mysql']);

echo "Finding permission tables...\n";
$tables = DB::select('SHOW TABLES');
$permissionTables = [];

foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (stripos($tableName, 'role') !== false || stripos($tableName, 'permission') !== false) {
        echo "Found: $tableName\n";
        $permissionTables[] = $tableName;
    }
}

echo "\nUser 1 (ID: 1) info:\n";
$user1 = App\Models\User::find(1);
echo "Name: {$user1->name}\n";
echo "Email: {$user1->email}\n";

echo "\nUser 2 (ID: 2551) info:\n";
$user2 = App\Models\User::find(2551);
echo "Name: {$user2->name}\n";
echo "Email: {$user2->email}\n";

// Check for model_has_roles table (Shield/Spatie Permission)
if (in_array('model_has_roles', $permissionTables)) {
    echo "\nGranting roles from User 1 to User 2...\n";

    $user1Roles = DB::table('model_has_roles')
        ->where('model_type', 'App\\Models\\User')
        ->where('model_id', 1)
        ->get();

    foreach ($user1Roles as $role) {
        // Check if User 2 already has this role
        $exists = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 2551)
            ->where('role_id', $role->role_id)
            ->exists();

        if (!$exists) {
            DB::table('model_has_roles')->insert([
                'role_id' => $role->role_id,
                'model_type' => 'App\\Models\\User',
                'model_id' => 2551,
            ]);
            echo "✓ Granted role ID {$role->role_id}\n";
        } else {
            echo "- Role ID {$role->role_id} already exists\n";
        }
    }
}

// Check for model_has_permissions table
if (in_array('model_has_permissions', $permissionTables)) {
    echo "\nGranting direct permissions from User 1 to User 2...\n";

    $user1Permissions = DB::table('model_has_permissions')
        ->where('model_type', 'App\\Models\\User')
        ->where('model_id', 1)
        ->get();

    foreach ($user1Permissions as $perm) {
        $exists = DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 2551)
            ->where('permission_id', $perm->permission_id)
            ->exists();

        if (!$exists) {
            DB::table('model_has_permissions')->insert([
                'permission_id' => $perm->permission_id,
                'model_type' => 'App\\Models\\User',
                'model_id' => 2551,
            ]);
            echo "✓ Granted permission ID {$perm->permission_id}\n";
        }
    }
}

echo "\n✅ Permission grant complete!\n";
