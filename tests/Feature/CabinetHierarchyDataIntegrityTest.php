<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CabinetHierarchyDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating complete hierarchy with all relationships
     *
     * @test
     */
    public function it_can_create_complete_cabinet_hierarchy()
    {
        // Create a minimal project hierarchy
        $projectId = DB::table('projects_projects')->insertGetId([
            'name' => 'Test Kitchen Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomId = DB::table('projects_rooms')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Kitchen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $locationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $roomId,
            'name' => 'Island',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $locationId,
            'run_name' => 'Island Base Run',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cabinetId = DB::table('projects_cabinet_specifications')->insertGetId([
            'cabinet_run_id' => $runId,
            'cabinet_name' => 'B36',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sectionId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_number' => 1,
            'name' => 'Door Opening',
            'section_type' => 'door_opening',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assert all records were created
        $this->assertDatabaseHas('projects_projects', ['id' => $projectId]);
        $this->assertDatabaseHas('projects_cabinet_sections', ['id' => $sectionId]);
    }

    /**
     * Test creating door component with all relationships
     *
     * @test
     */
    public function it_can_create_door_with_relationships()
    {
        // Setup hierarchy
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $sectionId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_number' => 1,
            'name' => 'Door Opening',
            'section_type' => 'door_opening',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create door
        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_id' => $sectionId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'hinge_type' => 'full_overlay',
            'profile_type' => 'shaker',
            'fabrication_method' => 'cnc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assert door was created with correct relationships
        $door = DB::table('projects_doors')->find($doorId);
        $this->assertEquals($cabinetId, $door->cabinet_specification_id);
        $this->assertEquals($sectionId, $door->section_id);
        $this->assertEquals('D1', $door->door_name);
        $this->assertEquals('full_overlay', $door->hinge_type);
    }

    /**
     * Test creating drawer component
     *
     * @test
     */
    public function it_can_create_drawer_with_box_and_slide_details()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $sectionId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_number' => 2,
            'name' => 'Drawer Stack',
            'section_type' => 'drawer_stack',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $drawerId = DB::table('projects_drawers')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_id' => $sectionId,
            'drawer_number' => 1,
            'drawer_name' => 'DR1',
            'drawer_position' => 'top',
            'front_width_inches' => 22,
            'front_height_inches' => 6,
            'box_material' => 'maple',
            'slide_type' => 'blum_undermount',
            'soft_close' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assert drawer was created
        $drawer = DB::table('projects_drawers')->find($drawerId);
        $this->assertEquals('DR1', $drawer->drawer_name);
        $this->assertEquals('maple', $drawer->box_material);
        $this->assertEquals('blum_undermount', $drawer->slide_type);
        $this->assertTrue((bool) $drawer->soft_close);
    }

    /**
     * Test creating shelf component
     *
     * @test
     */
    public function it_can_create_adjustable_shelf()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $shelfId = DB::table('projects_shelves')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'shelf_number' => 1,
            'shelf_name' => 'S1',
            'shelf_type' => 'adjustable',
            'width_inches' => 17,
            'depth_inches' => 23,
            'thickness_inches' => 0.75,
            'material' => 'plywood',
            'edge_treatment' => 'edge_banded',
            'pin_hole_spacing' => 1.25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shelf = DB::table('projects_shelves')->find($shelfId);
        $this->assertEquals('adjustable', $shelf->shelf_type);
        $this->assertEquals(1.25, $shelf->pin_hole_spacing);
        $this->assertEquals('edge_banded', $shelf->edge_treatment);
    }

    /**
     * Test creating pullout component with procurement details
     *
     * @test
     */
    public function it_can_create_pullout_with_procurement_info()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $pulloutId = DB::table('projects_pullouts')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'pullout_number' => 1,
            'pullout_name' => 'P1',
            'pullout_type' => 'trash',
            'manufacturer' => 'Rev-A-Shelf',
            'model_number' => '5149-18DM-217',
            'unit_cost' => 189.50,
            'quantity' => 1,
            'mounting_type' => 'bottom_mount',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pullout = DB::table('projects_pullouts')->find($pulloutId);
        $this->assertEquals('trash', $pullout->pullout_type);
        $this->assertEquals('Rev-A-Shelf', $pullout->manufacturer);
        $this->assertEquals(189.50, $pullout->unit_cost);
    }

    /**
     * Test production tracking timestamps on doors
     *
     * @test
     */
    public function it_can_track_door_production_phases()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simulate production phases
        DB::table('projects_doors')->where('id', $doorId)->update([
            'cnc_cut_at' => now(),
        ]);

        DB::table('projects_doors')->where('id', $doorId)->update([
            'edge_banded_at' => now()->addHours(2),
        ]);

        DB::table('projects_doors')->where('id', $doorId)->update([
            'assembled_at' => now()->addHours(4),
        ]);

        $door = DB::table('projects_doors')->find($doorId);
        $this->assertNotNull($door->cnc_cut_at);
        $this->assertNotNull($door->edge_banded_at);
        $this->assertNotNull($door->assembled_at);
    }

    /**
     * Test QC inspection tracking
     *
     * @test
     */
    public function it_can_track_qc_inspection()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simulate QC inspection failure
        DB::table('projects_doors')->where('id', $doorId)->update([
            'qc_passed' => false,
            'qc_notes' => 'Chip on bottom rail',
            'qc_inspected_at' => now(),
            'qc_inspector_id' => 1,
        ]);

        $door = DB::table('projects_doors')->find($doorId);
        $this->assertFalse((bool) $door->qc_passed);
        $this->assertEquals('Chip on bottom rail', $door->qc_notes);
        $this->assertNotNull($door->qc_inspected_at);
    }

    /**
     * Test task assignment to project level
     *
     * @test
     */
    public function it_can_assign_task_to_project_level()
    {
        $projectId = $this->createMinimalHierarchy();

        $taskId = DB::table('projects_tasks')->insertGetId([
            'project_id' => $projectId,
            'title' => 'Design review for entire project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $task = DB::table('projects_tasks')->find($taskId);
        $this->assertEquals($projectId, $task->project_id);
        $this->assertNull($task->component_type);
        $this->assertNull($task->component_id);
    }

    /**
     * Test task assignment to component level (polymorphic)
     *
     * @test
     */
    public function it_can_assign_task_to_door_component()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taskId = DB::table('projects_tasks')->insertGetId([
            'project_id' => $projectId,
            'cabinet_specification_id' => $cabinetId,
            'component_type' => 'door',
            'component_id' => $doorId,
            'title' => 'CNC cut door D1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $task = DB::table('projects_tasks')->find($taskId);
        $this->assertEquals('door', $task->component_type);
        $this->assertEquals($doorId, $task->component_id);
    }

    /**
     * Test product inventory linking to cabinet
     *
     * @test
     */
    public function it_can_link_product_to_cabinet()
    {
        // Create a product first
        $productId = DB::table('products_products')->insertGetId([
            'name' => 'Shaker Style Base Cabinet - 36x34.5x24',
            'sku' => 'CAB-B36-SHAKER',
            'cost' => 250.00,
            'price' => 450.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectId = $this->createMinimalHierarchy();

        // Update cabinet with product link
        DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->update(['product_id' => $productId]);

        $cabinet = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->first();

        $this->assertEquals($productId, $cabinet->product_id);
    }

    /**
     * Test product inventory linking to door
     *
     * @test
     */
    public function it_can_link_product_to_door()
    {
        $productId = DB::table('products_products')->insertGetId([
            'name' => 'Shaker Door Blank - 3/4" Maple - 18x30',
            'sku' => 'DOOR-BLANK-MAPLE-18X30',
            'cost' => 45.00,
            'price' => 85.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'product_id' => $productId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $door = DB::table('projects_doors')->find($doorId);
        $this->assertEquals($productId, $door->product_id);
    }

    /**
     * Test cascade delete from cabinet to sections
     *
     * @test
     */
    public function it_cascades_delete_from_cabinet_to_sections()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $sectionId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'section_number' => 1,
            'name' => 'Door Opening',
            'section_type' => 'door_opening',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete cabinet
        DB::table('projects_cabinet_specifications')->where('id', $cabinetId)->delete();

        // Section should be deleted (cascade)
        $section = DB::table('projects_cabinet_sections')->find($sectionId);
        $this->assertNull($section);
    }

    /**
     * Test cascade delete from cabinet to doors
     *
     * @test
     */
    public function it_cascades_delete_from_cabinet_to_doors()
    {
        $projectId = $this->createMinimalHierarchy();
        $cabinetId = DB::table('projects_cabinet_specifications')
            ->where('cabinet_name', 'B36')
            ->value('id');

        $doorId = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $cabinetId,
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete cabinet
        DB::table('projects_cabinet_specifications')->where('id', $cabinetId)->delete();

        // Door should be deleted (cascade)
        $door = DB::table('projects_doors')->find($doorId);
        $this->assertNull($door);
    }

    /**
     * Helper: Create minimal project hierarchy
     */
    private function createMinimalHierarchy(): int
    {
        $projectId = DB::table('projects_projects')->insertGetId([
            'name' => 'Test Kitchen Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomId = DB::table('projects_rooms')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Kitchen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $locationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $roomId,
            'name' => 'Island',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $runId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $locationId,
            'run_name' => 'Island Base Run',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('projects_cabinet_specifications')->insert([
            'cabinet_run_id' => $runId,
            'cabinet_name' => 'B36',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $projectId;
    }
}
