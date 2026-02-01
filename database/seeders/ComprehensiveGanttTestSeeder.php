<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Project\Models\HardwareRequirement;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Product\Models\Product;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\Department;
use Webkul\Recruitment\Models\JobPosition;

/**
 * Comprehensive Gantt Test Seeder
 *
 * Creates a complete, realistic sample dataset for testing the Gantt chart
 * with projects in all 5 production stages and proper gate field population.
 *
 * Run with: php artisan db:seed --class=ComprehensiveGanttTestSeeder
 */
class ComprehensiveGanttTestSeeder extends Seeder
{
    protected Carbon $now;
    protected ?Company $company;
    protected ?User $admin;
    protected array $stages = [];
    protected $milestoneTemplates;
    protected array $createdProjects = [];
    protected array $hardwareProducts = [];

    /**
     * Project definitions with stage-appropriate data
     *
     * Timeline Design for Gantt Testing:
     * ==================================
     * TODAY = reference point (vertical line in Gantt)
     *
     * PAST (completed): Bars entirely left of today
     * PRESENT (active): Bars crossing today's line
     * FUTURE (upcoming): Bars entirely right of today
     *
     * This gives us good visual testing across the timeline:
     * - 2 COMPLETE projects: ended 2-4 weeks ago (PAST)
     * - 2 DELIVERY projects: ending this week or next (crossing TODAY)
     * - 3 PRODUCTION projects: middle of timeline, some overlap (crossing TODAY)
     * - 2 SOURCING projects: recently started, ending in future (crossing TODAY)
     * - 2 DESIGN projects: starting soon, all in future (FUTURE)
     * - 2 DISCOVERY projects: starting next week+ (FUTURE)
     * - 2 UNSCHEDULED: no dates (for drag-drop testing)
     */
    protected array $projectDefinitions = [
        // =====================================================================
        // UNSCHEDULED (2 projects) - No dates, for drag-drop testing
        // =====================================================================
        [
            'customer' => 'Garcia',
            'room_type' => 'Kitchen',
            'stage' => 'discovery',
            'linear_feet' => 42,
            'unscheduled' => true,
            'days_offset' => null,
            'duration' => 90,
        ],
        [
            'customer' => 'Wilson',
            'room_type' => 'Mudroom',
            'stage' => 'discovery',
            'linear_feet' => 18,
            'unscheduled' => true,
            'days_offset' => null,
            'duration' => 45,
        ],

        // =====================================================================
        // COMPLETE (2 projects) - PAST: Entirely before today
        // These ended 2-4 weeks ago, providing historical reference
        // =====================================================================
        [
            'customer' => 'Davis',
            'room_type' => 'Kitchen',
            'stage' => 'delivery',
            'is_complete' => true,
            'linear_feet' => 48,
            'unscheduled' => false,
            'days_offset' => -110, // Started 110 days ago
            'duration' => 90,      // Ended 20 days ago
        ],
        [
            'customer' => 'Miller',
            'room_type' => 'Butler Pantry',
            'stage' => 'delivery',
            'is_complete' => true,
            'linear_feet' => 20,
            'unscheduled' => false,
            'days_offset' => -75, // Started 75 days ago
            'duration' => 45,     // Ended 30 days ago
        ],

        // =====================================================================
        // DELIVERY (2 projects) - PRESENT: Crossing today, ending soon
        // Started 6-8 weeks ago, ending this week or next
        // =====================================================================
        [
            'customer' => 'Thompson',
            'room_type' => 'Kitchen',
            'stage' => 'delivery',
            'linear_feet' => 52,
            'unscheduled' => false,
            'days_offset' => -56, // Started 8 weeks ago
            'duration' => 60,     // Ends in 4 days
        ],
        [
            'customer' => 'Lee',
            'room_type' => 'Laundry Room',
            'stage' => 'delivery',
            'linear_feet' => 16,
            'unscheduled' => false,
            'days_offset' => -42, // Started 6 weeks ago
            'duration' => 50,     // Ends in 8 days
        ],

        // =====================================================================
        // PRODUCTION (3 projects) - PRESENT: Overlapping, crossing today
        // Started 4-6 weeks ago, ending 2-4 weeks from now
        // Creates capacity overlap for testing warnings
        // =====================================================================
        [
            'customer' => 'Johnson',
            'room_type' => 'Kitchen',
            'stage' => 'production',
            'linear_feet' => 45,
            'unscheduled' => false,
            'days_offset' => -35, // Started 5 weeks ago
            'duration' => 56,     // Ends in 3 weeks
        ],
        [
            'customer' => 'Martinez',
            'room_type' => 'Kitchen',
            'stage' => 'production',
            'linear_feet' => 38,
            'unscheduled' => false,
            'days_offset' => -28, // Started 4 weeks ago
            'duration' => 49,     // Ends in 3 weeks
        ],
        [
            'customer' => "O'Brien",
            'room_type' => 'Kitchen',
            'stage' => 'production',
            'linear_feet' => 28,
            'unscheduled' => false,
            'days_offset' => -21, // Started 3 weeks ago
            'duration' => 42,     // Ends in 3 weeks
        ],

        // =====================================================================
        // SOURCING (2 projects) - PRESENT: Recently started, ending in future
        // Started 2-3 weeks ago, ending 3-5 weeks from now
        // =====================================================================
        [
            'customer' => 'Williams',
            'room_type' => 'Master Bathroom',
            'stage' => 'sourcing',
            'linear_feet' => 18,
            'unscheduled' => false,
            'days_offset' => -21, // Started 3 weeks ago
            'duration' => 56,     // Ends in 5 weeks
        ],
        [
            'customer' => 'Anderson',
            'room_type' => 'Walk-in Closet',
            'stage' => 'sourcing',
            'linear_feet' => 35,
            'unscheduled' => false,
            'days_offset' => -14, // Started 2 weeks ago
            'duration' => 49,     // Ends in 5 weeks
        ],

        // =====================================================================
        // DESIGN (2 projects) - FUTURE: Starting this week, all in future
        // Just started or starting in a few days
        // =====================================================================
        [
            'customer' => 'Chen',
            'room_type' => 'Master Bathroom',
            'stage' => 'design',
            'linear_feet' => 15,
            'unscheduled' => false,
            'days_offset' => -3,  // Started 3 days ago
            'duration' => 42,     // Ends in ~6 weeks
        ],
        [
            'customer' => 'Patel',
            'room_type' => 'Home Office',
            'stage' => 'design',
            'linear_feet' => 22,
            'unscheduled' => false,
            'days_offset' => 2,   // Starts in 2 days
            'duration' => 49,     // Ends in ~7 weeks
        ],

        // =====================================================================
        // DISCOVERY (2 projects) - FUTURE: Starting next week+
        // Upcoming projects, entirely in the future
        // =====================================================================
        [
            'customer' => 'Baker',
            'room_type' => 'Kitchen',
            'stage' => 'discovery',
            'linear_feet' => 38,
            'unscheduled' => false,
            'days_offset' => 7,   // Starts in 1 week
            'duration' => 70,     // Ends in ~11 weeks
        ],
        [
            'customer' => 'Campbell',
            'room_type' => 'Home Office',
            'stage' => 'discovery',
            'linear_feet' => 24,
            'unscheduled' => false,
            'days_offset' => 14,  // Starts in 2 weeks
            'duration' => 56,     // Ends in ~10 weeks
        ],
    ];

    /**
     * Room type to configuration mapping
     */
    protected array $roomTypeConfig = [
        'Kitchen' => [
            'room_type' => 'kitchen',
            'room_code' => 'K',
            'locations' => ['Sink Wall', 'Range Wall', 'Island'],
            'runs_per_location' => ['base' => 1, 'wall' => 1],
        ],
        'Master Bathroom' => [
            'room_type' => 'bathroom',
            'room_code' => 'BTH',
            'locations' => ['Vanity Wall'],
            'runs_per_location' => ['base' => 1],
        ],
        'Home Office' => [
            'room_type' => 'office',
            'room_code' => 'OFF',
            'locations' => ['Desk Wall', 'Storage Wall'],
            'runs_per_location' => ['base' => 1, 'wall' => 1],
        ],
        'Walk-in Closet' => [
            'room_type' => 'closet',
            'room_code' => 'CLO',
            'locations' => ['Left Wall', 'Back Wall', 'Right Wall'],
            'runs_per_location' => ['base' => 1],
        ],
        'Laundry Room' => [
            'room_type' => 'laundry',
            'room_code' => 'LAU',
            'locations' => ['Appliance Wall'],
            'runs_per_location' => ['base' => 1, 'wall' => 1],
        ],
        'Mudroom' => [
            'room_type' => 'mudroom',
            'room_code' => 'MUD',
            'locations' => ['Entry Wall'],
            'runs_per_location' => ['base' => 1, 'tall' => 1],
        ],
        'Butler Pantry' => [
            'room_type' => 'pantry',
            'room_code' => 'PAN',
            'locations' => ['Counter Wall', 'Storage Wall'],
            'runs_per_location' => ['base' => 1, 'wall' => 1, 'tall' => 1],
        ],
    ];

    /**
     * Connecticut addresses for realistic data
     */
    protected array $ctAddresses = [
        ['street' => '125 Maple Street', 'city' => 'Greenwich', 'zip' => '06830'],
        ['street' => '87 Oak Avenue', 'city' => 'Stamford', 'zip' => '06902'],
        ['street' => '234 Harbor Road', 'city' => 'Westport', 'zip' => '06880'],
        ['street' => '56 Hillside Drive', 'city' => 'New Canaan', 'zip' => '06840'],
        ['street' => '412 Riverside Lane', 'city' => 'Darien', 'zip' => '06820'],
        ['street' => '78 Beach Road', 'city' => 'Fairfield', 'zip' => '06824'],
        ['street' => '190 Forest Hill', 'city' => 'Norwalk', 'zip' => '06851'],
        ['street' => '345 Country Club Road', 'city' => 'Ridgefield', 'zip' => '06877'],
        ['street' => '23 Sunset Boulevard', 'city' => 'Wilton', 'zip' => '06897'],
        ['street' => '67 Mill Pond Road', 'city' => 'Weston', 'zip' => '06883'],
        ['street' => '158 Main Street', 'city' => 'Southport', 'zip' => '06890'],
        ['street' => '89 Saugatuck Avenue', 'city' => 'Westport', 'zip' => '06880'],
        ['street' => '432 Post Road', 'city' => 'Cos Cob', 'zip' => '06807'],
        ['street' => '76 Tokeneke Road', 'city' => 'Darien', 'zip' => '06820'],
        ['street' => '211 Compo Beach Road', 'city' => 'Westport', 'zip' => '06880'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->now = Carbon::now();

        $this->command->info("\n=== Comprehensive Gantt Test Seeder ===\n");

        DB::beginTransaction();

        try {
            $this->loadPrerequisites();
            $this->ensureProjectStages();
            $this->loadMilestoneTemplates();
            $this->loadHardwareProducts();
            $this->createShayneEmployee();

            $this->command->info("Creating 15 test projects across all production stages...\n");

            foreach ($this->projectDefinitions as $index => $definition) {
                $project = $this->createProjectWithFullHierarchy($definition, $index);
                $this->createdProjects[$definition['customer']] = $project;
            }

            // Create project dependencies for Gantt visualization
            $this->createProjectDependencies();

            DB::commit();

            $this->command->info("\n=== Seeding Complete ===");
            $this->command->info("Reference Date: " . $this->now->format('Y-m-d') . " (TODAY)");
            $this->command->info("");
            $this->command->info("Created 15 projects with timeline distribution:");
            $this->command->info("");
            $this->command->info("  PAST (completed before today):");
            $this->command->info("    - Davis Kitchen: Complete, ended ~3 weeks ago");
            $this->command->info("    - Miller Butler Pantry: Complete, ended ~4 weeks ago");
            $this->command->info("");
            $this->command->info("  PRESENT (crossing today's line):");
            $this->command->info("    - Thompson Kitchen: Delivery, ends in ~4 days");
            $this->command->info("    - Lee Laundry Room: Delivery, ends in ~8 days");
            $this->command->info("    - Johnson Kitchen: Production, ends in ~3 weeks");
            $this->command->info("    - Martinez Kitchen: Production, ends in ~3 weeks");
            $this->command->info("    - O'Brien Kitchen: Production, ends in ~3 weeks");
            $this->command->info("    - Williams Master Bathroom: Sourcing, ends in ~5 weeks");
            $this->command->info("    - Anderson Walk-in Closet: Sourcing, ends in ~5 weeks");
            $this->command->info("    - Chen Master Bathroom: Design, started 3 days ago");
            $this->command->info("");
            $this->command->info("  FUTURE (starting after today):");
            $this->command->info("    - Patel Home Office: Design, starts in 2 days");
            $this->command->info("    - Baker Kitchen: Discovery, starts in 1 week");
            $this->command->info("    - Campbell Home Office: Discovery, starts in 2 weeks");
            $this->command->info("");
            $this->command->info("  UNSCHEDULED (no dates, for drag-drop testing):");
            $this->command->info("    - Garcia Kitchen");
            $this->command->info("    - Wilson Mudroom");
            $this->command->info("");
            $this->command->info("Each project includes:");
            $this->command->info("  - Sales order with appropriate payment status");
            $this->command->info("  - Room → Location → Cabinet Run → Cabinet hierarchy");
            $this->command->info("  - Doors & drawers with Blum hardware requirements");
            $this->command->info("  - 22 milestones with stage-appropriate completion");
            $this->command->info("  - 22 tasks distributed across 5 task stages");
            $this->command->info("  - Gate fields populated based on production stage");
            $this->command->info("");
            $this->command->info("Timeline spans: ~4 months ago → ~3 months ahead");
            $this->command->info("This ensures good Gantt chart testing across past/present/future.\n");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Seeding failed: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Load prerequisite data (company, admin user)
     */
    protected function loadPrerequisites(): void
    {
        $this->command->info("Loading prerequisites...");

        $this->company = Company::first();
        if (!$this->company) {
            $this->command->error("No company found. Please run initial setup first.");
            throw new \RuntimeException("No company found");
        }

        $this->admin = User::first();
        if (!$this->admin) {
            $this->command->error("No user found. Please run initial setup first.");
            throw new \RuntimeException("No user found");
        }

        $this->command->info("  ✓ Using company: {$this->company->name}");
        $this->command->info("  ✓ Using admin: {$this->admin->name}");
    }

    /**
     * Ensure all production stages exist
     */
    protected function ensureProjectStages(): void
    {
        $this->command->info("Ensuring project stages exist...");

        $stageData = [
            ['name' => 'Discovery', 'stage_key' => 'discovery', 'color' => '#8b5cf6', 'sort' => 1],
            ['name' => 'Design', 'stage_key' => 'design', 'color' => '#3b82f6', 'sort' => 2],
            ['name' => 'Sourcing', 'stage_key' => 'sourcing', 'color' => '#f59e0b', 'sort' => 3],
            ['name' => 'Production', 'stage_key' => 'production', 'color' => '#10b981', 'sort' => 4],
            ['name' => 'Delivery', 'stage_key' => 'delivery', 'color' => '#6366f1', 'sort' => 5],
            ['name' => 'Complete', 'stage_key' => 'complete', 'color' => '#9ca3af', 'sort' => 6],
        ];

        foreach ($stageData as $data) {
            $stage = ProjectStage::firstOrCreate(
                ['stage_key' => $data['stage_key']],
                [
                    'name' => $data['name'],
                    'color' => $data['color'],
                    'sort' => $data['sort'],
                    'is_active' => true,
                    'is_collapsed' => false,
                    'company_id' => $this->company->id,
                    'creator_id' => $this->admin->id,
                ]
            );
            $this->stages[$data['stage_key']] = $stage;
        }

        $this->command->info("  ✓ " . count($this->stages) . " stages configured");
    }

    /**
     * Load milestone templates
     */
    protected function loadMilestoneTemplates(): void
    {
        $this->command->info("Loading milestone templates...");

        $this->milestoneTemplates = MilestoneTemplate::where('is_active', true)
            ->orderBy('relative_days')
            ->get();

        if ($this->milestoneTemplates->isEmpty()) {
            $this->command->warn("  ! No milestone templates found. Running MilestoneTemplateSeeder...");
            $this->call(\Webkul\Project\Database\Seeders\MilestoneTemplateSeeder::class);
            $this->milestoneTemplates = MilestoneTemplate::where('is_active', true)
                ->orderBy('relative_days')
                ->get();
        }

        $this->command->info("  ✓ " . $this->milestoneTemplates->count() . " milestone templates loaded");
    }

    /**
     * Load hardware products (Blum hinges and slides)
     */
    protected function loadHardwareProducts(): void
    {
        $this->command->info("Loading hardware products...");

        // Load Blum hinges
        $hinges = Product::where('name', 'like', '%Blum%')
            ->where(function ($q) {
                $q->where('name', 'like', '%hinge%')
                  ->orWhere('name', 'like', '%Hinge%')
                  ->orWhere('name', 'like', '%inserta%')
                  ->orWhere('name', 'like', '%INSERTA%');
            })
            ->get();

        // Load Blum slides
        $slides = Product::where('name', 'like', '%Blum%')
            ->where(function ($q) {
                $q->where('name', 'like', '%slide%')
                  ->orWhere('name', 'like', '%Slide%')
                  ->orWhere('name', 'like', '%runner%');
            })
            ->get();

        // Load hinge mounting plates
        $plates = Product::where('name', 'like', '%Blum%')
            ->where('name', 'like', '%Mounting%')
            ->get();

        $this->hardwareProducts = [
            'hinges' => $hinges->isNotEmpty() ? $hinges : collect(),
            'slides' => $slides->isNotEmpty() ? $slides : collect(),
            'plates' => $plates->isNotEmpty() ? $plates : collect(),
        ];

        $this->command->info("  ✓ " . $hinges->count() . " hinge products loaded");
        $this->command->info("  ✓ " . $slides->count() . " slide products loaded");
        $this->command->info("  ✓ " . $plates->count() . " mounting plate products loaded");
    }

    /**
     * Create Shayne Dygert employee
     */
    protected function createShayneEmployee(): void
    {
        $this->command->info("Creating Shayne Dygert employee...");

        // Get or create department
        $department = Department::firstOrCreate(
            ['name' => 'Production'],
            [
                'company_id' => $this->company->id,
                'manager_id' => null,
                'parent_id' => null,
                'color' => '#10b981',
                'creator_id' => $this->admin->id,
            ]
        );

        // Get or create job position using the recruitments model
        $jobPosition = null;
        try {
            $jobPosition = JobPosition::firstOrCreate(
                ['name' => 'Cabinet Maker'],
                [
                    'company_id' => $this->company->id,
                    'department_id' => $department->id,
                    'is_active' => true,
                    'description' => 'Skilled cabinet maker responsible for assembly, finishing, and quality control',
                    'creator_id' => $this->admin->id,
                ]
            );
        } catch (\Exception $e) {
            // Job position model may not exist, use raw DB
            $jobPosition = DB::table('employees_job_positions')->where('name', 'Cabinet Maker')->first();
            if (!$jobPosition) {
                $jobId = DB::table('employees_job_positions')->insertGetId([
                    'name' => 'Cabinet Maker',
                    'company_id' => $this->company->id,
                    'department_id' => $department->id,
                    'is_active' => true,
                    'description' => 'Skilled cabinet maker responsible for assembly, finishing, and quality control',
                    'creator_id' => $this->admin->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $jobPosition = (object)['id' => $jobId];
            }
        }

        // Check if employee already exists
        $existingEmployee = Employee::where('name', 'Shayne Dygert')->first();
        if ($existingEmployee) {
            $this->command->info("  ✓ Shayne Dygert already exists (ID: {$existingEmployee->id})");
            return;
        }

        // Create the employee
        $employee = Employee::create([
            'name' => 'Shayne Dygert',
            'work_email' => 'shayne@tcswoodwork.com',
            'employee_type' => 'employee',
            'department_id' => $department->id,
            'job_id' => $jobPosition->id ?? null,
            'company_id' => $this->company->id,
            'is_active' => true,
            'time_zone' => 'America/New_York',
            'creator_id' => $this->admin->id,
        ]);

        $this->command->info("  ✓ Created Shayne Dygert (ID: {$employee->id})");
    }

    /**
     * Create a project with full hierarchy
     */
    protected function createProjectWithFullHierarchy(array $definition, int $index): Project
    {
        $address = $this->ctAddresses[$index % count($this->ctAddresses)];
        $projectName = "{$definition['customer']} {$definition['room_type']}";

        $this->command->info("Creating: {$projectName} [{$definition['stage']}]");

        // 1. Create or get customer
        $customer = $this->getOrCreateCustomer($definition['customer'], $address);

        // 2. Create project
        $project = $this->createProject($definition, $customer, $address);

        // 3. Create sales order with appropriate timestamps
        $salesOrder = $this->createSalesOrder($project, $customer, $definition);

        // 4. Create room hierarchy
        $this->createRoomHierarchy($project, $definition);

        // 5. Create milestones
        $this->createMilestones($project, $definition);

        // 6. Create task stages and tasks with dependencies
        $this->createTaskStagesAndTasks($project, $definition);

        // 7. Set gate fields based on stage
        $this->setGateFields($project, $salesOrder, $definition);

        $this->command->info("  ✓ Created project ID: {$project->id}");

        return $project;
    }

    /**
     * Get or create a customer partner
     */
    protected function getOrCreateCustomer(string $lastName, array $address): Partner
    {
        $email = strtolower($lastName) . '@example.com';

        return Partner::firstOrCreate(
            ['email' => $email],
            [
                'name' => "The {$lastName} Family",
                'account_type' => 'individual',
                'sub_type' => 'customer',
                'phone' => '203-555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'street1' => $address['street'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state_id' => DB::table('states')->where('code', 'CT')->value('id'),
                'country_id' => DB::table('countries')->where('code', 'US')->value('id') ?? 233,
                'is_active' => true,
                'customer_rank' => 1,
            ]
        );
    }

    /**
     * Create project record
     */
    protected function createProject(array $definition, Partner $customer, array $address): Project
    {
        $stage = $this->stages[$definition['stage']] ?? $this->stages['discovery'];

        // Calculate dates
        $startDate = null;
        $endDate = null;

        if (!$definition['unscheduled'] && $definition['days_offset'] !== null) {
            $startDate = $this->now->copy()->addDays($definition['days_offset']);
            $endDate = $startDate->copy()->addDays($definition['duration']);
        }

        // Generate project number
        $streetCode = preg_replace('/[^a-zA-Z0-9]/', '', $address['street']);
        $streetCode = substr($streetCode, 0, 15);
        $existingCount = Project::where('company_id', $this->company->id)->count();
        $projectNumber = "TCS-" . str_pad($existingCount + 501, 3, '0', STR_PAD_LEFT) . "-{$streetCode}";

        return Project::create([
            'name' => "{$definition['customer']} {$definition['room_type']}",
            'project_number' => $projectNumber,
            'project_type' => 'residential',
            'lead_source' => 'referral',
            'budget_range' => $definition['linear_feet'] > 40 ? 'premium' : 'standard',
            'complexity_score' => $this->calculateComplexity($definition),
            'description' => "Custom cabinetry project for {$definition['room_type']} at {$address['street']}, {$address['city']}",
            'visibility' => 'internal',
            'color' => $stage->color,
            'start_date' => $startDate,
            'desired_completion_date' => $endDate,
            'estimated_linear_feet' => $definition['linear_feet'],
            'allow_timesheets' => true,
            'allow_milestones' => true,
            'allow_task_dependencies' => true,
            'is_active' => true,
            'is_converted' => true,
            'converted_at' => $this->now->copy()->subDays(abs($definition['days_offset'] ?? 30) + 7),
            'current_production_stage' => $definition['stage'],
            'stage_id' => $stage->id,
            'partner_id' => $customer->id,
            'company_id' => $this->company->id,
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);
    }

    /**
     * Create sales order with payment timestamps based on stage
     */
    protected function createSalesOrder(Project $project, Partner $customer, array $definition): Order
    {
        $stageIndex = $this->getStageIndex($definition);
        $ratePerLf = $definition['linear_feet'] > 40 ? 450 : 380;
        $totalAmount = $definition['linear_feet'] * $ratePerLf;
        $depositAmount = $totalAmount * 0.30;

        // Calculate timestamps based on stage
        $proposalAcceptedAt = null;
        $depositPaidAt = null;
        $finalPaidAt = null;

        if ($project->start_date) {
            $proposalAcceptedAt = $project->start_date->copy()->subDays(7);

            // Deposit paid if past discovery
            if ($stageIndex >= 1) { // Design or later
                $depositPaidAt = $project->start_date->copy()->subDays(3);
            }

            // Final paid if complete
            if ($stageIndex >= 5) { // Complete
                $finalPaidAt = $project->desired_completion_date?->copy()->addDays(5);
            }
        }

        return Order::create([
            'company_id' => $this->company->id,
            'partner_id' => $customer->id,
            'partner_invoice_id' => $customer->id,
            'partner_shipping_id' => $customer->id,
            'currency_id' => DB::table('currencies')->where('name', 'USD')->value('id') ?? DB::table('currencies')->first()?->id ?? 1,
            'project_id' => $project->id,
            'name' => "{$project->project_number}-SO1",
            'state' => $stageIndex >= 1 ? 'sale' : 'draft',
            'date_order' => $proposalAcceptedAt ?? $this->now,
            'amount_untaxed' => $totalAmount,
            'amount_tax' => 0,
            'amount_total' => $totalAmount,
            'locked' => false,
            'require_signature' => true,
            'require_payment' => true,
            'invoice_status' => $stageIndex >= 5 ? 'invoiced' : 'no',
            'woodworking_order_type' => 'full_project',
            'deposit_percentage' => 30,
            'deposit_amount' => $depositAmount,
            'balance_percentage' => 70,
            'balance_amount' => $totalAmount - $depositAmount,
            'proposal_status' => $stageIndex >= 1 ? 'accepted' : 'sent',
            'proposal_sent_at' => $proposalAcceptedAt?->copy()->subDays(3),
            'proposal_accepted_at' => $proposalAcceptedAt,
            'production_authorized' => $stageIndex >= 1,
            'production_authorized_at' => $depositPaidAt,
            'deposit_paid_at' => $depositPaidAt,
            'final_paid_at' => $finalPaidAt,
        ]);
    }

    /**
     * Create room hierarchy (Room -> Locations -> Runs -> Cabinets)
     */
    protected function createRoomHierarchy(Project $project, array $definition): void
    {
        $roomConfig = $this->roomTypeConfig[$definition['room_type']] ?? $this->roomTypeConfig['Kitchen'];
        $stageIndex = $this->getStageIndex($definition);

        // Create room
        $room = Room::create([
            'project_id' => $project->id,
            'name' => $definition['room_type'],
            'room_type' => $roomConfig['room_type'],
            'room_code' => $roomConfig['room_code'] . '1',
            'floor_number' => '1',
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        $totalLf = $definition['linear_feet'];
        $lfPerLocation = $totalLf / count($roomConfig['locations']);
        $cabinetPosition = 0;

        foreach ($roomConfig['locations'] as $locIndex => $locationName) {
            // Create location
            $location = RoomLocation::create([
                'room_id' => $room->id,
                'name' => $locationName,
                'location_type' => $this->getLocationType($locationName),
                'sequence' => $locIndex + 1,
                'sort_order' => $locIndex + 1,
                'material_type' => 'paint_grade',
                'wood_species' => 'hard_maple',
                'door_style' => 'shaker',
                'has_face_frame' => true,
                'soft_close_doors' => true,
                'soft_close_drawers' => true,
                'creator_id' => $this->admin->id,
            ]);

            // Create cabinet runs
            foreach ($roomConfig['runs_per_location'] as $runType => $count) {
                for ($runNum = 1; $runNum <= $count; $runNum++) {
                    $run = CabinetRun::create([
                        'room_location_id' => $location->id,
                        'name' => ucfirst($runType) . ' Cabinets',
                        'run_type' => $runType,
                        'run_code' => strtoupper(substr($runType, 0, 1)) . $runNum,
                        'total_linear_feet' => $lfPerLocation / count($roomConfig['runs_per_location']),
                        'sort_order' => $runNum,
                        'creator_id' => $this->admin->id,
                    ]);

                    // Create cabinets (2-4 per run)
                    $cabinetsPerRun = rand(2, 4);
                    $runLf = $lfPerLocation / count($roomConfig['runs_per_location']);
                    $lfPerCabinet = $runLf / $cabinetsPerRun;

                    for ($cabNum = 1; $cabNum <= $cabinetsPerRun; $cabNum++) {
                        $cabinetPosition++;
                        $widthInches = round($lfPerCabinet * 12, 0);
                        // Snap to common sizes
                        $widthInches = $this->snapToCommonWidth($widthInches);

                        // QC passed if in production or later
                        $qcPassed = $stageIndex >= 3; // Production or later

                        // For production stage, vary QC status to test partial completion
                        if ($definition['stage'] === 'production') {
                            $qcPassed = rand(0, 10) > 3; // 70% passed
                        }

                        $cabinet = Cabinet::create([
                            'project_id' => $project->id,
                            'room_id' => $room->id,
                            'cabinet_run_id' => $run->id,
                            'cabinet_number' => $cabNum,
                            'full_code' => "{$project->project_number}-{$room->room_code}-{$location->location_code}-{$run->run_code}-{$cabNum}",
                            'position_in_run' => $cabNum,
                            'length_inches' => $widthInches, // Cabinet width
                            'width_inches' => 24, // Standard depth
                            'depth_inches' => $runType === 'wall' ? 12 : 24,
                            'height_inches' => $this->getCabinetHeight($runType),
                            'linear_feet' => $widthInches / 12,
                            'quantity' => 1,
                            'qc_passed' => $qcPassed,
                            'creator_id' => $this->admin->id,
                        ]);

                        // Create doors and drawers based on cabinet type
                        $this->createDoorsAndDrawers($cabinet, $runType, $stageIndex);
                    }
                }
            }
        }
    }

    /**
     * Create doors, drawers, and hardware requirements for a cabinet
     */
    protected function createDoorsAndDrawers(Cabinet $cabinet, string $runType, int $stageIndex): void
    {
        $cabinetWidth = $cabinet->length_inches;
        $cabinetHeight = $cabinet->height_inches;

        // Determine door/drawer configuration based on cabinet width and type
        $hasDoors = true;
        $hasDrawers = $runType === 'base'; // Base cabinets have drawers
        $doorCount = 1;
        $drawerCount = 0;

        if ($cabinetWidth >= 30) {
            $doorCount = 2; // Wide cabinets have double doors
        }

        if ($hasDrawers) {
            // Standard base cabinet: 1 drawer on top, doors below
            $drawerCount = $cabinetWidth >= 30 ? 2 : 1;
        }

        // Tall cabinets (pantry) - multiple doors
        if ($runType === 'tall') {
            $doorCount = 2; // Upper and lower doors
            $hasDrawers = false;
        }

        // Wall cabinets - just doors, no drawers
        if ($runType === 'wall') {
            $hasDrawers = false;
            $drawerCount = 0;
        }

        // Create doors
        for ($doorNum = 1; $doorNum <= $doorCount; $doorNum++) {
            $doorWidth = $doorCount > 1 ? ($cabinetWidth / 2) - 0.125 : $cabinetWidth - 0.25;
            $doorHeight = $hasDrawers ? ($cabinetHeight - 34.75 + 24) : ($cabinetHeight - 1.5); // Account for toe kick on base

            if ($runType === 'wall') {
                $doorHeight = $cabinetHeight - 1.5;
            }

            // Hardware installation status based on production stage
            $hardwareInstalled = $stageIndex >= 3;
            $qcPassed = $stageIndex >= 3 && rand(0, 10) > 2;

            $door = Door::create([
                'cabinet_id' => $cabinet->id,
                'door_number' => $doorNum,
                'door_name' => "Door {$doorNum}",
                'full_code' => "{$cabinet->full_code}-DOOR{$doorNum}",
                'sort_order' => $doorNum,
                'width_inches' => round($doorWidth, 4),
                'height_inches' => round($doorHeight, 4),
                'thickness_inches' => 0.75,
                'hinge_type' => 'blum_clip_top',
                'hinge_quantity' => $doorHeight > 30 ? 3 : 2, // Taller doors need 3 hinges
                'hinge_side' => $doorNum % 2 === 1 ? 'left' : 'right',
                'finish_type' => 'paint_grade',
                'qc_passed' => $qcPassed,
                'hardware_installed_at' => $hardwareInstalled ? $cabinet->created_at : null,
            ]);

            // Create hardware requirements for hinges
            $this->createHingeRequirements($door, $cabinet, $stageIndex);
        }

        // Create drawers
        for ($drawerNum = 1; $drawerNum <= $drawerCount; $drawerNum++) {
            $drawerWidth = $cabinetWidth - 0.5; // Allow for face frame overlap
            $drawerHeight = 6; // Standard 6" drawer front

            // Hardware installation status based on production stage
            $hardwareInstalled = $stageIndex >= 3;
            $qcPassed = $stageIndex >= 3 && rand(0, 10) > 2;

            $drawer = Drawer::create([
                'cabinet_id' => $cabinet->id,
                'drawer_number' => $drawerNum,
                'drawer_name' => "Drawer {$drawerNum}",
                'full_code' => "{$cabinet->full_code}-DRW{$drawerNum}",
                'sort_order' => $drawerNum,
                'drawer_position' => $drawerNum === 1 ? 'upper' : 'lower',
                'front_width_inches' => round($drawerWidth, 4),
                'front_height_inches' => $drawerHeight,
                'box_width_inches' => round($drawerWidth - 1.5, 4), // Account for slides
                'box_height_inches' => 4.5, // Standard drawer box height
                'box_depth_inches' => 18, // Standard 18" depth
                'slide_length_inches' => 18,
                'opening_width_inches' => round($drawerWidth, 4),
                'opening_height_inches' => $drawerHeight + 0.5,
                'qc_passed' => $qcPassed,
                'slides_installed_at' => $hardwareInstalled ? $cabinet->created_at : null,
            ]);

            // Create hardware requirements for slides
            $this->createSlideRequirements($drawer, $cabinet, $stageIndex);
        }
    }

    /**
     * Create hinge hardware requirements for a door
     */
    protected function createHingeRequirements(Door $door, Cabinet $cabinet, int $stageIndex): void
    {
        if ($this->hardwareProducts['hinges']->isEmpty()) {
            return;
        }

        // Get a random hinge product
        $hingeProduct = $this->hardwareProducts['hinges']->random();
        $hingeQty = $door->hinge_quantity ?? 2;

        // Calculate allocation status based on production stage
        $allocated = $stageIndex >= 2; // Allocated in sourcing
        $kitted = $stageIndex >= 3;    // Kitted in production
        $installed = $stageIndex >= 3 && rand(0, 10) > 3;

        // Note: door_id column doesn't exist in hardware_requirements table yet
        // Using door_number field instead for linking
        HardwareRequirement::create([
            'cabinet_id' => $cabinet->id,
            'cabinet_run_id' => $cabinet->cabinet_run_id,
            'product_id' => $hingeProduct->id,
            'hardware_type' => 'hinge',
            'manufacturer' => 'Blum',
            'model_number' => 'CLIP top BLUMOTION',
            'quantity_required' => $hingeQty,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'door_number' => $door->door_number,
            'hinge_type' => 'soft_close',
            'hinge_opening_angle' => 110,
            'overlay_dimension_mm' => 19,
            'finish' => 'nickel',
            'unit_cost' => 4.50,
            'total_hardware_cost' => $hingeQty * 4.50,
            'install_sequence' => 1,
            'requires_jig' => true,
            'jig_name' => 'Blum hinge drilling jig',
            'hardware_allocated' => $allocated,
            'hardware_allocated_at' => $allocated ? now()->subDays(rand(5, 15)) : null,
            'hardware_kitted' => $kitted,
            'hardware_kitted_at' => $kitted ? now()->subDays(rand(1, 5)) : null,
            'hardware_installed' => $installed,
            'hardware_installed_at' => $installed ? now()->subDays(rand(0, 3)) : null,
        ]);

        // Also create mounting plate requirement
        if ($this->hardwareProducts['plates']->isNotEmpty()) {
            $plateProduct = $this->hardwareProducts['plates']->random();

            HardwareRequirement::create([
                'cabinet_id' => $cabinet->id,
                'cabinet_run_id' => $cabinet->cabinet_run_id,
                'product_id' => $plateProduct->id,
                'hardware_type' => 'hinge_plate',
                'manufacturer' => 'Blum',
                'model_number' => 'INSERTA mounting plate',
                'quantity_required' => $hingeQty,
                'unit_of_measure' => 'EA',
                'applied_to' => 'door',
                'door_number' => $door->door_number,
                'finish' => 'nickel',
                'unit_cost' => 1.25,
                'total_hardware_cost' => $hingeQty * 1.25,
                'install_sequence' => 2,
                'requires_jig' => false,
                'hardware_allocated' => $allocated,
                'hardware_allocated_at' => $allocated ? now()->subDays(rand(5, 15)) : null,
                'hardware_kitted' => $kitted,
                'hardware_kitted_at' => $kitted ? now()->subDays(rand(1, 5)) : null,
                'hardware_installed' => $installed,
                'hardware_installed_at' => $installed ? now()->subDays(rand(0, 3)) : null,
            ]);
        }
    }

    /**
     * Create slide hardware requirements for a drawer
     */
    protected function createSlideRequirements(Drawer $drawer, Cabinet $cabinet, int $stageIndex): void
    {
        if ($this->hardwareProducts['slides']->isEmpty()) {
            return;
        }

        // Get appropriate slide product based on drawer depth
        $slideProduct = $this->hardwareProducts['slides']->first(function ($product) use ($drawer) {
            $slideLength = $drawer->slide_length_inches ?? 18;
            return str_contains($product->name, "{$slideLength}") || str_contains($product->name, "{$slideLength}\"");
        }) ?? $this->hardwareProducts['slides']->random();

        // Calculate allocation status based on production stage
        $allocated = $stageIndex >= 2; // Allocated in sourcing
        $kitted = $stageIndex >= 3;    // Kitted in production
        $installed = $stageIndex >= 3 && rand(0, 10) > 3;

        // Note: drawer_id column doesn't exist in hardware_requirements table yet
        // Using drawer_number field instead for linking
        // Drawer slides come in pairs
        HardwareRequirement::create([
            'cabinet_id' => $cabinet->id,
            'cabinet_run_id' => $cabinet->cabinet_run_id,
            'product_id' => $slideProduct->id,
            'hardware_type' => 'slide',
            'manufacturer' => 'Blum',
            'model_number' => 'TANDEM plus BLUMOTION',
            'quantity_required' => 1, // 1 pair
            'unit_of_measure' => 'PR', // Pair
            'applied_to' => 'drawer',
            'drawer_number' => $drawer->drawer_number,
            'slide_type' => 'undermount',
            'slide_length_inches' => $drawer->slide_length_inches ?? 18,
            'slide_weight_capacity_lbs' => 100,
            'finish' => 'zinc',
            'unit_cost' => 42.00,
            'total_hardware_cost' => 42.00,
            'install_sequence' => 1,
            'requires_jig' => true,
            'jig_name' => 'Blum TANDEM installation jig',
            'hardware_allocated' => $allocated,
            'hardware_allocated_at' => $allocated ? now()->subDays(rand(5, 15)) : null,
            'hardware_kitted' => $kitted,
            'hardware_kitted_at' => $kitted ? now()->subDays(rand(1, 5)) : null,
            'hardware_installed' => $installed,
            'hardware_installed_at' => $installed ? now()->subDays(rand(0, 3)) : null,
        ]);
    }

    /**
     * Create milestones from templates based on project stage
     */
    protected function createMilestones(Project $project, array $definition): void
    {
        if (!$project->start_date) {
            return; // Skip milestones for unscheduled projects
        }

        $stageIndex = $this->getStageIndex($definition);
        $stageOrder = ['discovery', 'design', 'sourcing', 'production', 'delivery'];

        foreach ($this->milestoneTemplates as $template) {
            $deadline = $project->start_date->copy()->addDays($template->relative_days);
            $templateStageIndex = array_search($template->production_stage, $stageOrder);

            // Milestone is completed if its stage is before current stage
            $isCompleted = $templateStageIndex !== false && $templateStageIndex < $stageIndex;

            // If in the same stage, some milestones may be completed
            if ($templateStageIndex === $stageIndex) {
                // Earlier milestones in same stage have higher chance of being complete
                $isCompleted = rand(0, 10) < 6; // 60% chance
            }

            // Complete stage has all milestones done
            if ($stageIndex >= 5) {
                $isCompleted = true;
            }

            Milestone::create([
                'project_id' => $project->id,
                'name' => $template->name,
                'production_stage' => $template->production_stage,
                'deadline' => $deadline,
                'is_critical' => $template->is_critical,
                'description' => $template->description,
                'sort_order' => $template->sort_order,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? $deadline->copy()->subDays(rand(0, 3)) : null,
                'creator_id' => $this->admin->id,
            ]);
        }
    }

    /**
     * Create task stages and tasks with dependencies for a project
     */
    protected function createTaskStagesAndTasks(Project $project, array $definition): void
    {
        // Create task stages for the project
        $taskStageDefinitions = [
            ['name' => 'Backlog', 'is_collapsed' => true, 'sort' => 1],
            ['name' => 'To Do', 'is_collapsed' => false, 'sort' => 2],
            ['name' => 'In Progress', 'is_collapsed' => false, 'sort' => 3],
            ['name' => 'Review', 'is_collapsed' => false, 'sort' => 4],
            ['name' => 'Done', 'is_collapsed' => true, 'sort' => 5],
        ];

        $taskStages = [];
        foreach ($taskStageDefinitions as $stageData) {
            $taskStages[$stageData['name']] = TaskStage::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $stageData['name'],
                ],
                [
                    'is_active' => true,
                    'is_collapsed' => $stageData['is_collapsed'],
                    'sort' => $stageData['sort'],
                    'creator_id' => $this->admin->id,
                ]
            );
        }

        // Define tasks based on production stage workflow
        $stageIndex = $this->getStageIndex($definition);
        $taskDefinitions = $this->getTaskDefinitionsForStage($definition['stage']);

        foreach ($taskDefinitions as $taskDef) {
            // Determine which task stage this task should be in based on project's production stage
            $taskStageKey = $this->getTaskStageForProductionStage($taskDef['production_phase'], $stageIndex);
            $taskStage = $taskStages[$taskStageKey] ?? $taskStages['Backlog'];

            // Determine task state
            $state = match ($taskStageKey) {
                'Done' => 'done',
                'In Progress' => 'in_progress',
                'Review' => 'in_progress',
                default => 'pending',
            };

            Task::create([
                'project_id' => $project->id,
                'stage_id' => $taskStage->id,
                'title' => $taskDef['title'],
                'description' => $taskDef['description'] ?? null,
                'priority' => $taskDef['priority'] ?? false,
                'state' => $state,
                'allocated_hours' => $taskDef['hours'] ?? 0,
                'is_active' => true,
                'creator_id' => $this->admin->id,
            ]);
        }
    }

    /**
     * Get task definitions for a given stage
     */
    protected function getTaskDefinitionsForStage(string $stage): array
    {
        return [
            // Discovery Phase
            ['key' => 'initial_consult', 'title' => 'Initial Consultation', 'production_phase' => 'discovery', 'hours' => 2, 'priority' => true, 'depends_on' => []],
            ['key' => 'site_measure', 'title' => 'Site Measurements', 'production_phase' => 'discovery', 'hours' => 4, 'depends_on' => ['initial_consult']],
            ['key' => 'scope_define', 'title' => 'Define Project Scope', 'production_phase' => 'discovery', 'hours' => 2, 'depends_on' => ['site_measure']],

            // Design Phase
            ['key' => 'concept_design', 'title' => 'Concept Design Development', 'production_phase' => 'design', 'hours' => 8, 'priority' => true, 'depends_on' => ['scope_define']],
            ['key' => 'client_review', 'title' => 'Client Design Review', 'production_phase' => 'design', 'hours' => 2, 'depends_on' => ['concept_design']],
            ['key' => 'final_design', 'title' => 'Final Design & Redlines', 'production_phase' => 'design', 'hours' => 6, 'depends_on' => ['client_review']],
            ['key' => 'bom_generate', 'title' => 'Generate Bill of Materials', 'production_phase' => 'design', 'hours' => 4, 'depends_on' => ['final_design']],

            // Sourcing Phase
            ['key' => 'material_order', 'title' => 'Order Materials', 'production_phase' => 'sourcing', 'hours' => 4, 'priority' => true, 'depends_on' => ['bom_generate']],
            ['key' => 'hardware_order', 'title' => 'Order Hardware', 'production_phase' => 'sourcing', 'hours' => 2, 'depends_on' => ['bom_generate']],
            ['key' => 'receive_materials', 'title' => 'Receive & Stage Materials', 'production_phase' => 'sourcing', 'hours' => 4, 'depends_on' => ['material_order', 'hardware_order']],

            // Production Phase
            ['key' => 'cnc_cut', 'title' => 'CNC Cutting', 'production_phase' => 'production', 'hours' => 8, 'priority' => true, 'depends_on' => ['receive_materials']],
            ['key' => 'face_frame', 'title' => 'Face Frame Assembly', 'production_phase' => 'production', 'hours' => 6, 'depends_on' => ['cnc_cut']],
            ['key' => 'cabinet_assembly', 'title' => 'Cabinet Box Assembly', 'production_phase' => 'production', 'hours' => 12, 'depends_on' => ['face_frame']],
            ['key' => 'door_drawer', 'title' => 'Door & Drawer Production', 'production_phase' => 'production', 'hours' => 8, 'depends_on' => ['cnc_cut']],
            ['key' => 'finishing', 'title' => 'Finishing & Touch-up', 'production_phase' => 'production', 'hours' => 10, 'depends_on' => ['cabinet_assembly', 'door_drawer']],
            ['key' => 'hardware_install', 'title' => 'Hardware Installation', 'production_phase' => 'production', 'hours' => 4, 'depends_on' => ['finishing']],
            ['key' => 'qc_inspect', 'title' => 'QC Inspection', 'production_phase' => 'production', 'hours' => 2, 'priority' => true, 'depends_on' => ['hardware_install']],

            // Delivery Phase
            ['key' => 'pack_load', 'title' => 'Pack & Load for Delivery', 'production_phase' => 'delivery', 'hours' => 4, 'depends_on' => ['qc_inspect']],
            ['key' => 'delivery', 'title' => 'Deliver to Site', 'production_phase' => 'delivery', 'hours' => 4, 'priority' => true, 'depends_on' => ['pack_load']],
            ['key' => 'install_support', 'title' => 'Installation Support', 'production_phase' => 'delivery', 'hours' => 8, 'depends_on' => ['delivery']],
            ['key' => 'final_walkthrough', 'title' => 'Final Walkthrough', 'production_phase' => 'delivery', 'hours' => 2, 'depends_on' => ['install_support']],
            ['key' => 'closeout', 'title' => 'Project Closeout', 'production_phase' => 'delivery', 'hours' => 2, 'depends_on' => ['final_walkthrough']],
        ];
    }

    /**
     * Determine which task stage a task should be in based on production stage
     */
    protected function getTaskStageForProductionStage(string $taskPhase, int $currentStageIndex): string
    {
        $phaseIndices = [
            'discovery' => 0,
            'design' => 1,
            'sourcing' => 2,
            'production' => 3,
            'delivery' => 4,
        ];

        $taskPhaseIndex = $phaseIndices[$taskPhase] ?? 0;

        if ($taskPhaseIndex < $currentStageIndex) {
            return 'Done';
        } elseif ($taskPhaseIndex == $currentStageIndex) {
            // For tasks in current phase, randomly assign to In Progress, Review, or To Do
            $rand = rand(1, 10);
            if ($rand <= 3) return 'Done';
            if ($rand <= 5) return 'In Progress';
            if ($rand <= 7) return 'Review';
            return 'To Do';
        } else {
            return 'Backlog';
        }
    }

    /**
     * Set gate fields on project based on stage
     */
    protected function setGateFields(Project $project, Order $salesOrder, array $definition): void
    {
        $stageIndex = $this->getStageIndex($definition);

        $updates = [];

        // Design stage fields (set if past design)
        if ($stageIndex >= 2) { // Sourcing or later
            $designDate = $project->start_date?->copy()->addDays(21);
            $updates['design_approved_at'] = $designDate;
            $updates['redline_approved_at'] = $designDate?->copy()->addDays(3);
            $updates['design_locked_at'] = $designDate?->copy()->addDays(5);
        }

        // Sourcing stage fields (set if past sourcing)
        if ($stageIndex >= 3) { // Production or later
            $sourcingDate = $project->start_date?->copy()->addDays(42);
            $updates['materials_staged_at'] = $sourcingDate;
            $updates['all_materials_received_at'] = $sourcingDate?->copy()->subDays(7);
            $updates['procurement_locked_at'] = $sourcingDate?->copy()->addDays(2);
        }

        // Delivery stage fields
        if ($stageIndex >= 4) { // Delivery or later
            $productionDate = $project->start_date?->copy()->addDays(84);
            $updates['production_locked_at'] = $productionDate;
            $updates['bol_created_at'] = $productionDate?->copy()->addDays(3);
        }

        // Complete stage fields
        if ($stageIndex >= 5) { // Complete
            $deliveryDate = $project->desired_completion_date;
            $updates['delivered_at'] = $deliveryDate;
            $updates['closeout_delivered_at'] = $deliveryDate?->copy()->addDays(3);
            $updates['customer_signoff_at'] = $deliveryDate?->copy()->addDays(5);
            $updates['bol_signed_at'] = $deliveryDate;
        }

        if (!empty($updates)) {
            $project->update($updates);
        }
    }

    /**
     * Get effective stage index (0-5) accounting for is_complete flag
     * 0=discovery, 1=design, 2=sourcing, 3=production, 4=delivery, 5=complete
     */
    protected function getStageIndex(array $definition): int
    {
        if ($definition['is_complete'] ?? false) {
            return 5; // Complete
        }
        $stages = ['discovery', 'design', 'sourcing', 'production', 'delivery'];
        $index = array_search($definition['stage'], $stages);
        return $index !== false ? $index : 0;
    }

    /**
     * Calculate complexity score
     */
    protected function calculateComplexity(array $definition): int
    {
        $base = 2;
        if ($definition['linear_feet'] > 40) $base++;
        if ($definition['linear_feet'] > 50) $base++;
        if (str_contains($definition['room_type'], 'Kitchen')) $base++;
        return min($base, 5);
    }

    /**
     * Get location type from name
     */
    protected function getLocationType(string $name): string
    {
        $name = strtolower($name);
        if (str_contains($name, 'island')) return 'island';
        if (str_contains($name, 'peninsula')) return 'peninsula';
        return 'wall';
    }

    /**
     * Snap cabinet width to common sizes
     */
    protected function snapToCommonWidth(float $width): int
    {
        $commonWidths = [12, 15, 18, 21, 24, 27, 30, 33, 36, 42, 48];
        $closest = $commonWidths[0];
        $minDiff = abs($width - $closest);

        foreach ($commonWidths as $common) {
            $diff = abs($width - $common);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $common;
            }
        }

        return $closest;
    }

    /**
     * Get standard cabinet height by type
     */
    protected function getCabinetHeight(string $runType): float
    {
        return match ($runType) {
            'base' => 34.75,
            'wall' => 30,
            'tall' => 84,
            default => 34.75,
        };
    }

    /**
     * Create project dependencies for Gantt visualization
     *
     * In a real woodworking shop, project dependencies occur due to:
     * - Shared resources (CNC machine, finishing booth, install crew)
     * - Material batching (order wood together for same species)
     * - Same customer multiple rooms (coordinate delivery)
     * - Install crew availability
     */
    protected function createProjectDependencies(): void
    {
        // Check if projects_project_dependencies table exists
        if (!DB::getSchemaBuilder()->hasTable('projects_project_dependencies')) {
            return;
        }

        // SCENARIO 1: Production bottleneck - CNC machine is a shared resource
        // Martinez Kitchen must wait for Johnson Kitchen to clear CNC cutting
        // This represents shop capacity management
        if (isset($this->createdProjects['Martinez']) && isset($this->createdProjects['Johnson'])) {
            DB::table('projects_project_dependencies')->insert([
                'project_id' => $this->createdProjects['Martinez']->id,
                'depends_on_id' => $this->createdProjects['Johnson']->id,
                'dependency_type' => 'resource', // CNC machine availability
                'lag_days' => 0,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        // SCENARIO 2: Install crew sequencing
        // Lee Laundry Room delivery must wait for Thompson Kitchen install to complete
        // Can't have install crew at two sites simultaneously
        if (isset($this->createdProjects['Lee']) && isset($this->createdProjects['Thompson'])) {
            DB::table('projects_project_dependencies')->insert([
                'project_id' => $this->createdProjects['Lee']->id,
                'depends_on_id' => $this->createdProjects['Thompson']->id,
                'dependency_type' => 'crew', // Install crew availability
                'lag_days' => 1, // 1 day buffer between installs
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        // SCENARIO 3: Material batching
        // Williams Master Bathroom and Chen Master Bathroom share wood species
        // Order materials together for cost efficiency
        if (isset($this->createdProjects['Williams']) && isset($this->createdProjects['Chen'])) {
            DB::table('projects_project_dependencies')->insert([
                'project_id' => $this->createdProjects['Williams']->id,
                'depends_on_id' => $this->createdProjects['Chen']->id,
                'dependency_type' => 'material', // Material batching
                'lag_days' => 0,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }
}
