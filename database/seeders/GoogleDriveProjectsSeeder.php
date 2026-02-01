<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Google Drive Projects Seeder
 *
 * Scans local Google Drive folders and creates projects from folder structure.
 * Imports active and completed Nantucket projects with realistic data.
 *
 * Run with: php artisan db:seed --class=GoogleDriveProjectsSeeder
 */
class GoogleDriveProjectsSeeder extends Seeder
{
    protected Carbon $now;
    protected ?Company $company;
    protected ?User $admin;
    protected array $stages = [];
    protected $milestoneTemplates;
    protected array $createdProjects = [];

    /**
     * Google Drive paths
     */
    protected string $activePath = '/Users/andrewphan/tcsadmin/Google_Drive/Nantucket Projects';
    protected string $completedPath = '/Users/andrewphan/tcsadmin/Google_Drive/Nantucket Projects/COMPETED PROJECTS';

    /**
     * Project name formatting map
     */
    protected array $projectNameMap = [
        '4R Jefferson' => '4R Jefferson Ave',
        '5 Doc Ryder' => '5 Doc Ryder Vanity',
        '7 Moors End' => '7 Moors End Lane',
        '82 Edmunds Wellesley' => '82 Edmunds Lane, Wellesley',
        'LMC' => 'LMC Entertainment Center',
        '18 Sankaty' => '18 Sankaty Head Rd',
        '34 Hummock' => '34 Hummock Pond Rd',
        '19 Gardner' => '19 Gardner Rd',
        '24 Fair' => '24 Fair St',
        '26 Fair' => '26 Fair St',
        '30 Center' => '30 Center St',
        '33 Main Toole Cabana' => '33 Main St - Toole Cabana',
        '34A Grove' => '34A Grove Lane',
        '4 Windsor' => '4 Windsor Rd',
        '5 Canonicus' => '5 Canonicus Ave',
        '5 Maxey' => '5 Maxey Pond Rd',
        '51 Fair St' => '51 Fair St',
        '51 Weweeder' => '51 Weweeder Ave',
        '7 Sherburne' => '7 Sherburne Commons',
        '76 Madaket' => '76 Madaket Rd',
        '9 Hawks Circle' => '9 Hawks Circle',
        'Alyssa' => 'Alyssa Residence',
        'Gay St' => 'Gay St Residence',
        'Golf Club' => 'Nantucket Golf Club',
        'Grey St' => 'Grey St Residence',
        'Kopelman' => 'Kopelman Residence',
        'MVR' => 'MVR Project',
        'Monique' => 'Monique Residence',
        'Pawguvet' => 'Pawguvet Residence',
        'Robin T Ent' => 'Robin T Entertainment Center',
        'Teak Table' => 'Custom Teak Table',
        'Washington St' => 'Washington St Residence',
        'Willard' => 'Willard Residence',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->now = Carbon::now();

        $this->command->info("\n=== Google Drive Projects Seeder ===\n");

        // Check if paths exist
        if (!File::isDirectory($this->activePath)) {
            $this->command->error("Google Drive path not found: {$this->activePath}");
            return;
        }

        DB::beginTransaction();

        try {
            $this->loadPrerequisites();
            $this->ensureProjectStages();
            $this->loadMilestoneTemplates();

            // Scan and import active projects
            $this->command->info("\n--- Active Projects ---\n");
            $activeProjects = $this->scanDirectory($this->activePath, false);
            $this->command->info("Found " . count($activeProjects) . " active project folders.\n");

            foreach ($activeProjects as $folderData) {
                $this->createProjectFromFolder($folderData, false);
            }

            // Scan and import completed projects
            if (File::isDirectory($this->completedPath)) {
                $this->command->info("\n--- Completed Projects ---\n");
                $completedProjects = $this->scanDirectory($this->completedPath, true);
                $this->command->info("Found " . count($completedProjects) . " completed project folders.\n");

                foreach ($completedProjects as $folderData) {
                    $this->createProjectFromFolder($folderData, true);
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
     * Scan directory for project folders
     */
    protected function scanDirectory(string $path, bool $isCompleted): array
    {
        $projects = [];
        $directories = File::directories($path);

        foreach ($directories as $dir) {
            $folderName = basename($dir);

            // Skip special folders
            if (in_array($folderName, ['COMPETED PROJECTS', '.DS_Store', '@eaDir'])) {
                continue;
            }

            // Count PDFs and CNC files
            $pdfCount = count(File::glob($dir . '/*.pdf'));
            $cncCount = count(File::glob($dir . '/**/*.cnc')) + count(File::glob($dir . '/*.cnc'));

            // Check for CNC in subdirectories too
            $subdirs = File::directories($dir);
            foreach ($subdirs as $subdir) {
                $pdfCount += count(File::glob($subdir . '/*.pdf'));
                $cncCount += count(File::glob($subdir . '/*.cnc'));
            }

            $projects[] = [
                'folder_name' => $folderName,
                'path' => $dir,
                'pdf_count' => $pdfCount,
                'cnc_count' => $cncCount,
                'is_completed' => $isCompleted,
            ];
        }

        return $projects;
    }

    /**
     * Create project from folder data
     */
    protected function createProjectFromFolder(array $folderData, bool $isCompleted): ?Project
    {
        $folderName = $folderData['folder_name'];

        // Format project name
        $projectName = $this->formatProjectName($folderName);

        // Check if project already exists
        $existingProject = Project::where('name', $projectName)
            ->orWhere('name', $folderName)
            ->first();

        if ($existingProject) {
            $this->command->warn("  ⚠ Skipping duplicate: {$projectName}");
            return null;
        }

        $this->command->info("Creating: {$folderName} → {$projectName}");

        // Determine stage based on file counts
        $stage = $this->determineStageFromFiles($folderData, $isCompleted);

        // Calculate dates
        $dates = $this->calculateDates($isCompleted, $stage);

        // Estimate linear feet based on PDF count
        $linearFeet = $this->estimateLinearFeet($folderData);

        // Create customer
        $customer = $this->getOrCreateCustomer($projectName);

        // Get stage record
        $stageRecord = $this->stages[$stage] ?? $this->stages['discovery'];

        // Generate project number
        $projectNumber = $this->generateProjectNumber($projectName);

        // Create the project
        $project = Project::create([
            'name' => $projectName,
            'project_number' => $projectNumber,
            'project_type' => $this->determineProjectType($projectName),
            'lead_source' => 'referral',
            'budget_range' => $linearFeet > 50 ? 'premium' : 'standard',
            'complexity_score' => $this->calculateComplexityScore($folderData),
            'description' => $this->generateDescription($folderData, $projectName),
            'google_drive_folder_url' => null, // Could be populated with Drive URL if available
            'google_drive_enabled' => false,
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
        $this->createSalesOrder($project, $customer, $stage, $isCompleted, $linearFeet);

        // Create room hierarchy
        $this->createRoomHierarchy($project, $stage);

        // Create milestones
        if ($dates['start'] && $this->milestoneTemplates->isNotEmpty()) {
            $this->createMilestones($project, $stage, $isCompleted);
        }

        // Create tasks
        $this->createTasks($project, $stage);

        // Set gate fields
        $this->setGateFields($project, $stage, $isCompleted, $dates);

        $this->createdProjects[$projectName] = $project;

        $stageLabel = $isCompleted ? 'complete' : $stage;
        $this->command->info("  ✓ Created project ID: {$project->id} [{$stageLabel}] - {$linearFeet} LF");

        return $project;
    }

    /**
     * Format project name from folder name
     */
    protected function formatProjectName(string $folderName): string
    {
        // Check for known mappings
        if (isset($this->projectNameMap[$folderName])) {
            return $this->projectNameMap[$folderName];
        }

        // Default: title case and clean up
        return ucwords(str_replace(['_', '-'], ' ', $folderName));
    }

    /**
     * Determine stage from file counts
     */
    protected function determineStageFromFiles(array $folderData, bool $isCompleted): string
    {
        if ($isCompleted) {
            return 'delivery';
        }

        $pdfCount = $folderData['pdf_count'];
        $cncCount = $folderData['cnc_count'];

        // More CNC files = further in production
        if ($cncCount >= 10) {
            return 'production';
        } elseif ($cncCount >= 1) {
            return 'sourcing';
        } elseif ($pdfCount >= 3) {
            return 'design';
        }

        return 'discovery';
    }

    /**
     * Calculate project dates
     */
    protected function calculateDates(bool $isCompleted, string $stage): array
    {
        if ($isCompleted) {
            // Completed: random finish within last 6 months
            $end = $this->now->copy()->subDays(rand(30, 180));
            $duration = rand(45, 90);
            $start = $end->copy()->subDays($duration);
        } else {
            // Active: based on stage
            $stageOffsets = [
                'discovery' => [7, 21],      // Starts soon
                'design' => [-14, 7],        // Started recently
                'sourcing' => [-30, -14],    // Started a month ago
                'production' => [-60, -30],  // Started 1-2 months ago
                'delivery' => [-90, -60],    // Started 2-3 months ago
            ];

            $offset = $stageOffsets[$stage] ?? [0, 14];
            $startOffset = rand($offset[0], $offset[1]);
            $start = $this->now->copy()->addDays($startOffset);
            $duration = rand(45, 90);
            $end = $start->copy()->addDays($duration);
        }

        return [
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
        ];
    }

    /**
     * Estimate linear feet from folder data
     */
    protected function estimateLinearFeet(array $folderData): int
    {
        $pdfCount = $folderData['pdf_count'];
        $cncCount = $folderData['cnc_count'];

        // Base estimate on file counts (more files = larger project)
        $base = 25;
        $fromPdfs = $pdfCount * 8;
        $fromCnc = $cncCount * 2;

        $total = $base + $fromPdfs + $fromCnc;

        // Cap at reasonable range
        return min(max($total, 20), 150);
    }

    /**
     * Calculate complexity score
     */
    protected function calculateComplexityScore(array $folderData): int
    {
        $pdfCount = $folderData['pdf_count'];
        $cncCount = $folderData['cnc_count'];

        if ($cncCount >= 20 || $pdfCount >= 15) return 5;
        if ($cncCount >= 10 || $pdfCount >= 10) return 4;
        if ($cncCount >= 5 || $pdfCount >= 5) return 3;
        if ($cncCount >= 1 || $pdfCount >= 2) return 2;
        return 1;
    }

    /**
     * Generate project description
     */
    protected function generateDescription(array $folderData, string $projectName): string
    {
        $desc = "Nantucket custom cabinetry project: {$projectName}\n\n";
        $desc .= "Project imported from Google Drive folder.\n";
        $desc .= "PDFs: {$folderData['pdf_count']} | CNC files: {$folderData['cnc_count']}";

        return $desc;
    }

    /**
     * Determine project type from name
     */
    protected function determineProjectType(string $projectName): string
    {
        $nameLower = strtolower($projectName);

        if (str_contains($nameLower, 'golf club') || str_contains($nameLower, 'club')) {
            return 'commercial';
        }
        if (str_contains($nameLower, 'entertainment') || str_contains($nameLower, 'table')) {
            return 'residential';
        }

        return 'residential';
    }

    /**
     * Generate project number
     */
    protected function generateProjectNumber(string $projectName): string
    {
        $existingCount = Project::where('company_id', $this->company->id)->count();
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', substr($projectName, 0, 8));
        return 'TCS-' . str_pad($existingCount + 700, 4, '0', STR_PAD_LEFT) . '-' . strtoupper($cleanName);
    }

    /**
     * Get or create customer
     */
    protected function getOrCreateCustomer(string $projectName): Partner
    {
        $email = strtolower(Str::slug($projectName)) . '@nantucket.tcswoodwork.com';

        return Partner::firstOrCreate(
            ['email' => $email],
            [
                'name' => $projectName,
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
        $ratePerLf = $linearFeet > 50 ? 475 : 395;
        $totalAmount = $linearFeet * $ratePerLf;
        $depositAmount = $totalAmount * 0.30;

        return Order::create([
            'company_id' => $this->company->id,
            'partner_id' => $customer->id,
            'partner_invoice_id' => $customer->id,
            'partner_shipping_id' => $customer->id,
            'currency_id' => DB::table('currencies')->where('name', 'USD')->value('id') ?? 1,
            'project_id' => $project->id,
            'name' => "{$project->project_number}-SO1",
            'state' => $stageIndex >= 1 ? 'sale' : 'draft',
            'date_order' => $project->start_date ?? $this->now,
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
            'production_authorized' => $stageIndex >= 1,
        ]);
    }

    /**
     * Create room hierarchy (simplified version)
     */
    protected function createRoomHierarchy(Project $project, string $stage): void
    {
        $stageIndex = $this->getStageIndex($stage, false);

        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
            'room_code' => 'R1',
            'floor_number' => '1',
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'Main Wall',
            'location_type' => 'wall',
            'sequence' => 1,
            'sort_order' => 1,
            'material_type' => 'paint_grade',
            'wood_species' => 'hard_maple',
            'door_style' => 'shaker',
            'has_face_frame' => true,
            'soft_close_doors' => true,
            'soft_close_drawers' => true,
            'creator_id' => $this->admin->id,
        ]);

        $linearFeet = $project->estimated_linear_feet;
        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Base Cabinets',
            'run_type' => 'base',
            'run_code' => 'B1',
            'total_linear_feet' => $linearFeet,
            'sort_order' => 1,
            'creator_id' => $this->admin->id,
        ]);

        // Create a few cabinets
        $cabinetCount = max(2, min(6, intval($linearFeet / 10)));
        for ($i = 1; $i <= $cabinetCount; $i++) {
            $widthInches = rand(24, 36);
            $qcPassed = $stageIndex >= 3;

            $cabinet = Cabinet::create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'cabinet_run_id' => $run->id,
                'cabinet_number' => $i,
                'full_code' => "{$project->project_number}-R1-B1-{$i}",
                'position_in_run' => $i,
                'length_inches' => $widthInches,
                'width_inches' => 24,
                'depth_inches' => 24,
                'height_inches' => 34.75,
                'linear_feet' => $widthInches / 12,
                'quantity' => 1,
                'qc_passed' => $qcPassed,
                'creator_id' => $this->admin->id,
            ]);

            // Create door
            Door::create([
                'cabinet_id' => $cabinet->id,
                'door_number' => 1,
                'door_name' => 'Door 1',
                'full_code' => "{$cabinet->full_code}-DOOR1",
                'sort_order' => 1,
                'width_inches' => $widthInches - 0.25,
                'height_inches' => 24,
                'thickness_inches' => 0.75,
                'hinge_type' => 'blum_clip_top',
                'hinge_quantity' => 2,
                'hinge_side' => 'left',
                'finish_type' => 'paint_grade',
                'qc_passed' => $stageIndex >= 3,
            ]);

            // Create drawer
            Drawer::create([
                'cabinet_id' => $cabinet->id,
                'drawer_number' => 1,
                'drawer_name' => 'Drawer 1',
                'full_code' => "{$cabinet->full_code}-DRW1",
                'sort_order' => 1,
                'drawer_position' => 'upper',
                'front_width_inches' => $widthInches - 0.5,
                'front_height_inches' => 6,
                'box_width_inches' => $widthInches - 2,
                'box_height_inches' => 4.5,
                'box_depth_inches' => 18,
                'slide_length_inches' => 18,
                'opening_width_inches' => $widthInches - 0.5,
                'opening_height_inches' => 6.5,
                'qc_passed' => $stageIndex >= 3,
            ]);
        }
    }

    /**
     * Create milestones
     */
    protected function createMilestones(Project $project, string $stage, bool $isCompleted): void
    {
        $stageIndex = $this->getStageIndex($stage, $isCompleted);
        $stageOrder = ['discovery', 'design', 'sourcing', 'production', 'delivery'];

        foreach ($this->milestoneTemplates as $template) {
            $deadline = $project->start_date->copy()->addDays($template->relative_days);
            $templateStageIndex = array_search($template->production_stage, $stageOrder);

            $milestoneCompleted = $templateStageIndex !== false && $templateStageIndex < $stageIndex;

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
     * Create tasks
     */
    protected function createTasks(Project $project, string $stage): void
    {
        $taskStages = [];
        foreach (['Backlog', 'To Do', 'In Progress', 'Done'] as $index => $name) {
            $taskStages[$name] = TaskStage::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                [
                    'is_active' => true,
                    'is_collapsed' => $name === 'Backlog' || $name === 'Done',
                    'sort' => $index + 1,
                    'creator_id' => $this->admin->id,
                ]
            );
        }

        $stageIndex = $this->getStageIndex($stage, false);

        $tasks = [
            ['title' => 'Initial measurements', 'phase' => 0],
            ['title' => 'Design development', 'phase' => 1],
            ['title' => 'Material ordering', 'phase' => 2],
            ['title' => 'Cabinet fabrication', 'phase' => 3],
            ['title' => 'Finishing', 'phase' => 3],
            ['title' => 'Delivery coordination', 'phase' => 4],
        ];

        foreach ($tasks as $taskDef) {
            $taskStageName = $taskDef['phase'] < $stageIndex ? 'Done' :
                            ($taskDef['phase'] == $stageIndex ? 'In Progress' : 'Backlog');

            Task::create([
                'project_id' => $project->id,
                'stage_id' => $taskStages[$taskStageName]->id,
                'title' => $taskDef['title'],
                'state' => $taskStageName === 'Done' ? 'done' : ($taskStageName === 'In Progress' ? 'in_progress' : 'pending'),
                'is_active' => true,
                'creator_id' => $this->admin->id,
            ]);
        }
    }

    /**
     * Set gate fields
     */
    protected function setGateFields(Project $project, string $stage, bool $isCompleted, array $dates): void
    {
        $stageIndex = $this->getStageIndex($stage, $isCompleted);
        $updates = [];

        if ($stageIndex >= 2) {
            $updates['design_approved_at'] = $project->start_date?->copy()->addDays(14);
        }
        if ($stageIndex >= 3) {
            $updates['materials_staged_at'] = $project->start_date?->copy()->addDays(28);
        }
        if ($stageIndex >= 4) {
            $updates['production_locked_at'] = $project->start_date?->copy()->addDays(56);
        }
        if ($isCompleted) {
            $updates['delivered_at'] = $project->desired_completion_date;
            $updates['customer_signoff_at'] = $project->desired_completion_date?->copy()->addDays(5);
        }

        if (!empty($updates)) {
            $project->update($updates);
        }
    }

    /**
     * Get stage index
     */
    protected function getStageIndex(string $stage, bool $isComplete): int
    {
        if ($isComplete) return 5;
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

        $this->command->info("  ✓ " . $this->milestoneTemplates->count() . " milestone templates loaded");
    }

    /**
     * Print summary
     */
    protected function printSummary(): void
    {
        $this->command->info("\n=== Google Drive Import Complete ===");
        $this->command->info("Imported " . count($this->createdProjects) . " projects from Google Drive:\n");

        foreach ($this->createdProjects as $name => $project) {
            $stage = $project->current_production_stage;
            $lf = $project->estimated_linear_feet;
            $this->command->info("  - {$name} [{$stage}] {$lf} LF");
        }

        $this->command->info("");
    }
}
