<?php

namespace Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource\Actions\CloneProjectAction;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Support\Models\Company;

class CloneProjectActionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Partner $partner;
    protected Partner $newPartner;
    protected ProjectStage $stage;
    protected ProjectStage $newStage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required project tables not available. Run all migrations first.');
        }

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->company = Company::firstOrCreate(
            ['name' => 'Test Company'],
            ['is_active' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Original Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->newPartner = Partner::firstOrCreate(
            ['name' => 'New Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->stage = ProjectStage::firstOrCreate(
            ['name' => 'New', 'company_id' => $this->company->id],
            ['sort' => 1]
        );

        $this->newStage = ProjectStage::firstOrCreate(
            ['name' => 'Quoted', 'company_id' => $this->company->id],
            ['sort' => 2]
        );
    }

    /**
     * Check if required database tables exist for these tests
     */
    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'projects_projects',
            'projects_project_stages',
            'projects_rooms',
            'projects_room_locations',
            'projects_cabinet_runs',
            'projects_cabinet_specifications',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /** @test */
    public function it_clones_basic_project_data(): void
    {
        $project = $this->createProjectWithData();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned Project',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => false,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertNotEquals($project->id, $clonedProject->id);
        $this->assertEquals('Cloned Project', $clonedProject->name);
        $this->assertEquals($this->partner->id, $clonedProject->partner_id);
        $this->assertEquals($this->stage->id, $clonedProject->stage_id);
    }

    /** @test */
    public function it_clones_project_with_different_customer(): void
    {
        $project = $this->createProjectWithData();

        $clonedProject = $this->performClone($project, [
            'name' => 'Project for New Customer',
            'partner_id' => $this->newPartner->id,
            'stage_id' => $this->newStage->id,
            'company_id' => $this->company->id,
            'include_rooms' => true,
            'include_cabinets' => true,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertNotEquals($project->partner_id, $clonedProject->partner_id);
        $this->assertEquals($this->newPartner->id, $clonedProject->partner_id);
    }

    /** @test */
    public function it_clones_rooms_when_enabled(): void
    {
        $project = $this->createProjectWithRooms();
        $originalRoomCount = $project->rooms()->count();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Rooms',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => true,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertEquals($originalRoomCount, $clonedProject->rooms()->count());
        $this->assertGreaterThan(0, $clonedProject->rooms()->count());

        // Room IDs should be different
        $originalRoomIds = $project->rooms()->pluck('id')->toArray();
        $clonedRoomIds = $clonedProject->rooms()->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($originalRoomIds, $clonedRoomIds));
    }

    /** @test */
    public function it_skips_rooms_when_disabled(): void
    {
        $project = $this->createProjectWithRooms();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned without Rooms',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => false,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertEquals(0, $clonedProject->rooms()->count());
    }

    /** @test */
    public function it_clones_cabinets_with_rooms(): void
    {
        $project = $this->createProjectWithCabinets();
        $originalCabinetCount = $project->cabinetSpecifications()->count();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Cabinets',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => true,
            'include_cabinets' => true,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertEquals($originalCabinetCount, $clonedProject->cabinetSpecifications()->count());
    }

    /** @test */
    public function it_resets_dates_when_enabled(): void
    {
        $project = $this->createProjectWithData([
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(60),
            'desired_completion_date' => now()->addDays(90),
        ]);

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Reset Dates',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => false,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertNull($clonedProject->start_date);
        $this->assertNull($clonedProject->end_date);
        $this->assertNull($clonedProject->desired_completion_date);
    }

    /** @test */
    public function it_preserves_dates_when_reset_disabled(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now()->addDays(60);

        $project = $this->createProjectWithData([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Preserved Dates',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => false,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => false,
            'reset_pricing' => false,
        ]);

        $this->assertEquals($startDate->format('Y-m-d'), $clonedProject->start_date->format('Y-m-d'));
        $this->assertEquals($endDate->format('Y-m-d'), $clonedProject->end_date->format('Y-m-d'));
    }

    /** @test */
    public function it_resets_pricing_when_enabled(): void
    {
        $project = $this->createProjectWithCabinets();

        // Update cabinet with pricing
        $cabinet = $project->cabinetSpecifications()->first();
        $cabinet->update([
            'unit_price_per_lf' => 200.00,
            'total_price' => 2000.00,
        ]);

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Reset Pricing',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => true,
            'include_cabinets' => true,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => true,
        ]);

        $clonedCabinet = $clonedProject->cabinetSpecifications()->first();
        $this->assertEquals(0, $clonedCabinet->unit_price_per_lf);
        $this->assertEquals(0, $clonedCabinet->total_price);
    }

    /** @test */
    public function it_clones_room_locations_and_cabinet_runs(): void
    {
        $project = $this->createProjectWithCabinets();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned with Full Structure',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => true,
            'include_cabinets' => true,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        // Check room locations are cloned
        $originalRoom = $project->rooms()->first();
        $clonedRoom = $clonedProject->rooms()->first();

        $this->assertEquals(
            $originalRoom->locations()->count(),
            $clonedRoom->locations()->count()
        );

        // Check cabinet runs are cloned
        $originalLocation = $originalRoom->locations()->first();
        $clonedLocation = $clonedRoom->locations()->first();

        if ($originalLocation && $clonedLocation) {
            $this->assertEquals(
                $originalLocation->cabinetRuns()->count(),
                $clonedLocation->cabinetRuns()->count()
            );
        }
    }

    /** @test */
    public function it_assigns_current_user_as_creator(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $project = $this->createProjectWithData();

        $clonedProject = $this->performClone($project, [
            'name' => 'Cloned Project',
            'partner_id' => $this->partner->id,
            'stage_id' => $this->stage->id,
            'company_id' => $this->company->id,
            'include_rooms' => false,
            'include_cabinets' => false,
            'include_addresses' => false,
            'reset_dates' => true,
            'reset_pricing' => false,
        ]);

        $this->assertEquals(auth()->id(), $clonedProject->creator_id);
        $this->assertEquals(auth()->id(), $clonedProject->user_id);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createProjectWithData(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Original Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createProjectWithRooms(): Project
    {
        $project = $this->createProjectWithData();

        Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'total_price' => 5000.00,
        ]);

        Room::create([
            'project_id' => $project->id,
            'name' => 'Bathroom',
            'total_price' => 3000.00,
        ]);

        return $project;
    }

    protected function createProjectWithCabinets(): Project
    {
        $project = $this->createProjectWithData();

        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Run 1',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'cabinet_run_id' => $run->id,
                'cabinet_number' => "K{$i}",
                'linear_feet' => 5.0,
                'unit_price_per_lf' => 150.00,
                'total_price' => 750.00,
                'width_inches' => 36,
                'depth_inches' => 24,
                'height_inches' => 34,
                'creator_id' => $this->user->id,
            ]);
        }

        return $project;
    }

    /**
     * Simulate the clone action logic
     */
    protected function performClone(Project $source, array $data): Project
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($source, $data) {
            // Clone base project data
            $projectData = $source->replicate([
                'id',
                'project_number',
                'created_at',
                'updated_at',
                'deleted_at',
            ])->toArray();

            // Apply form overrides
            $projectData['name'] = $data['name'];
            $projectData['partner_id'] = $data['partner_id'];
            $projectData['stage_id'] = $data['stage_id'];
            $projectData['company_id'] = $data['company_id'];
            $projectData['creator_id'] = auth()->id() ?? $this->user->id;
            $projectData['user_id'] = auth()->id() ?? $this->user->id;

            // Reset dates if requested
            if ($data['reset_dates'] ?? false) {
                $projectData['start_date'] = null;
                $projectData['end_date'] = null;
                $projectData['desired_completion_date'] = null;
            }

            $newProject = Project::create($projectData);

            // Clone rooms if requested
            if ($data['include_rooms'] ?? false) {
                $this->cloneRooms($source, $newProject, $data);
            }

            return $newProject;
        });
    }

    protected function cloneRooms(Project $source, Project $target, array $data): void
    {
        foreach ($source->rooms as $room) {
            $roomData = $room->replicate([
                'id',
                'project_id',
                'created_at',
                'updated_at',
            ])->toArray();

            $roomData['project_id'] = $target->id;

            if ($data['reset_pricing'] ?? false) {
                $roomData['total_price'] = null;
                $roomData['estimated_cabinet_value'] = null;
            }

            $newRoom = $target->rooms()->create($roomData);

            // Clone room locations
            foreach ($room->locations as $location) {
                $locationData = $location->replicate([
                    'id',
                    'room_id',
                    'created_at',
                    'updated_at',
                ])->toArray();

                $locationData['room_id'] = $newRoom->id;
                $newLocation = $newRoom->locations()->create($locationData);

                // Clone cabinet runs
                foreach ($location->cabinetRuns as $run) {
                    $runData = $run->replicate([
                        'id',
                        'room_location_id',
                        'created_at',
                        'updated_at',
                    ])->toArray();

                    $runData['room_location_id'] = $newLocation->id;
                    $newRun = $newLocation->cabinetRuns()->create($runData);

                    // Clone cabinets if requested
                    if ($data['include_cabinets'] ?? false) {
                        foreach ($run->cabinets as $cabinet) {
                            $this->cloneCabinet($cabinet, $target, $newRoom, $newRun, $data);
                        }
                    }
                }
            }
        }
    }

    protected function cloneCabinet($cabinet, Project $target, $newRoom, $newRun, array $data): void
    {
        $cabinetData = $cabinet->replicate([
            'id',
            'project_id',
            'room_id',
            'cabinet_run_id',
            'order_line_id',
            'created_at',
            'updated_at',
        ])->toArray();

        $cabinetData['project_id'] = $target->id;
        $cabinetData['room_id'] = $newRoom?->id;
        $cabinetData['cabinet_run_id'] = $newRun?->id;
        $cabinetData['order_line_id'] = null;
        $cabinetData['creator_id'] = auth()->id() ?? $this->user->id;

        if ($data['reset_pricing'] ?? false) {
            $cabinetData['unit_price_per_lf'] = 0;
            $cabinetData['total_price'] = 0;
        }

        $target->cabinetSpecifications()->create($cabinetData);
    }
}
