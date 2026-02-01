<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\HardwareRequirement;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Product\Models\Product;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Notion Projects Import Seeder
 *
 * Imports real project data from Notion CSV export and adapts it to
 * the TCS ERP database structure with full hierarchy and realistic data.
 *
 * Run with: php artisan db:seed --class=NotionProjectsImportSeeder
 */
class NotionProjectsImportSeeder extends Seeder
{
    protected Carbon $now;
    protected ?Company $company;
    protected ?User $admin;
    protected array $stages = [];
    protected $milestoneTemplates;
    protected array $hardwareProducts = [];
    protected array $createdProjects = [];

    /**
     * CSV file path (relative to project root)
     */
    protected string $csvPath = 'notion_import/extracted/content/Private & Shared/Active Projects 2c4a8c394fe48091b778e632e3967e2c_all.csv';

    /**
     * Project type configurations based on project name patterns
     */
    protected array $projectTypeConfig = [
        // Residential projects (default)
        'default' => [
            'project_type' => 'residential',
            'room_type' => 'kitchen',
            'linear_feet_range' => [30, 55],
            'locations' => ['Sink Wall', 'Range Wall', 'Island'],
        ],
        // Commercial projects (detected by keywords)
        'commercial' => [
            'project_type' => 'commercial',
            'room_type' => 'commercial',
            'linear_feet_range' => [60, 120],
            'locations' => ['Main Area', 'Reception', 'Back Office'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->now = Carbon::now();

        $this->command->info("\n=== Notion Projects Import Seeder ===\n");

        // Check if CSV file exists
        $fullPath = base_path($this->csvPath);
        if (!file_exists($fullPath)) {
            $this->command->error("CSV file not found: {$fullPath}");
            $this->command->info("Please extract the Notion export first.");
            return;
        }

        DB::beginTransaction();

        try {
            $this->loadPrerequisites();
            $this->ensureProjectStages();
            $this->loadMilestoneTemplates();
            $this->loadHardwareProducts();

            // Parse CSV and create projects
            $projects = $this->parseNotionCSV($fullPath);

            $this->command->info("Found " . count($projects) . " projects in Notion export.\n");

            foreach ($projects as $index => $notionProject) {
                $project = $this->createProjectFromNotion($notionProject, $index);
                if ($project) {
                    $this->createdProjects[$notionProject['name']] = $project;
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
     * Parse the Notion CSV export
     */
    protected function parseNotionCSV(string $filePath): array
    {
        $projects = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \RuntimeException("Could not open CSV file: {$filePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);
        // Remove BOM if present
        $headers[0] = preg_replace('/^\x{FEFF}/u', '', $headers[0]);

        // Normalize headers
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue; // Skip empty rows

            $project = [];
            foreach ($headers as $index => $header) {
                $project[$header] = $row[$index] ?? null;
            }
            $projects[] = $project;
        }

        fclose($handle);

        return $projects;
    }

    /**
     * Create a project from Notion data
     */
    protected function createProjectFromNotion(array $notionData, int $index): ?Project
    {
        $rawName = trim($notionData['name'] ?? '');
        if (empty($rawName)) {
            return null;
        }

        // Format the project name (convert addresses, handle special cases)
        $projectName = $this->formatProjectName($rawName);

        // Check if project already exists (check both raw and formatted names)
        $existingProject = Project::where('name', $projectName)
            ->orWhere('name', $rawName)
            ->first();
        if ($existingProject) {
            $this->command->warn("  ⚠ Skipping duplicate: {$projectName}");
            return null;
        }

        $this->command->info("Creating: {$rawName} → {$projectName}");

        // Parse delivery date
        $deliveryDate = $this->parseDate($notionData['delivery date'] ?? null);

        // Determine if completed
        $isCompleted = strtolower($notionData['completed'] ?? 'no') === 'yes';

        // Determine urgency
        $isUrgent = strtolower($notionData['urgency'] ?? '') === 'urgent';

        // Determine project type (commercial vs residential) - use raw name for detection
        $projectConfig = $this->determineProjectConfig($rawName);

        // Calculate dates relative to today and delivery date
        $dates = $this->calculateProjectDates($deliveryDate, $isCompleted, $projectConfig);

        // Determine production stage based on dates and completion
        $stage = $this->determineProductionStage($dates, $isCompleted);

        // Extract project number if present (e.g., "[0569]") - use raw name
        $projectNumber = $this->extractProjectNumber($rawName);

        // Create customer - use formatted name
        $customer = $this->getOrCreateCustomer($projectName);

        // Get stage record
        $stageRecord = $this->stages[$stage] ?? $this->stages['discovery'];

        // Calculate linear feet
        $linearFeet = rand($projectConfig['linear_feet_range'][0], $projectConfig['linear_feet_range'][1]);

        // Create the project
        $project = Project::create([
            'name' => $projectName,
            'project_number' => $projectNumber,
            'project_type' => $projectConfig['project_type'],
            'lead_source' => 'referral',
            'budget_range' => $linearFeet > 50 ? 'premium' : 'standard',
            'complexity_score' => $linearFeet > 60 ? 4 : ($linearFeet > 40 ? 3 : 2),
            'description' => "Project imported from Notion: {$projectName}",
            'google_drive_folder_url' => $notionData['build pdf folder'] ?? null,
            'google_drive_enabled' => !empty($notionData['build pdf folder']),
            'visibility' => 'internal',
            'color' => $stageRecord->color,
            'start_date' => $dates['start'],
            'desired_completion_date' => $dates['end'],
            'estimated_linear_feet' => $linearFeet,
            'allow_timesheets' => true,
            'allow_milestones' => true,
            'allow_task_dependencies' => true,
            'is_active' => !$isCompleted,
            'is_converted' => true,
            'converted_at' => $dates['start']?->copy()->subDays(7),
            'current_production_stage' => $stage,
            'stage_id' => $stageRecord->id,
            'partner_id' => $customer->id,
            'company_id' => $this->company->id,
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);

        // Create sales order
        $salesOrder = $this->createSalesOrder($project, $customer, $stage, $isCompleted, $linearFeet);

        // Create room hierarchy with cabinets
        $this->createRoomHierarchy($project, $projectConfig, $stage);

        // Create milestones
        if ($dates['start']) {
            $this->createMilestones($project, $stage, $isCompleted);
        }

        // Create tasks
        $this->createTaskStagesAndTasks($project, $stage);

        // Set gate fields based on stage
        $this->setGateFields($project, $salesOrder, $stage, $isCompleted, $dates);

        // Mark urgent projects - add "URGENT" to description
        if ($isUrgent) {
            $project->update(['description' => "⚠️ URGENT\n\n" . $project->description]);
        }

        $this->command->info("  ✓ Created project ID: {$project->id} [{$stage}]");

        return $project;
    }

    /**
     * Format project name from Notion (handles addresses and special cases)
     */
    protected function formatProjectName(string $rawName): string
    {
        $name = trim($rawName);

        // Known Nantucket address suffixes to append
        $addressSuffixes = [
            '34 Hummock' => '34 Hummock Pond Rd',
            '67 Surfside' => '67 Surfside Rd',
            '5 FIELDS' => '5 Fields Way',
            'CODFISH PARK' => 'Codfish Park',
            'SANKATY' => 'Sankaty Head',
        ];

        // Check for known addresses
        foreach ($addressSuffixes as $pattern => $formatted) {
            if (strcasecmp($name, $pattern) === 0) {
                return $formatted;
            }
        }

        // Format [####] prefix projects - extract the address part
        if (preg_match('/^\[(\d+)\]\s*(.+)$/', $name, $matches)) {
            return trim($matches[2]); // Return just the address part
        }

        // Title case for all-caps names
        if (strtoupper($name) === $name && strlen($name) > 3) {
            return ucwords(strtolower($name));
        }

        return $name;
    }

    /**
     * Parse a date string from Notion
     */
    protected function parseDate(?string $dateStr): ?Carbon
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine project configuration based on name
     */
    protected function determineProjectConfig(string $projectName): array
    {
        $nameLower = strtolower($projectName);

        // Check for commercial indicators
        $commercialKeywords = ['ent center', 'office', 'commercial', 'retail', 'restaurant', 'hotel'];
        foreach ($commercialKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return $this->projectTypeConfig['commercial'];
            }
        }

        return $this->projectTypeConfig['default'];
    }

    /**
     * Calculate project dates based on delivery date
     */
    protected function calculateProjectDates(?Carbon $deliveryDate, bool $isCompleted, array $config): array
    {
        // If no delivery date, estimate based on current state
        if (!$deliveryDate) {
            if ($isCompleted) {
                // Completed project without date - assume finished recently
                $end = $this->now->copy()->subWeeks(rand(2, 6));
                $duration = rand(45, 75);
                $start = $end->copy()->subDays($duration);
            } else {
                // Active project without date - starts soon
                $start = $this->now->copy()->addDays(rand(7, 21));
                $duration = rand(45, 75);
                $end = $start->copy()->addDays($duration);
            }
        } else {
            // Use delivery date as end date
            $end = $deliveryDate->copy();

            // Calculate appropriate duration based on project size
            $duration = rand(45, 75);

            // Calculate start date
            $start = $end->copy()->subDays($duration);
        }

        return [
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
        ];
    }

    /**
     * Determine production stage based on dates and completion
     */
    protected function determineProductionStage(array $dates, bool $isCompleted): string
    {
        if ($isCompleted) {
            return 'delivery'; // Complete with all gates
        }

        if (!$dates['start'] || !$dates['end']) {
            return 'discovery';
        }

        $today = $this->now;
        $start = $dates['start'];
        $end = $dates['end'];
        $duration = $dates['duration'];

        // If hasn't started yet
        if ($start > $today) {
            return 'discovery';
        }

        // Calculate progress through project timeline
        $daysElapsed = $start->diffInDays($today);
        $progressPercent = ($daysElapsed / $duration) * 100;

        // Map progress to stage
        // Discovery: 0-10%, Design: 10-25%, Sourcing: 25-40%, Production: 40-85%, Delivery: 85-100%
        if ($progressPercent < 10) {
            return 'discovery';
        } elseif ($progressPercent < 25) {
            return 'design';
        } elseif ($progressPercent < 40) {
            return 'sourcing';
        } elseif ($progressPercent < 85) {
            return 'production';
        } else {
            return 'delivery';
        }
    }

    /**
     * Extract project number from name (e.g., "[0569]" from "[0569] 9 Austin Farm")
     */
    protected function extractProjectNumber(string $name): string
    {
        if (preg_match('/\[(\d+)\]/', $name, $matches)) {
            return 'TCS-' . $matches[1];
        }

        // Generate a project number
        $existingCount = Project::where('company_id', $this->company->id)->count();
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', substr($name, 0, 10));
        return 'TCS-' . str_pad($existingCount + 600, 4, '0', STR_PAD_LEFT) . '-' . strtoupper($cleanName);
    }

    /**
     * Get or create a customer from project name
     */
    protected function getOrCreateCustomer(string $projectName): Partner
    {
        // Clean up project name for customer
        $customerName = preg_replace('/^\[\d+\]\s*/', '', $projectName); // Remove [####] prefix
        $customerName = trim($customerName);

        // Use project name as customer identifier
        $email = strtolower(Str::slug($customerName)) . '@project.tcswoodwork.com';

        return Partner::firstOrCreate(
            ['email' => $email],
            [
                'name' => $customerName,
                'account_type' => 'individual',
                'sub_type' => 'customer',
                'is_active' => true,
                'customer_rank' => 1,
            ]
        );
    }

    /**
     * Create sales order
     */
    protected function createSalesOrder(Project $project, Partner $customer, string $stage, bool $isCompleted, int $linearFeet): Order
    {
        $stageIndex = $this->getStageIndex($stage, $isCompleted);
        $ratePerLf = $linearFeet > 50 ? 450 : 380;
        $totalAmount = $linearFeet * $ratePerLf;
        $depositAmount = $totalAmount * 0.30;

        $proposalAcceptedAt = $project->start_date?->copy()->subDays(7);
        $depositPaidAt = $stageIndex >= 1 ? $project->start_date?->copy()->subDays(3) : null;
        $finalPaidAt = $isCompleted ? $project->desired_completion_date?->copy()->addDays(5) : null;

        return Order::create([
            'company_id' => $this->company->id,
            'partner_id' => $customer->id,
            'partner_invoice_id' => $customer->id,
            'partner_shipping_id' => $customer->id,
            'currency_id' => DB::table('currencies')->where('name', 'USD')->value('id') ?? 1,
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
            'invoice_status' => $isCompleted ? 'invoiced' : 'no',
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
     * Create room hierarchy
     */
    protected function createRoomHierarchy(Project $project, array $config, string $stage): void
    {
        $stageIndex = $this->getStageIndex($stage, false);

        $room = Room::create([
            'project_id' => $project->id,
            'name' => ucfirst($config['room_type']),
            'room_type' => $config['room_type'],
            'room_code' => 'R1',
            'floor_number' => '1',
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        $linearFeet = $project->estimated_linear_feet;
        $lfPerLocation = $linearFeet / count($config['locations']);

        foreach ($config['locations'] as $locIndex => $locationName) {
            $location = RoomLocation::create([
                'room_id' => $room->id,
                'name' => $locationName,
                'location_type' => str_contains(strtolower($locationName), 'island') ? 'island' : 'wall',
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

            // Create base cabinet run
            $run = CabinetRun::create([
                'room_location_id' => $location->id,
                'name' => 'Base Cabinets',
                'run_type' => 'base',
                'run_code' => 'B1',
                'total_linear_feet' => $lfPerLocation,
                'sort_order' => 1,
                'creator_id' => $this->admin->id,
            ]);

            // Create cabinets
            $cabinetsPerRun = rand(2, 4);
            for ($cabNum = 1; $cabNum <= $cabinetsPerRun; $cabNum++) {
                $widthInches = $this->snapToCommonWidth(($lfPerLocation / $cabinetsPerRun) * 12);
                $qcPassed = $stageIndex >= 3;

                if ($stage === 'production') {
                    $qcPassed = rand(0, 10) > 3;
                }

                $cabinet = Cabinet::create([
                    'project_id' => $project->id,
                    'room_id' => $room->id,
                    'cabinet_run_id' => $run->id,
                    'cabinet_number' => $cabNum,
                    'full_code' => "{$project->project_number}-{$room->room_code}-{$run->run_code}-{$cabNum}",
                    'position_in_run' => $cabNum,
                    'length_inches' => $widthInches,
                    'width_inches' => 24,
                    'depth_inches' => 24,
                    'height_inches' => 34.75,
                    'linear_feet' => $widthInches / 12,
                    'quantity' => 1,
                    'qc_passed' => $qcPassed,
                    'creator_id' => $this->admin->id,
                ]);

                // Create doors and drawers
                $this->createDoorsAndDrawers($cabinet, $stageIndex);
            }
        }
    }

    /**
     * Create doors and drawers for a cabinet
     */
    protected function createDoorsAndDrawers(Cabinet $cabinet, int $stageIndex): void
    {
        $cabinetWidth = $cabinet->length_inches;
        $doorCount = $cabinetWidth >= 30 ? 2 : 1;
        $drawerCount = $cabinetWidth >= 30 ? 2 : 1;

        // Create doors
        for ($doorNum = 1; $doorNum <= $doorCount; $doorNum++) {
            $doorWidth = $doorCount > 1 ? ($cabinetWidth / 2) - 0.125 : $cabinetWidth - 0.25;
            $hardwareInstalled = $stageIndex >= 3;

            $door = Door::create([
                'cabinet_id' => $cabinet->id,
                'door_number' => $doorNum,
                'door_name' => "Door {$doorNum}",
                'full_code' => "{$cabinet->full_code}-DOOR{$doorNum}",
                'sort_order' => $doorNum,
                'width_inches' => round($doorWidth, 4),
                'height_inches' => 24,
                'thickness_inches' => 0.75,
                'hinge_type' => 'blum_clip_top',
                'hinge_quantity' => 2,
                'hinge_side' => $doorNum % 2 === 1 ? 'left' : 'right',
                'finish_type' => 'paint_grade',
                'qc_passed' => $stageIndex >= 3,
                'hardware_installed_at' => $hardwareInstalled ? $cabinet->created_at : null,
            ]);

            $this->createHingeRequirements($door, $cabinet, $stageIndex);
        }

        // Create drawers
        for ($drawerNum = 1; $drawerNum <= $drawerCount; $drawerNum++) {
            $drawerWidth = $cabinetWidth - 0.5;
            $hardwareInstalled = $stageIndex >= 3;

            $drawer = Drawer::create([
                'cabinet_id' => $cabinet->id,
                'drawer_number' => $drawerNum,
                'drawer_name' => "Drawer {$drawerNum}",
                'full_code' => "{$cabinet->full_code}-DRW{$drawerNum}",
                'sort_order' => $drawerNum,
                'drawer_position' => $drawerNum === 1 ? 'upper' : 'lower',
                'front_width_inches' => round($drawerWidth, 4),
                'front_height_inches' => 6,
                'box_width_inches' => round($drawerWidth - 1.5, 4),
                'box_height_inches' => 4.5,
                'box_depth_inches' => 18,
                'slide_length_inches' => 18,
                'opening_width_inches' => round($drawerWidth, 4),
                'opening_height_inches' => 6.5,
                'qc_passed' => $stageIndex >= 3,
                'slides_installed_at' => $hardwareInstalled ? $cabinet->created_at : null,
            ]);

            $this->createSlideRequirements($drawer, $cabinet, $stageIndex);
        }
    }

    /**
     * Create hinge hardware requirements
     */
    protected function createHingeRequirements(Door $door, Cabinet $cabinet, int $stageIndex): void
    {
        if ($this->hardwareProducts['hinges']->isEmpty()) return;

        $hingeProduct = $this->hardwareProducts['hinges']->random();
        $hingeQty = $door->hinge_quantity ?? 2;
        $allocated = $stageIndex >= 2;
        $kitted = $stageIndex >= 3;
        $installed = $stageIndex >= 3 && rand(0, 10) > 3;

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
    }

    /**
     * Create slide hardware requirements
     */
    protected function createSlideRequirements(Drawer $drawer, Cabinet $cabinet, int $stageIndex): void
    {
        if ($this->hardwareProducts['slides']->isEmpty()) return;

        $slideProduct = $this->hardwareProducts['slides']->random();
        $allocated = $stageIndex >= 2;
        $kitted = $stageIndex >= 3;
        $installed = $stageIndex >= 3 && rand(0, 10) > 3;

        HardwareRequirement::create([
            'cabinet_id' => $cabinet->id,
            'cabinet_run_id' => $cabinet->cabinet_run_id,
            'product_id' => $slideProduct->id,
            'hardware_type' => 'slide',
            'manufacturer' => 'Blum',
            'model_number' => 'TANDEM plus BLUMOTION',
            'quantity_required' => 1,
            'unit_of_measure' => 'PR',
            'applied_to' => 'drawer',
            'drawer_number' => $drawer->drawer_number,
            'slide_type' => 'undermount',
            'slide_length_inches' => 18,
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
     * Create milestones from templates
     */
    protected function createMilestones(Project $project, string $stage, bool $isCompleted): void
    {
        $stageIndex = $this->getStageIndex($stage, $isCompleted);
        $stageOrder = ['discovery', 'design', 'sourcing', 'production', 'delivery'];

        foreach ($this->milestoneTemplates as $template) {
            $deadline = $project->start_date->copy()->addDays($template->relative_days);
            $templateStageIndex = array_search($template->production_stage, $stageOrder);

            $milestoneCompleted = $templateStageIndex !== false && $templateStageIndex < $stageIndex;

            if ($templateStageIndex === $stageIndex) {
                $milestoneCompleted = rand(0, 10) < 6;
            }

            if ($isCompleted) {
                $milestoneCompleted = true;
            }

            Milestone::create([
                'project_id' => $project->id,
                'name' => $template->name,
                'production_stage' => $template->production_stage,
                'deadline' => $deadline,
                'is_critical' => $template->is_critical,
                'description' => $template->description,
                'sort_order' => $template->sort_order,
                'is_completed' => $milestoneCompleted,
                'completed_at' => $milestoneCompleted ? $deadline->copy()->subDays(rand(0, 3)) : null,
                'creator_id' => $this->admin->id,
            ]);
        }
    }

    /**
     * Create task stages and tasks
     */
    protected function createTaskStagesAndTasks(Project $project, string $stage): void
    {
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
                ['project_id' => $project->id, 'name' => $stageData['name']],
                [
                    'is_active' => true,
                    'is_collapsed' => $stageData['is_collapsed'],
                    'sort' => $stageData['sort'],
                    'creator_id' => $this->admin->id,
                ]
            );
        }

        $stageIndex = $this->getStageIndex($stage, false);
        $taskDefinitions = $this->getTaskDefinitions();

        foreach ($taskDefinitions as $taskDef) {
            $taskStageKey = $this->getTaskStageForProductionStage($taskDef['production_phase'], $stageIndex);
            $taskStage = $taskStages[$taskStageKey] ?? $taskStages['Backlog'];

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
     * Get task definitions
     */
    protected function getTaskDefinitions(): array
    {
        return [
            ['title' => 'Initial Consultation', 'production_phase' => 'discovery', 'hours' => 2, 'priority' => true],
            ['title' => 'Site Measurements', 'production_phase' => 'discovery', 'hours' => 4],
            ['title' => 'Define Project Scope', 'production_phase' => 'discovery', 'hours' => 2],
            ['title' => 'Concept Design Development', 'production_phase' => 'design', 'hours' => 8, 'priority' => true],
            ['title' => 'Client Design Review', 'production_phase' => 'design', 'hours' => 2],
            ['title' => 'Final Design & Redlines', 'production_phase' => 'design', 'hours' => 6],
            ['title' => 'Generate Bill of Materials', 'production_phase' => 'design', 'hours' => 4],
            ['title' => 'Order Materials', 'production_phase' => 'sourcing', 'hours' => 4, 'priority' => true],
            ['title' => 'Order Hardware', 'production_phase' => 'sourcing', 'hours' => 2],
            ['title' => 'Receive & Stage Materials', 'production_phase' => 'sourcing', 'hours' => 4],
            ['title' => 'CNC Cutting', 'production_phase' => 'production', 'hours' => 8, 'priority' => true],
            ['title' => 'Face Frame Assembly', 'production_phase' => 'production', 'hours' => 6],
            ['title' => 'Cabinet Box Assembly', 'production_phase' => 'production', 'hours' => 12],
            ['title' => 'Door & Drawer Production', 'production_phase' => 'production', 'hours' => 8],
            ['title' => 'Finishing & Touch-up', 'production_phase' => 'production', 'hours' => 10],
            ['title' => 'Hardware Installation', 'production_phase' => 'production', 'hours' => 4],
            ['title' => 'QC Inspection', 'production_phase' => 'production', 'hours' => 2, 'priority' => true],
            ['title' => 'Pack & Load for Delivery', 'production_phase' => 'delivery', 'hours' => 4],
            ['title' => 'Deliver to Site', 'production_phase' => 'delivery', 'hours' => 4, 'priority' => true],
            ['title' => 'Installation Support', 'production_phase' => 'delivery', 'hours' => 8],
            ['title' => 'Final Walkthrough', 'production_phase' => 'delivery', 'hours' => 2],
            ['title' => 'Project Closeout', 'production_phase' => 'delivery', 'hours' => 2],
        ];
    }

    /**
     * Map task phase to task stage
     */
    protected function getTaskStageForProductionStage(string $taskPhase, int $currentStageIndex): string
    {
        $phaseIndices = ['discovery' => 0, 'design' => 1, 'sourcing' => 2, 'production' => 3, 'delivery' => 4];
        $taskPhaseIndex = $phaseIndices[$taskPhase] ?? 0;

        if ($taskPhaseIndex < $currentStageIndex) {
            return 'Done';
        } elseif ($taskPhaseIndex == $currentStageIndex) {
            $rand = rand(1, 10);
            if ($rand <= 3) return 'Done';
            if ($rand <= 5) return 'In Progress';
            if ($rand <= 7) return 'Review';
            return 'To Do';
        }
        return 'Backlog';
    }

    /**
     * Set gate fields based on stage
     */
    protected function setGateFields(Project $project, Order $salesOrder, string $stage, bool $isCompleted, array $dates): void
    {
        $stageIndex = $this->getStageIndex($stage, $isCompleted);
        $updates = [];

        if ($stageIndex >= 2) {
            $designDate = $project->start_date?->copy()->addDays(14);
            $updates['design_approved_at'] = $designDate;
            $updates['redline_approved_at'] = $designDate?->copy()->addDays(3);
            $updates['design_locked_at'] = $designDate?->copy()->addDays(5);
        }

        if ($stageIndex >= 3) {
            $sourcingDate = $project->start_date?->copy()->addDays(28);
            $updates['materials_staged_at'] = $sourcingDate;
            $updates['all_materials_received_at'] = $sourcingDate?->copy()->subDays(5);
            $updates['procurement_locked_at'] = $sourcingDate?->copy()->addDays(2);
        }

        if ($stageIndex >= 4) {
            $productionDate = $project->start_date?->copy()->addDays(56);
            $updates['production_locked_at'] = $productionDate;
            $updates['bol_created_at'] = $productionDate?->copy()->addDays(3);
        }

        if ($isCompleted) {
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
     * Get stage index (0-5)
     */
    protected function getStageIndex(string $stage, bool $isComplete): int
    {
        if ($isComplete) return 5;
        $stages = ['discovery', 'design', 'sourcing', 'production', 'delivery'];
        $index = array_search($stage, $stages);
        return $index !== false ? $index : 0;
    }

    /**
     * Snap to common cabinet width
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
            $this->command->warn("  ! No milestone templates found.");
        }

        $this->command->info("  ✓ " . $this->milestoneTemplates->count() . " milestone templates loaded");
    }

    /**
     * Load hardware products
     */
    protected function loadHardwareProducts(): void
    {
        $this->command->info("Loading hardware products...");

        $hinges = Product::where('name', 'like', '%Blum%')
            ->where(function ($q) {
                $q->where('name', 'like', '%hinge%')
                  ->orWhere('name', 'like', '%Hinge%')
                  ->orWhere('name', 'like', '%inserta%');
            })->get();

        $slides = Product::where('name', 'like', '%Blum%')
            ->where(function ($q) {
                $q->where('name', 'like', '%slide%')
                  ->orWhere('name', 'like', '%runner%');
            })->get();

        $this->hardwareProducts = [
            'hinges' => $hinges->isNotEmpty() ? $hinges : collect(),
            'slides' => $slides->isNotEmpty() ? $slides : collect(),
        ];

        $this->command->info("  ✓ " . $hinges->count() . " hinge products, " . $slides->count() . " slide products");
    }

    /**
     * Print summary
     */
    protected function printSummary(): void
    {
        $this->command->info("\n=== Import Complete ===");
        $this->command->info("Reference Date: " . $this->now->format('Y-m-d') . " (TODAY)");
        $this->command->info("");
        $this->command->info("Imported " . count($this->createdProjects) . " projects from Notion:");
        $this->command->info("");

        foreach ($this->createdProjects as $name => $project) {
            $start = $project->start_date ? $project->start_date->format('Y-m-d') : 'N/A';
            $end = $project->desired_completion_date ? $project->desired_completion_date->format('Y-m-d') : 'N/A';
            $this->command->info("  - {$name}");
            $this->command->info("    Stage: {$project->current_production_stage} | Dates: {$start} → {$end}");
        }

        $this->command->info("");
        $this->command->info("Each project includes:");
        $this->command->info("  - Sales order with payment tracking");
        $this->command->info("  - Room → Location → Cabinet Run → Cabinet hierarchy");
        $this->command->info("  - Doors & drawers with Blum hardware");
        $this->command->info("  - Milestones and tasks based on stage");
        $this->command->info("  - Gate fields populated appropriately\n");
    }
}
