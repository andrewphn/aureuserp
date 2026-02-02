<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Proposal Clients Seeder
 *
 * Creates real clients and projects from TCS proposal HTML files.
 * Extracts client info, pricing, and project details.
 *
 * Run with: php artisan db:seed --class=ProposalClientsSeeder
 */
class ProposalClientsSeeder extends Seeder
{
    protected Carbon $now;
    protected ?Company $company;
    protected ?User $admin;
    protected ?Country $usCountry;
    protected ?State $nyState;
    protected array $stages = [];
    protected array $createdClients = [];
    protected array $createdProjects = [];

    /**
     * Real client and project data extracted from proposals
     */
    protected array $clientData = [
        [
            // From: proposal-TCS-0504-WT-coffee-stations.html
            'client' => [
                'name' => 'Watchtower',
                'account_type' => 'company',
                'sub_type' => 'customer',
                'reference' => 'Account #13334',
                'phone' => null,
                'email' => 'furniture.dept@watchtower.org',
                'notes' => 'Furniture Department - Open Account #13334',
            ],
            'projects' => [
                [
                    'name' => 'Watchtower Coffee Stations',
                    'project_number' => 'TCS-0504-WT',
                    'description' => "Mobile Coffee Station Units for Lobby\n\n• PAT Units: Two 60\" wide mobile coffee stations\n• WRK Unit: One mobile free-standing coffee station\n• Quartz countertops with trash holes\n• Laminate on particle board, mahogany color\n• All units have lockable casters",
                    'project_type' => 'commercial',
                    'total_amount' => 14225.00,
                    'deposit_amount' => 4267.50,
                    'stage' => 'production',
                    'linear_feet' => 15,
                    'proposal_date' => '2025-01-22',
                ],
                [
                    'name' => 'Watchtower Cherry Dining Set',
                    'project_number' => 'TCS-0527-WT',
                    'description' => "Cherry Dining Table & Chairs\n\nCustom solid cherry dining table with matching chairs.\nHigh-end furniture project for Watchtower facility.",
                    'project_type' => 'commercial',
                    'total_amount' => 28500.00,
                    'deposit_amount' => 8550.00,
                    'stage' => 'design',
                    'linear_feet' => 0, // Furniture, not cabinetry
                    'proposal_date' => '2025-01-15',
                ],
                [
                    'name' => 'Watchtower Writing Desk & Media Console',
                    'project_number' => 'TCS-0534-WT',
                    'description' => "Writing Desk + Media Console\n\nTotal: $3,980\n\nCustom furniture pieces for executive office.",
                    'project_type' => 'commercial',
                    'total_amount' => 3980.00,
                    'deposit_amount' => 1194.00,
                    'stage' => 'sourcing',
                    'linear_feet' => 8,
                    'proposal_date' => '2025-02-01',
                ],
                [
                    'name' => 'Watchtower Pantry Shelving System',
                    'project_number' => 'TCS-0545-WT',
                    'description' => "Large Pantry Shelving System\n\nTotal: $54,412\n\nExtensive shelving system for facility pantry/storage area.\nMultiple sections with adjustable shelving.",
                    'project_type' => 'commercial',
                    'total_amount' => 54412.00,
                    'deposit_amount' => 16323.60,
                    'stage' => 'discovery',
                    'linear_feet' => 120,
                    'proposal_date' => '2025-02-10',
                ],
                [
                    'name' => 'Watchtower Multipurpose Closets',
                    'project_number' => 'TCS-0546-WT',
                    'description' => "Multipurpose Closet Systems\n\nCustom closet organization systems for facility.",
                    'project_type' => 'commercial',
                    'total_amount' => 18750.00,
                    'deposit_amount' => 5625.00,
                    'stage' => 'discovery',
                    'linear_feet' => 45,
                    'proposal_date' => '2025-02-15',
                ],
            ],
        ],
        [
            // From: proposal-TCS-0002-BEAST-maintenance-partition.html
            'client' => [
                'name' => 'Nick Forlano',
                'company_name' => 'Dutchess Bier Cafe',
                'account_type' => 'individual',
                'sub_type' => 'customer',
                'street1' => '1064 Main Street',
                'city' => 'Fishkill',
                'state' => 'NY',
                'zip' => '12524',
                'phone' => '917-476-7526',
                'email' => 'theduchessbeercafe@gmail.com',
                'notes' => 'Restaurant/Bar owner - Dutchess Bier Cafe',
            ],
            'projects' => [
                [
                    'name' => 'Dutchess Bier Cafe Bar Maintenance',
                    'project_number' => 'TCS-0002-BEAST',
                    'description' => "Bar Top Service & Kitchen Partition\n\n1. Bar Top Spot Sanding & Recoating - $1,275\n   • Spot sanding of elevated areas\n   • Full recoating with Rubio Monocoat system\n\n2. Kitchen Air Space Partition - $4,250\n   • Removable temporary partition\n   • Black spray-finished shaker panel design\n   • 1/2\" ultralight MDF with applied paneling",
                    'project_type' => 'commercial',
                    'total_amount' => 5525.00,
                    'deposit_amount' => 1657.50,
                    'stage' => 'design',
                    'linear_feet' => 12,
                    'proposal_date' => '2025-08-14',
                ],
            ],
        ],
        [
            // Generic commercial client for other proposals
            'client' => [
                'name' => 'TCS Commercial Projects',
                'account_type' => 'company',
                'sub_type' => 'customer',
                'email' => 'commercial@tcswoodwork.com',
                'notes' => 'Internal account for miscellaneous commercial projects',
            ],
            'projects' => [
                [
                    'name' => 'Fabric Display Cabinet',
                    'project_number' => 'TCS-0924-SBD',
                    'description' => "Custom Fabric Display Cabinet\n\nShowroom display cabinet for fabric samples.\nGlass doors, interior lighting, adjustable shelving.",
                    'project_type' => 'commercial',
                    'total_amount' => 8750.00,
                    'deposit_amount' => 2625.00,
                    'stage' => 'sourcing',
                    'linear_feet' => 8,
                    'proposal_date' => '2025-01-10',
                ],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PRODUCTION GUARD - This seeder is for development/staging only
        if (app()->environment('production')) {
            $this->command->error('⛔ This seeder cannot run in production!');
            $this->command->error('   ProposalClientsSeeder is for development data only.');
            return;
        }

        $this->now = Carbon::now();

        $this->command->info("\n=== Proposal Clients Seeder ===\n");

        DB::beginTransaction();

        try {
            $this->loadPrerequisites();
            $this->ensureProjectStages();

            foreach ($this->clientData as $data) {
                $client = $this->createClient($data['client']);

                if ($client) {
                    foreach ($data['projects'] as $projectData) {
                        $this->createProjectFromProposal($client, $projectData);
                    }
                }
            }

            DB::commit();

            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Create client from data
     */
    protected function createClient(array $data): ?Partner
    {
        $name = $data['company_name'] ?? $data['name'];

        // Check if client exists
        $existingClient = Partner::where('name', $name)
            ->orWhere('email', $data['email'] ?? null)
            ->first();

        if ($existingClient) {
            $this->command->warn("  ⚠ Using existing client: {$name}");
            return $existingClient;
        }

        $this->command->info("Creating client: {$name}");

        // Get state
        $state = null;
        if (!empty($data['state'])) {
            $state = State::where('code', $data['state'])->first();
        }

        $client = Partner::create([
            'name' => $name,
            'account_type' => $data['account_type'] ?? 'company',
            'sub_type' => $data['sub_type'] ?? 'customer',
            'reference' => $data['reference'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'street1' => $data['street1'] ?? null,
            'street2' => $data['street2'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'state_id' => $state?->id,
            'country_id' => $this->usCountry?->id,
            'comment' => $data['notes'] ?? null,
            'customer_rank' => 1,
            'is_active' => true,
            'creator_id' => $this->admin->id,
            'company_id' => $this->company->id,
        ]);

        $this->createdClients[$name] = $client;

        $this->command->info("  ✓ Created client ID: {$client->id}");

        return $client;
    }

    /**
     * Create project from proposal data
     */
    protected function createProjectFromProposal(Partner $client, array $data): ?Project
    {
        $projectName = $data['name'];

        // Check if project exists
        $existingProject = Project::where('name', $projectName)
            ->orWhere('project_number', $data['project_number'])
            ->first();

        if ($existingProject) {
            $this->command->warn("    ⚠ Skipping duplicate project: {$projectName}");
            return null;
        }

        $this->command->info("  Creating project: {$projectName}");

        $stage = $data['stage'] ?? 'discovery';
        $stageRecord = $this->stages[$stage] ?? $this->stages['discovery'];

        // Calculate dates
        $proposalDate = Carbon::parse($data['proposal_date'] ?? $this->now);
        $startDate = $proposalDate->copy()->addDays(14);
        $endDate = $startDate->copy()->addDays(60);

        $linearFeet = $data['linear_feet'] ?? 20;

        $project = Project::create([
            'name' => $projectName,
            'project_number' => $data['project_number'],
            'project_type' => $data['project_type'] ?? 'commercial',
            'lead_source' => 'proposal',
            'budget_range' => $data['total_amount'] > 20000 ? 'premium' : 'standard',
            'complexity_score' => $data['total_amount'] > 30000 ? 4 : ($data['total_amount'] > 10000 ? 3 : 2),
            'description' => $data['description'],
            'visibility' => 'internal',
            'color' => $stageRecord->color,
            'start_date' => $startDate,
            'desired_completion_date' => $endDate,
            'estimated_linear_feet' => $linearFeet,
            'allow_timesheets' => true,
            'allow_milestones' => true,
            'allow_task_dependencies' => true,
            'is_active' => true,
            'is_converted' => true,
            'converted_at' => $proposalDate,
            'current_production_stage' => $stage,
            'stage_id' => $stageRecord->id,
            'partner_id' => $client->id,
            'company_id' => $this->company->id,
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);

        // Create sales order with real pricing
        $this->createSalesOrder($project, $client, $data);

        // Create basic room structure if has linear feet
        if ($linearFeet > 0) {
            $this->createRoomStructure($project, $stage);
        }

        $this->createdProjects[$projectName] = $project;

        $amount = number_format($data['total_amount'], 2);
        $this->command->info("    ✓ Created project ID: {$project->id} [\${$amount}]");

        return $project;
    }

    /**
     * Create sales order with real pricing
     */
    protected function createSalesOrder(Project $project, Partner $client, array $data): Order
    {
        $totalAmount = $data['total_amount'];
        $depositAmount = $data['deposit_amount'] ?? ($totalAmount * 0.30);

        $stageIndex = $this->getStageIndex($data['stage'] ?? 'discovery');
        $proposalDate = Carbon::parse($data['proposal_date'] ?? $this->now);

        return Order::create([
            'company_id' => $this->company->id,
            'partner_id' => $client->id,
            'partner_invoice_id' => $client->id,
            'partner_shipping_id' => $client->id,
            'currency_id' => DB::table('currencies')->where('name', 'USD')->value('id') ?? 1,
            'project_id' => $project->id,
            'name' => "{$project->project_number}-SO1",
            'state' => $stageIndex >= 1 ? 'sale' : 'draft',
            'date_order' => $proposalDate,
            'amount_untaxed' => $totalAmount,
            'amount_tax' => 0,
            'amount_total' => $totalAmount,
            'locked' => false,
            'require_signature' => true,
            'require_payment' => true,
            'invoice_status' => 'no',
            'woodworking_order_type' => 'full_project',
            'deposit_percentage' => round(($depositAmount / $totalAmount) * 100, 0),
            'deposit_amount' => $depositAmount,
            'balance_percentage' => round((1 - ($depositAmount / $totalAmount)) * 100, 0),
            'balance_amount' => $totalAmount - $depositAmount,
            'proposal_status' => $stageIndex >= 1 ? 'accepted' : 'sent',
            'proposal_sent_at' => $proposalDate,
            'proposal_accepted_at' => $stageIndex >= 1 ? $proposalDate->copy()->addDays(7) : null,
            'production_authorized' => $stageIndex >= 2,
            'deposit_paid_at' => $stageIndex >= 2 ? $proposalDate->copy()->addDays(10) : null,
        ]);
    }

    /**
     * Create basic room structure
     */
    protected function createRoomStructure(Project $project, string $stage): void
    {
        $stageIndex = $this->getStageIndex($stage);

        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Main Area',
            'room_type' => 'commercial',
            'room_code' => 'R1',
            'floor_number' => '1',
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'Primary Location',
            'location_type' => 'wall',
            'sequence' => 1,
            'sort_order' => 1,
            'material_type' => 'paint_grade',
            'creator_id' => $this->admin->id,
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Cabinets',
            'run_type' => 'base',
            'run_code' => 'B1',
            'total_linear_feet' => $project->estimated_linear_feet,
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        // Create one cabinet as placeholder
        Cabinet::create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'cabinet_run_id' => $run->id,
            'cabinet_number' => 1,
            'full_code' => "{$project->project_number}-R1-B1-1",
            'position_in_run' => 1,
            'length_inches' => $project->estimated_linear_feet * 12,
            'width_inches' => 24,
            'depth_inches' => 24,
            'height_inches' => 34.75,
            'linear_feet' => $project->estimated_linear_feet,
            'quantity' => 1,
            'qc_passed' => $stageIndex >= 3,
            'creator_id' => $this->admin->id,
        ]);
    }

    /**
     * Get stage index
     */
    protected function getStageIndex(string $stage): int
    {
        $stages = ['discovery', 'design', 'sourcing', 'production', 'delivery'];
        $index = array_search($stage, $stages);
        return $index !== false ? $index : 0;
    }

    /**
     * Load prerequisites
     */
    protected function loadPrerequisites(): void
    {
        $this->command->info("Loading prerequisites...");

        $this->company = Company::first();
        if (!$this->company) {
            throw new \RuntimeException("No company found");
        }

        $this->admin = User::first();
        if (!$this->admin) {
            throw new \RuntimeException("No user found");
        }

        $this->usCountry = Country::where('code', 'US')->first();
        $this->nyState = State::where('code', 'NY')->first();

        $this->command->info("  ✓ Using company: {$this->company->name}");
        $this->command->info("  ✓ Using admin: {$this->admin->name}");
    }

    /**
     * Ensure project stages exist
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
     * Print summary
     */
    protected function printSummary(): void
    {
        $this->command->info("\n=== Proposal Import Complete ===");

        $this->command->info("\nCreated " . count($this->createdClients) . " clients:");
        foreach ($this->createdClients as $name => $client) {
            $this->command->info("  - {$name}");
        }

        $this->command->info("\nCreated " . count($this->createdProjects) . " projects:");
        foreach ($this->createdProjects as $name => $project) {
            $stage = $project->current_production_stage;
            $this->command->info("  - {$name} [{$stage}]");
        }

        $this->command->info("");
    }
}
