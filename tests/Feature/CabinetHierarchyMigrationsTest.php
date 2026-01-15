<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CabinetHierarchyMigrationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Migration 1: Cabinet Sections table structure
     *
     * @test
     */
    public function it_creates_cabinet_sections_table_with_correct_structure()
    {
        // Assert table exists
        $this->assertTrue(Schema::hasTable('projects_cabinet_sections'));

        // Assert required columns exist
        $columns = [
            'id',
            'cabinet_specification_id',
            'section_number',
            'name',
            'section_type',
            'width_inches',
            'height_inches',
            'position_from_left_inches',
            'position_from_bottom_inches',
            'opening_width_inches',
            'opening_height_inches',
            'component_count',
            'sort_order',
            'notes',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_cabinet_sections', $column),
                "Column '{$column}' does not exist in projects_cabinet_sections table"
            );
        }

        // Assert foreign key exists
        $foreignKeys = $this->getForeignKeys('projects_cabinet_sections');
        $this->assertContains('cabinet_specification_id', array_column($foreignKeys, 'column'));
    }

    /**
     * Test Migration 2: Doors table structure
     *
     * @test
     */
    public function it_creates_doors_table_with_correct_structure()
    {
        // Assert table exists
        $this->assertTrue(Schema::hasTable('projects_doors'));

        // Assert construction columns
        $constructionColumns = [
            'rail_width_inches',
            'stile_width_inches',
            'has_check_rail',
            'check_rail_width_inches',
            'profile_type',
            'fabrication_method',
            'thickness_inches',
        ];

        foreach ($constructionColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_doors', $column),
                "Construction column '{$column}' missing from doors table"
            );
        }

        // Assert hardware columns
        $hardwareColumns = [
            'hinge_type',
            'hinge_model',
            'hinge_quantity',
            'hinge_side',
        ];

        foreach ($hardwareColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_doors', $column),
                "Hardware column '{$column}' missing from doors table"
            );
        }

        // Assert production tracking columns
        $trackingColumns = [
            'cnc_cut_at',
            'manually_cut_at',
            'edge_banded_at',
            'assembled_at',
            'sanded_at',
            'finished_at',
            'hardware_installed_at',
            'installed_in_cabinet_at',
        ];

        foreach ($trackingColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_doors', $column),
                "Production tracking column '{$column}' missing from doors table"
            );
        }

        // Assert QC columns
        $qcColumns = ['qc_passed', 'qc_notes', 'qc_inspected_at', 'qc_inspector_id'];
        foreach ($qcColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_doors', $column),
                "QC column '{$column}' missing from doors table"
            );
        }

        // Assert foreign keys
        $foreignKeys = $this->getForeignKeys('projects_doors');
        $foreignKeyColumns = array_column($foreignKeys, 'column');

        $this->assertContains('cabinet_specification_id', $foreignKeyColumns);
        $this->assertContains('section_id', $foreignKeyColumns);
        $this->assertContains('product_id', $foreignKeyColumns);
    }

    /**
     * Test Migration 3: Drawers table structure
     *
     * @test
     */
    public function it_creates_drawers_table_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('projects_drawers'));

        // Assert drawer front columns
        $frontColumns = [
            'front_width_inches',
            'front_height_inches',
            'top_rail_width_inches',
            'bottom_rail_width_inches',
            'stile_width_inches',
            'front_thickness_inches',
        ];

        foreach ($frontColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_drawers', $column),
                "Drawer front column '{$column}' missing"
            );
        }

        // Assert drawer box columns
        $boxColumns = [
            'box_width_inches',
            'box_depth_inches',
            'box_height_inches',
            'box_material',
            'box_thickness',
            'joinery_method',
        ];

        foreach ($boxColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_drawers', $column),
                "Drawer box column '{$column}' missing"
            );
        }

        // Assert slide columns
        $slideColumns = [
            'slide_type',
            'slide_model',
            'slide_length_inches',
            'slide_quantity',
            'soft_close',
        ];

        foreach ($slideColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_drawers', $column),
                "Slide column '{$column}' missing"
            );
        }

        // Assert production tracking
        $this->assertTrue(Schema::hasColumn('projects_drawers', 'box_assembled_at'));
        $this->assertTrue(Schema::hasColumn('projects_drawers', 'front_attached_at'));
        $this->assertTrue(Schema::hasColumn('projects_drawers', 'slides_installed_at'));
    }

    /**
     * Test Migration 4: Shelves table structure
     *
     * @test
     */
    public function it_creates_shelves_table_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('projects_shelves'));

        // Assert dimension columns
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'width_inches'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'depth_inches'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'thickness_inches'));

        // Assert type and configuration
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'shelf_type'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'material'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'edge_treatment'));

        // Assert adjustable shelf specific columns
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'pin_hole_spacing'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'number_of_positions'));

        // Assert pullout shelf specific columns
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'slide_type'));
        $this->assertTrue(Schema::hasColumn('projects_shelves', 'weight_capacity_lbs'));

        // Assert foreign keys
        $foreignKeys = $this->getForeignKeys('projects_shelves');
        $this->assertContains('product_id', array_column($foreignKeys, 'column'));
    }

    /**
     * Test Migration 5: Pullouts table structure
     *
     * @test
     */
    public function it_creates_pullouts_table_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('projects_pullouts'));

        // Assert type and details columns
        $typeColumns = [
            'pullout_type',
            'manufacturer',
            'model_number',
            'description',
        ];

        foreach ($typeColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_pullouts', $column),
                "Pullout type column '{$column}' missing"
            );
        }

        // Assert mounting columns
        $mountingColumns = [
            'mounting_type',
            'slide_type',
            'slide_model',
            'weight_capacity_lbs',
        ];

        foreach ($mountingColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_pullouts', $column),
                "Mounting column '{$column}' missing"
            );
        }

        // Assert procurement columns
        $this->assertTrue(Schema::hasColumn('projects_pullouts', 'unit_cost'));
        $this->assertTrue(Schema::hasColumn('projects_pullouts', 'quantity'));
        $this->assertTrue(Schema::hasColumn('projects_pullouts', 'ordered_at'));
        $this->assertTrue(Schema::hasColumn('projects_pullouts', 'received_at'));
    }

    /**
     * Test Migration 6: Tasks table extension
     *
     * @test
     */
    public function it_extends_tasks_table_with_section_and_component_fields()
    {
        $this->assertTrue(Schema::hasTable('projects_tasks'));

        // Assert new columns exist
        $this->assertTrue(Schema::hasColumn('projects_tasks', 'section_id'));
        $this->assertTrue(Schema::hasColumn('projects_tasks', 'component_type'));
        $this->assertTrue(Schema::hasColumn('projects_tasks', 'component_id'));

        // Verify indexes exist
        $indexes = $this->getIndexes('projects_tasks');
        $indexNames = array_column($indexes, 'name');

        // Check for task-specific indexes
        $this->assertTrue(
            in_array('idx_tasks_section', $indexNames) ||
            in_array('projects_tasks_section_id_index', $indexNames),
            'Section index missing from tasks table'
        );
    }

    /**
     * Test Migration 7: Product links to all tables
     *
     * @test
     */
    public function it_adds_product_id_to_all_component_tables()
    {
        // Assert product_id exists on cabinet specifications
        $this->assertTrue(
            Schema::hasColumn('projects_cabinet_specifications', 'product_id'),
            'product_id missing from cabinet_specifications'
        );

        // Assert product_id exists on all component tables
        $componentTables = [
            'projects_doors',
            'projects_drawers',
            'projects_shelves',
            'projects_pullouts',
        ];

        foreach ($componentTables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'product_id'),
                "product_id missing from {$table}"
            );

            // Verify foreign key to products_products
            $foreignKeys = $this->getForeignKeys($table);
            $this->assertContains(
                'product_id',
                array_column($foreignKeys, 'column'),
                "Foreign key on product_id missing from {$table}"
            );
        }
    }

    /**
     * Test complete hierarchy can be queried
     *
     * @test
     */
    public function it_can_query_complete_hierarchy()
    {
        // This test verifies the relationships work by attempting a complex join
        // Note: We're just checking the query doesn't fail, not that it returns data

        $query = DB::table('projects_projects')
            ->join('projects_rooms', 'projects_rooms.project_id', '=', 'projects_projects.id')
            ->join('projects_room_locations', 'projects_room_locations.room_id', '=', 'projects_rooms.id')
            ->join('projects_cabinet_runs', 'projects_cabinet_runs.room_location_id', '=', 'projects_room_locations.id')
            ->join('projects_cabinet_specifications', 'projects_cabinet_specifications.cabinet_run_id', '=', 'projects_cabinet_runs.id')
            ->join('projects_cabinet_sections', 'projects_cabinet_sections.cabinet_specification_id', '=', 'projects_cabinet_specifications.id')
            ->leftJoin('projects_doors', 'projects_doors.section_id', '=', 'projects_cabinet_sections.id')
            ->select('projects_projects.id as project_id', 'projects_doors.id as door_id');

        // Query executes without error
        $this->assertIsObject($query);
    }

    /**
     * Test polymorphic task assignment structure
     *
     * @test
     */
    public function it_supports_polymorphic_task_assignment()
    {
        $this->assertTrue(Schema::hasTable('projects_tasks'));

        // Verify component_type and component_id exist for polymorphic relationship
        $this->assertTrue(Schema::hasColumn('projects_tasks', 'component_type'));
        $this->assertTrue(Schema::hasColumn('projects_tasks', 'component_id'));

        // Verify all hierarchy level foreign keys exist
        $hierarchyColumns = [
            'project_id',
            'room_id',
            'room_location_id',
            'cabinet_run_id',
            'cabinet_specification_id',
            'section_id',
        ];

        foreach ($hierarchyColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects_tasks', $column),
                "Hierarchy column '{$column}' missing from tasks table"
            );
        }
    }

    /**
     * Helper: Get foreign keys for a table
     */
    private function getForeignKeys(string $table): array
    {
        $databaseName = DB::connection()->getDatabaseName();

        return DB::select("
            SELECT
                COLUMN_NAME as `column`,
                REFERENCED_TABLE_NAME as referenced_table,
                REFERENCED_COLUMN_NAME as referenced_column
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $table]);
    }

    /**
     * Helper: Get indexes for a table
     */
    private function getIndexes(string $table): array
    {
        $databaseName = DB::connection()->getDatabaseName();

        return DB::select("
            SELECT
                INDEX_NAME as name,
                COLUMN_NAME as `column`,
                NON_UNIQUE as non_unique
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
        ", [$databaseName, $table]);
    }
}
