<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Vetted Projects Seeder
 *
 * Creates REAL TCS/TFW projects from the shop whiteboard (Feb 2026).
 * These are actual active projects with real linear feet estimates.
 *
 * IMPORTANT: This seeder will NOT delete existing projects by default.
 * Set SEEDER_FORCE_CLEANUP=true environment variable to enable cleanup.
 *
 * Run with: php artisan db:seed --class=VettedProjectsSeeder
 */
class VettedProjectsSeeder extends Seeder
{
    protected ?Company $company = null;
    protected ?User $admin = null;
    protected int $projectCounter = 600;

    /**
     * Current real projects from shop whiteboard (Feb 2026)
     * Linear feet values from whiteboard where visible
     */
    protected array $currentProjects = [
        // Top section - active with checkmarks
        ['name' => '43 Warrens Landing', 'linear_feet' => 245, 'stage' => 'production', 'priority' => 'high'],
        ['name' => '61 Cliff Rd', 'linear_feet' => 260, 'stage' => 'production', 'priority' => 'high'],
        ['name' => '10 Beach St', 'linear_feet' => 30, 'stage' => 'production', 'priority' => 'medium'],
        ['name' => '32 West Chester St', 'linear_feet' => 30, 'stage' => 'production', 'priority' => 'medium'],
        ['name' => '46 Milestone Rd', 'linear_feet' => 120, 'stage' => 'sourcing', 'priority' => 'high'],

        // Middle section
        ['name' => '51 Weneeder Ave', 'linear_feet' => 70, 'stage' => 'sourcing', 'priority' => 'medium'],
        ['name' => '1 Briar Patch', 'linear_feet' => 100, 'stage' => 'design', 'priority' => 'medium'],
        ['name' => '7 Shady Ln', 'linear_feet' => 100, 'stage' => 'design', 'priority' => 'medium'],
        ['name' => '75 Bayberry Ln', 'linear_feet' => 75, 'stage' => 'design', 'priority' => 'medium'],
        ['name' => 'Codfish Park', 'linear_feet' => 40, 'stage' => 'delivery', 'priority' => 'low'],
        ['name' => '12 W Sankaty Rd', 'linear_feet' => 50, 'stage' => 'delivery', 'priority' => 'low'],
        ['name' => '5 Fields Way', 'linear_feet' => 55, 'stage' => 'delivery', 'priority' => 'low'],
        ['name' => '19 Gardner Rd', 'linear_feet' => 65, 'stage' => 'delivery', 'priority' => 'low'],

        // Lower section - newer projects
        ['name' => 'Sabin Bar', 'linear_feet' => 15, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => '19 Pilgrim Rd', 'linear_feet' => 25, 'stage' => 'discovery', 'priority' => 'medium'],
        ['name' => '5 Wauwinet Rd', 'linear_feet' => 250, 'stage' => 'discovery', 'priority' => 'high'],
        ['name' => 'Shutters', 'linear_feet' => 20, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => '4 Hollister Ave', 'linear_feet' => 19, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => '9 Hawks Circle', 'linear_feet' => 30, 'stage' => 'discovery', 'priority' => 'medium'],
        ['name' => '10 Marsh Hawk Ln', 'linear_feet' => 20, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => '119 Farm Ln', 'linear_feet' => 120, 'stage' => 'discovery', 'priority' => 'high'],
        ['name' => '4 Lovers Ln', 'linear_feet' => 14, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => 'Gay St', 'linear_feet' => 75, 'stage' => 'discovery', 'priority' => 'medium'],
        ['name' => '4 Jefferson Ave', 'linear_feet' => 50, 'stage' => 'production', 'priority' => 'high'],
        ['name' => 'Ice Rink', 'linear_feet' => 30, 'stage' => 'discovery', 'priority' => 'low'],
        ['name' => '26 Fair St Corbels', 'linear_feet' => 10, 'stage' => 'discovery', 'priority' => 'low'],
    ];

    public function run(): void
    {
        // PRODUCTION GUARD - This seeder is for development/staging only
        if (app()->environment('production')) {
            $this->command->error('⛔ This seeder cannot run in production!');
            $this->command->error('   VettedProjectsSeeder is for development data only.');
            return;
        }

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║     CURRENT PROJECTS SEEDER - FROM SHOP WHITEBOARD        ║');
        $this->command->info('║                    February 2026                          ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');

        $this->company = Company::first();
        $this->admin = User::first();

        if (!$this->company || !$this->admin) {
            $this->command->error('No company or admin user found. Run base seeders first.');
            return;
        }

        // Only cleanup if explicitly requested via environment variable
        if (env('SEEDER_FORCE_CLEANUP', false)) {
            $this->command->warn('SEEDER_FORCE_CLEANUP=true - Cleaning up existing projects...');
            $this->cleanupExistingProjects();
        } else {
            $this->command->info('Preserving existing data (set SEEDER_FORCE_CLEANUP=true to cleanup)');
        }

        // Ensure stages exist
        $this->ensureStagesExist();

        // Create projects
        $created = 0;
        $skipped = 0;
        $totalLF = 0;

        $this->command->info('');
        $this->command->info('Creating ' . count($this->currentProjects) . ' projects from whiteboard...');
        $this->command->info('');

        foreach ($this->currentProjects as $projectData) {
            // Check if project already exists by name
            $exists = Project::where('name', $projectData['name'])->exists();
            if ($exists) {
                $this->command->line("  ↻ Exists: {$projectData['name']}");
                $skipped++;
                continue;
            }

            $project = $this->createProject($projectData);
            if ($project) {
                $created++;
                $totalLF += $projectData['linear_feet'];
                $this->command->info("  ✓ {$project->project_number}: {$project->name} ({$projectData['linear_feet']} LF) [{$projectData['stage']}]");
            }
        }

        $this->printSummary($created, $skipped, $totalLF);
    }

    protected function ensureStagesExist(): void
    {
        $stages = [
            ['stage_key' => 'discovery', 'name' => 'Discovery', 'color' => '#6366f1', 'sort_order' => 1],
            ['stage_key' => 'design', 'name' => 'Design', 'color' => '#8b5cf6', 'sort_order' => 2],
            ['stage_key' => 'sourcing', 'name' => 'Sourcing', 'color' => '#f59e0b', 'sort_order' => 3],
            ['stage_key' => 'production', 'name' => 'Production', 'color' => '#10b981', 'sort_order' => 4],
            ['stage_key' => 'delivery', 'name' => 'Delivery', 'color' => '#3b82f6', 'sort_order' => 5],
            ['stage_key' => 'complete', 'name' => 'Complete', 'color' => '#6b7280', 'sort_order' => 6],
        ];

        foreach ($stages as $stage) {
            ProjectStage::firstOrCreate(
                ['stage_key' => $stage['stage_key']],
                $stage
            );
        }
    }

    protected function cleanupExistingProjects(): void
    {
        $this->command->info('Cleaning up existing sample projects...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Only delete sample projects (TFW-* and TCS-0* patterns)
        $sampleProjectIds = DB::table('projects_projects')
            ->where(function ($query) {
                $query->where('project_number', 'like', 'TFW-%')
                    ->orWhere('project_number', 'like', 'TCS-0%');
            })
            ->pluck('id');

        if ($sampleProjectIds->isNotEmpty()) {
            DB::table('projects_cnc_program_parts')
                ->whereIn('cnc_program_id', function ($q) use ($sampleProjectIds) {
                    $q->select('id')->from('projects_cnc_programs')->whereIn('project_id', $sampleProjectIds);
                })->delete();
            DB::table('projects_cnc_programs')->whereIn('project_id', $sampleProjectIds)->delete();
            DB::table('projects_tasks')->whereIn('project_id', $sampleProjectIds)->delete();
            DB::table('projects_milestones')->whereIn('project_id', $sampleProjectIds)->delete();
            DB::table('projects_change_orders')->whereIn('project_id', $sampleProjectIds)->delete();

            // Delete sales order lines and orders
            $orderIds = DB::table('sales_orders')->whereIn('project_id', $sampleProjectIds)->pluck('id');
            DB::table('sales_order_lines')->whereIn('order_id', $orderIds)->delete();
            DB::table('sales_orders')->whereIn('project_id', $sampleProjectIds)->delete();

            DB::table('projects_projects')->whereIn('id', $sampleProjectIds)->delete();

            $this->command->info("  Deleted {$sampleProjectIds->count()} sample projects");
        }

        // Also delete sample customers
        DB::table('partners_partners')
            ->where('email', 'like', '%@project.tcswoodwork.com')
            ->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('  Cleanup complete');
    }

    protected function createProject(array $data): ?Project
    {
        // Get stage
        $stage = ProjectStage::where('stage_key', $data['stage'])->first();
        if (!$stage) {
            $stage = ProjectStage::first();
        }

        // Generate project number
        $projectNumber = $this->generateProjectNumber($data['name']);

        // Create or find customer
        $customerEmail = strtolower(preg_replace('/[^a-z0-9]/i', '-', $data['name'])) . '@project.tcswoodwork.com';
        $customer = Partner::firstOrCreate(
            ['email' => $customerEmail],
            [
                'name' => $data['name'] . ' Client',
                'account_type' => 'individual',
                'sub_type' => 'customer',
                'is_active' => true,
                'customer_rank' => 1,
            ]
        );

        // Calculate dates based on stage
        $startDate = $this->calculateStartDate($data['stage']);
        $deliveryDate = $this->calculateDeliveryDate($data['stage'], $startDate, $data['linear_feet']);

        return Project::create([
            'name' => $data['name'],
            'project_number' => $projectNumber,
            'project_type' => 'residential',
            'description' => "Nantucket project - {$data['linear_feet']} linear feet",
            'visibility' => 'internal',
            'color' => $stage->color ?? '#10b981',
            'start_date' => $startDate,
            'desired_completion_date' => $deliveryDate,
            'estimated_linear_feet' => $data['linear_feet'],
            'allow_timesheets' => true,
            'is_active' => true,
            'is_converted' => $data['stage'] !== 'discovery',
            'current_production_stage' => $data['stage'],
            'stage_id' => $stage->id,
            'partner_id' => $customer->id,
            'company_id' => $this->company->id,
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);
    }

    protected function generateProjectNumber(string $name): string
    {
        $this->projectCounter++;

        // Create abbreviation from name
        $words = preg_split('/\s+/', $name);
        $abbrev = '';

        if (preg_match('/^\d+/', $name, $matches)) {
            // Starts with number - use number + first word
            $abbrev = $matches[0] . strtoupper(substr($words[1] ?? 'PRJ', 0, 4));
        } else {
            // Use first letters of words
            foreach (array_slice($words, 0, 2) as $word) {
                $abbrev .= strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $word), 0, 4));
            }
        }

        $abbrev = substr($abbrev, 0, 8);

        return "TFW-{$this->projectCounter}-{$abbrev}";
    }

    protected function calculateStartDate(string $stage): Carbon
    {
        return match ($stage) {
            'discovery' => now()->subDays(rand(1, 14)),
            'design' => now()->subDays(rand(14, 28)),
            'sourcing' => now()->subDays(rand(28, 42)),
            'production' => now()->subDays(rand(42, 70)),
            'delivery' => now()->subDays(rand(70, 90)),
            default => now()->subDays(14),
        };
    }

    protected function calculateDeliveryDate(string $stage, Carbon $startDate, int $linearFeet): Carbon
    {
        // Estimate based on LF: roughly 1 week per 20 LF, minimum 2 weeks
        $weeksEstimate = max(2, ceil($linearFeet / 20));

        return match ($stage) {
            'discovery' => $startDate->copy()->addWeeks($weeksEstimate + 8),
            'design' => $startDate->copy()->addWeeks($weeksEstimate + 6),
            'sourcing' => $startDate->copy()->addWeeks($weeksEstimate + 4),
            'production' => $startDate->copy()->addWeeks($weeksEstimate + 2),
            'delivery' => $startDate->copy()->addWeeks(2),
            default => $startDate->copy()->addWeeks($weeksEstimate + 4),
        };
    }

    protected function printSummary(int $created, int $skipped, int $totalLF): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('SUMMARY:');
        $this->command->info("  Projects Created: {$created}");
        $this->command->info("  Projects Skipped: {$skipped}");
        $this->command->info("  Total Linear Feet: {$totalLF} LF");

        // Show by stage
        $byStage = collect($this->currentProjects)->groupBy('stage')->map->count();
        $this->command->info('');
        $this->command->info('By Stage:');
        foreach ($byStage as $stage => $count) {
            $this->command->info("  {$stage}: {$count}");
        }

        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
