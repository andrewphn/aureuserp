<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

echo "\n=== KANBAN DATABASE TEST ===\n\n";

// 1. Show all stages
echo "Available Stages:\n";
echo "----------------\n";
$stages = ProjectStage::where('is_active', true)->orderBy('sort')->get();
foreach ($stages as $stage) {
    echo sprintf("ID: %d | Name: %-15s | Sort: %d\n", $stage->id, $stage->name, $stage->sort);
}

// 2. Show current project statuses
echo "\n\nCurrent Projects:\n";
echo "----------------\n";
$projects = Project::with('stage')->get();
foreach ($projects as $project) {
    echo sprintf(
        "ID: %d | Name: %-40s | Stage: %s (ID: %d)\n",
        $project->id,
        substr($project->name, 0, 40),
        $project->stage?->name ?? 'None',
        $project->stage_id
    );
}

echo "\n\n=== TEST INSTRUCTIONS ===\n\n";
echo "1. Open browser to: http://aureuserp.test/admin/project/kanban\n";
echo "2. Login with: info@tcswoodwork.com / Lola2024!\n";
echo "3. Drag a project from one column to another\n";
echo "4. Run this script again to verify the stage_id changed\n";
echo "5. Or use tinker to check specific project:\n";
echo "   DB_CONNECTION=mysql php artisan tinker --execute=\"echo \\\\Webkul\\\\Project\\\\Models\\\\Project::find(9)->stage_id;\"\n";
echo "\n";

echo "=== QUICK VERIFICATION COMMANDS ===\n\n";
echo "# Check project 9:\n";
echo "DB_CONNECTION=mysql php artisan tinker --execute=\"print_r(\\\\Webkul\\\\Project\\\\Models\\\\Project::with('stage')->find(9)->only(['id','name','stage_id']));\"\n\n";

echo "# Check all projects:\n";
echo "DB_CONNECTION=mysql php artisan tinker --execute=\"\\\\Webkul\\\\Project\\\\Models\\\\Project::with('stage')->get(['id','name','stage_id'])->each(fn(\\\$p) => print(\\\$p->id.': '.\\\$p->name.' - '.(\\\$p->stage?->name ?? 'None').\\\"\\n\\\"));\"\n\n";
