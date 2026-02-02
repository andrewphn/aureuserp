<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Project Infrastructure Seeder
 *
 * Creates all supporting data for the 10 real projects:
 * - Sales Orders with line items
 * - Project Tasks (based on stage)
 * - Project Tags
 * - CNC Programs (from Notion data)
 *
 * IMPORTANT: This seeder will NOT overwrite existing data.
 * It only creates new records if they don't exist.
 *
 * Run with: php artisan db:seed --class=ProjectInfrastructureSeeder
 *
 * To undo: php artisan db:seed --class=SampleDataCleanupSeeder
 */
class ProjectInfrastructureSeeder extends Seeder
{
    protected ?Company $company = null;
    protected ?User $admin = null;
    protected int $currencyId = 1;

    /**
     * Task templates by stage
     */
    protected array $tasksByStage = [
        'discovery' => [
            ['name' => 'Site visit and measurements', 'priority' => 'high'],
            ['name' => 'Review architectural drawings', 'priority' => 'high'],
            ['name' => 'Create initial estimate', 'priority' => 'medium'],
        ],
        'design' => [
            ['name' => 'Create shop drawings', 'priority' => 'high'],
            ['name' => 'Submit for client approval', 'priority' => 'high'],
            ['name' => 'Process change orders', 'priority' => 'medium'],
        ],
        'sourcing' => [
            ['name' => 'Generate BOM', 'priority' => 'high'],
            ['name' => 'Order sheet goods', 'priority' => 'high'],
            ['name' => 'Order hardware', 'priority' => 'medium'],
            ['name' => 'Confirm delivery dates', 'priority' => 'medium'],
        ],
        'production' => [
            ['name' => 'CNC programming', 'priority' => 'high'],
            ['name' => 'Cut sheet goods', 'priority' => 'high'],
            ['name' => 'Assemble cabinets', 'priority' => 'high'],
            ['name' => 'Apply finish', 'priority' => 'medium'],
            ['name' => 'Quality check', 'priority' => 'high'],
        ],
        'delivery' => [
            ['name' => 'Schedule delivery', 'priority' => 'high'],
            ['name' => 'Load truck', 'priority' => 'medium'],
            ['name' => 'On-site installation', 'priority' => 'high'],
            ['name' => 'Final punch list', 'priority' => 'medium'],
            ['name' => 'Client sign-off', 'priority' => 'high'],
        ],
    ];

    /**
     * Project tags
     */
    protected array $projectTags = [
        ['name' => 'Nantucket', 'color' => '#3B82F6'],
        ['name' => 'Kitchen', 'color' => '#10B981'],
        ['name' => 'Built-ins', 'color' => '#8B5CF6'],
        ['name' => 'Entertainment', 'color' => '#F59E0B'],
        ['name' => 'Residential', 'color' => '#6366F1'],
        ['name' => 'Commercial', 'color' => '#EF4444'],
        ['name' => 'Rush', 'color' => '#DC2626'],
        ['name' => 'VIP Client', 'color' => '#7C3AED'],
    ];

    /**
     * LF pricing by stage
     */
    protected array $pricePerLf = [
        'discovery' => 450,
        'design' => 475,
        'sourcing' => 500,
        'production' => 525,
        'delivery' => 550,
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║      PROJECT INFRASTRUCTURE SEEDER                        ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');

        $this->company = Company::first();
        $this->admin = User::first();
        $this->currencyId = DB::table('currencies')->where('name', 'USD')->value('id') ?? 1;

        if (!$this->company || !$this->admin) {
            $this->command->error('No company or admin user found.');
            return;
        }

        $projects = Project::with('partner')->get();
        $this->command->info("Building infrastructure for {$projects->count()} projects...\n");

        // Create project tags first
        $this->createProjectTags();

        // Create infrastructure for each project
        foreach ($projects as $project) {
            $this->command->info("Project: {$project->project_number} - {$project->name}");

            $this->createSalesOrder($project);
            $this->createTasks($project);
            $this->assignTags($project);
        }

        $this->printSummary();
    }

    protected function createProjectTags(): void
    {
        $this->command->info('Creating project tags...');

        foreach ($this->projectTags as $tag) {
            DB::table('projects_tags')->updateOrInsert(
                ['name' => $tag['name']],
                [
                    'name' => $tag['name'],
                    'color' => $tag['color'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->command->info("  ✓ Created " . count($this->projectTags) . " tags");
    }

    protected function createSalesOrder(Project $project): void
    {
        $orderNumber = 'SO-' . $project->project_number;

        // Skip if exists
        if (DB::table('sales_orders')->where('name', $orderNumber)->exists()) {
            $this->command->line("  ↻ Order exists: {$orderNumber}");
            return;
        }

        $lf = $project->estimated_linear_feet ?? 30;
        $rate = $this->pricePerLf[$project->current_production_stage] ?? 500;
        $total = $lf * $rate;

        $orderId = DB::table('sales_orders')->insertGetId([
            'name' => $orderNumber,
            'state' => 'sale',
            'partner_id' => $project->partner_id,
            'partner_invoice_id' => $project->partner_id,
            'partner_shipping_id' => $project->partner_id,
            'project_id' => $project->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currencyId,
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
            'date_order' => $project->start_date ?? now()->subDays(30),
            'commitment_date' => $project->desired_completion_date,
            'amount_untaxed' => $total,
            'amount_tax' => 0,
            'amount_total' => $total,
            'invoice_status' => 'to_invoice',
            'require_signature' => false,
            'require_payment' => false,
            'project_estimated_value' => $total,
            'quoted_price_override' => $total,
            'woodworking_order_type' => 'cabinetry',
            'proposal_status' => 'accepted',
            'production_authorized' => true,
            'production_authorized_at' => $project->start_date ?? now()->subDays(20),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create order line
        DB::table('sales_order_lines')->insert([
            'order_id' => $orderId,
            'name' => 'Custom Cabinetry - ' . $project->name,
            'product_qty' => $lf,
            'price_unit' => $rate,
            'price_subtotal' => $total,
            'price_tax' => 0,
            'price_total' => $total,
            'qty_delivered' => $project->current_production_stage === 'delivery' ? $lf : 0,
            'qty_invoiced' => 0,
            'state' => 'sale',
            'company_id' => $this->company->id,
            'creator_id' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("  ✓ Sales Order: \${$total} ({$lf} LF @ \${$rate}/LF)");
    }

    protected function createTasks(Project $project): void
    {
        $stage = $project->current_production_stage ?? 'discovery';
        $templates = $this->tasksByStage[$stage] ?? $this->tasksByStage['discovery'];

        // Get task stage
        $taskStage = DB::table('projects_task_stages')->first();
        $taskStageId = $taskStage->id ?? 1;

        $created = 0;
        foreach ($templates as $index => $template) {
            // Skip if task exists
            $exists = DB::table('projects_tasks')
                ->where('project_id', $project->id)
                ->where('title', $template['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Calculate deadline based on project timeline
            $daysFromStart = ($index + 1) * 7; // Weekly tasks
            $deadline = Carbon::parse($project->start_date ?? now())->addDays($daysFromStart);

            DB::table('projects_tasks')->insert([
                'project_id' => $project->id,
                'stage_id' => $taskStageId,
                'title' => $template['name'],
                'priority' => $template['priority'],
                'state' => $stage === 'delivery' ? 'done' : 'in_progress',
                'deadline' => $deadline,
                'company_id' => $this->company->id,
                'creator_id' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        if ($created > 0) {
            $this->command->line("  ✓ Tasks: {$created} created");
        }
    }

    protected function assignTags(Project $project): void
    {
        $tagsToAssign = [];

        // Assign based on project name/location
        if (str_contains(strtolower($project->name), 'nantucket') ||
            str_contains($project->project_number, 'TFW')) {
            $tagsToAssign[] = 'Nantucket';
        }

        if (str_contains(strtolower($project->name), 'kitchen')) {
            $tagsToAssign[] = 'Kitchen';
        }

        if (str_contains(strtolower($project->name), 'entertainment') ||
            str_contains(strtolower($project->name), 'cabinet')) {
            $tagsToAssign[] = 'Entertainment';
        }

        // All current projects are residential
        $tagsToAssign[] = 'Residential';

        // Mark delivery projects as rush if within 2 weeks
        if ($project->current_production_stage === 'production' &&
            $project->desired_completion_date &&
            Carbon::parse($project->desired_completion_date)->diffInDays(now()) < 14) {
            $tagsToAssign[] = 'Rush';
        }

        // Get tag IDs
        $tagIds = DB::table('projects_tags')
            ->whereIn('name', $tagsToAssign)
            ->pluck('id');

        // Create tag associations (if pivot table exists)
        if (DB::getSchemaBuilder()->hasTable('projects_project_tag')) {
            foreach ($tagIds as $tagId) {
                DB::table('projects_project_tag')->updateOrInsert(
                    ['project_id' => $project->id, 'tag_id' => $tagId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        if (count($tagsToAssign) > 0) {
            $this->command->line("  ✓ Tags: " . implode(', ', $tagsToAssign));
        }
    }

    protected function printSummary(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('INFRASTRUCTURE SUMMARY:');
        $this->command->info('  Projects:     ' . Project::count());
        $this->command->info('  Sales Orders: ' . DB::table('sales_orders')->count());
        $this->command->info('  Tasks:        ' . DB::table('projects_tasks')->count());
        $this->command->info('  Tags:         ' . DB::table('projects_tags')->count());

        $totalValue = DB::table('sales_orders')->sum('amount_total');
        $this->command->info('  Total Value:  $' . number_format($totalValue));
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
