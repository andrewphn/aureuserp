<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Friendship Lane Complete Job Seeder
 *
 * Seeds comprehensive sample data for the "25 Friendship Lane" project,
 * demonstrating a complete TCS woodworking job flowing through all
 * production stages.
 *
 * Run with: php artisan db:seed --class=FriendshipLaneJobSeeder
 */
class FriendshipLaneJobSeeder extends Seeder
{
    protected Carbon $now;
    protected array $data;
    protected array $refs = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->now = Carbon::now();

        // Load JSON data
        $jsonPath = database_path('seeders/data/friendship-lane-complete-job.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $this->data = json_decode(file_get_contents($jsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Invalid JSON: " . json_last_error_msg());
            return;
        }

        $this->command->info("\n=== 25 Friendship Lane Job Seeder ===\n");
        $this->command->info("Project: {$this->data['_meta']['project_summary']['name']}");
        $this->command->info("Total Value: \${$this->data['_meta']['project_summary']['total_value']}");
        $this->command->info("Linear Feet: {$this->data['_meta']['project_summary']['total_linear_feet']} LF\n");

        DB::beginTransaction();

        try {
            $this->seedProjectStages();
            $this->seedCustomer();
            $this->seedProject();
            $this->seedTaskStages(); // Must be after project creation (needs project_id)
            $this->seedProjectAddresses();
            $this->seedPdfDocument();
            $this->seedPdfPages();
            $this->seedRooms();
            $this->seedRoomLocations();
            $this->seedCabinetRuns();
            $this->seedCabinetSpecifications();
            $this->seedDoors();
            $this->seedDrawers();
            $this->seedShelves();
            $this->seedPullouts();
            $this->seedPdfAnnotations();
            $this->seedBom();
            $this->seedHardwareRequirements();
            $this->seedMilestones();
            $this->seedTasks();
            $this->seedSubtasks();
            $this->seedSalesOrder();
            $this->seedSalesOrderLines();
            $this->seedChangeOrders();
            $this->seedChangeOrderLines();

            DB::commit();

            $this->command->info("\n=== Seeding Complete ===\n");
            $this->command->info("Project ID: {$this->refs['project']}");
            $this->command->info("Customer ID: {$this->refs['customer']}");
            $this->command->info("Sales Order ID: {$this->refs['sales_order']}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Seeding failed: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Seed project stages (Discovery, Design, Sourcing, Production, Delivery)
     */
    protected function seedProjectStages(): void
    {
        $this->command->info("1. Seeding project stages...");

        foreach ($this->data['project_stages'] as $stage) {
            $existing = DB::table('projects_project_stages')
                ->where('name', $stage['name'])
                ->first();

            if ($existing) {
                $this->refs['stage_' . $stage['stage_key']] = $existing->id;
                $this->command->info("   ✓ Stage exists: {$stage['name']} (ID: {$existing->id})");
            } else {
                $id = DB::table('projects_project_stages')->insertGetId([
                    'name' => $stage['name'],
                    'color' => $stage['color'],
                    'is_active' => $stage['is_active'],
                    'is_collapsed' => $stage['is_collapsed'],
                    'sort' => $stage['sort'],
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
                $this->refs['stage_' . $stage['stage_key']] = $id;
                $this->command->info("   ✓ Created stage: {$stage['name']} (ID: {$id})");
            }
        }
    }

    /**
     * Seed task stages (Backlog, To Do, In Progress, Review, Done)
     * Must be called AFTER seedProject() as it requires project_id
     */
    protected function seedTaskStages(): void
    {
        $this->command->info("4. Seeding task stages...");

        $projectId = $this->refs['project'];

        foreach ($this->data['task_stages'] as $stage) {
            // Check if stage exists for THIS project
            $existing = DB::table('projects_task_stages')
                ->where('name', $stage['name'])
                ->where('project_id', $projectId)
                ->first();

            if ($existing) {
                $this->refs['task_stage_' . strtolower(str_replace(' ', '_', $stage['name']))] = $existing->id;
                $this->command->info("   ✓ Task stage exists: {$stage['name']}");
            } else {
                $id = DB::table('projects_task_stages')->insertGetId([
                    'project_id' => $projectId,
                    'name' => $stage['name'],
                    'is_active' => $stage['is_active'],
                    'is_collapsed' => $stage['is_collapsed'],
                    'sort' => $stage['sort'],
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
                $this->refs['task_stage_' . strtolower(str_replace(' ', '_', $stage['name']))] = $id;
                $this->command->info("   ✓ Created task stage: {$stage['name']}");
            }
        }
    }

    /**
     * Seed customer (Trottier Fine Woodworking)
     */
    protected function seedCustomer(): void
    {
        $this->command->info("2. Seeding customer...");

        $customer = $this->data['customer'];

        // Check if customer exists
        $existing = DB::table('partners_partners')
            ->where('name', $customer['name'])
            ->first();

        if ($existing) {
            $this->refs['customer'] = $existing->id;
            $this->command->info("   ✓ Customer exists: {$customer['name']} (ID: {$existing->id})");
            return;
        }

        // Get state ID for Massachusetts
        $stateId = DB::table('states')->where('code', 'MA')->value('id');

        // Get country ID for USA
        $countryId = DB::table('countries')->where('code', 'US')->value('id') ?? 233;

        $id = DB::table('partners_partners')->insertGetId([
            'account_type' => $customer['account_type'],
            'sub_type' => $customer['sub_type'],
            'name' => $customer['name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'mobile' => $customer['mobile'],
            'website' => $customer['website'],
            'tax_id' => $customer['tax_id'],
            'reference' => $customer['reference'],
            'color' => $customer['color'],
            'customer_rank' => $customer['customer_rank'],
            'supplier_rank' => $customer['supplier_rank'],
            'comment' => $customer['comment'],
            'street1' => $customer['street1'],
            'street2' => $customer['street2'],
            'city' => $customer['city'],
            'zip' => $customer['zip'],
            'state_id' => $stateId,
            'country_id' => $countryId,
            'is_active' => $customer['is_active'],
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->refs['customer'] = $id;
        $this->command->info("   ✓ Created customer: {$customer['name']} (ID: {$id})");
    }

    /**
     * Seed project
     */
    protected function seedProject(): void
    {
        $this->command->info("3. Seeding project...");

        $project = $this->data['project'];

        // Check if project exists
        $existing = DB::table('projects_projects')
            ->where('project_number', $project['project_number'])
            ->first();

        if ($existing) {
            $this->refs['project'] = $existing->id;
            $this->command->info("   ✓ Project exists: {$project['name']} (ID: {$existing->id})");
            return;
        }

        // Get production stage ID
        $stageId = $this->refs['stage_' . $project['current_production_stage']] ?? null;

        $id = DB::table('projects_projects')->insertGetId([
            'name' => $project['name'],
            'project_number' => $project['project_number'],
            'project_type' => $project['project_type'],
            'lead_source' => $project['lead_source'],
            'budget_range' => $project['budget_range'],
            'complexity_score' => $project['complexity_score'],
            'tasks_label' => $project['tasks_label'],
            'description' => $project['description'],
            'visibility' => $project['visibility'],
            'color' => $project['color'],
            'sort' => $project['sort'],
            'start_date' => $project['start_date'],
            'end_date' => $project['end_date'],
            'desired_completion_date' => $project['desired_completion_date'],
            'allocated_hours' => $project['allocated_hours'],
            'estimated_linear_feet' => $project['estimated_linear_feet'],
            'allow_timesheets' => $project['allow_timesheets'],
            'allow_milestones' => $project['allow_milestones'],
            'allow_task_dependencies' => $project['allow_task_dependencies'],
            'is_active' => $project['is_active'],
            'stage_id' => $stageId,
            'partner_id' => $this->refs['customer'],
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->refs['project'] = $id;
        $this->command->info("   ✓ Created project: {$project['name']} (ID: {$id})");
    }

    /**
     * Seed project addresses
     */
    protected function seedProjectAddresses(): void
    {
        $this->command->info("5. Seeding project addresses...");

        // Get state ID for Massachusetts
        $stateId = DB::table('states')->where('code', 'MA')->value('id');
        $countryId = DB::table('countries')->where('code', 'US')->value('id') ?? 233;

        foreach ($this->data['project_addresses'] as $address) {
            $id = DB::table('projects_project_addresses')->insertGetId([
                'project_id' => $this->refs['project'],
                'type' => $address['address_type'], // Column name is 'type' not 'address_type'
                'street1' => $address['street1'],
                'street2' => $address['street2'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state_id' => $stateId,
                'country_id' => $countryId,
                'is_primary' => $address['is_primary'],
                'notes' => $address['notes'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->command->info("   ✓ Created address: {$address['street1']}");
        }
    }

    /**
     * Seed PDF document
     */
    protected function seedPdfDocument(): void
    {
        $this->command->info("6. Seeding PDF document...");

        $doc = $this->data['pdf_document'];

        // Get first user for uploaded_by (required field)
        $userId = DB::table('users')->first()->id ?? 1;

        $id = DB::table('pdf_documents')->insertGetId([
            'module_type' => $doc['module_type'],
            'module_id' => $this->refs['project'],
            'file_name' => $doc['file_name'],
            'document_type' => $doc['document_type'],
            'file_path' => $doc['file_path'],
            'file_size' => $doc['file_size'],
            'mime_type' => $doc['mime_type'],
            'page_count' => $doc['page_count'],
            'version_number' => $doc['version_number'],
            'is_latest_version' => $doc['is_latest_version'],
            'is_primary_reference' => $doc['is_primary_reference'],
            'uploaded_by' => $userId,
            'tags' => json_encode($doc['tags']),
            'metadata' => json_encode($doc['metadata']),
            'notes' => $doc['notes'],
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->refs['pdf_document'] = $id;
        $this->command->info("   ✓ Created PDF document: {$doc['file_name']} (ID: {$id})");
    }

    /**
     * Seed PDF pages
     */
    protected function seedPdfPages(): void
    {
        $this->command->info("7. Seeding PDF pages...");

        foreach ($this->data['pdf_pages'] as $page) {
            $id = DB::table('pdf_pages')->insertGetId([
                'document_id' => $this->refs['pdf_document'],  // Column is 'document_id' not 'pdf_document_id'
                'page_number' => $page['page_number'],
                'page_type' => $page['page_type'],
                'width' => $page['width'],
                'height' => $page['height'],
                'rotation' => $page['rotation'],
                'extracted_text' => $page['extracted_text'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs['pdf_page_' . $page['page_number']] = $id;
        }
        $this->command->info("   ✓ Created " . count($this->data['pdf_pages']) . " PDF pages");
    }

    /**
     * Seed rooms
     */
    protected function seedRooms(): void
    {
        $this->command->info("8. Seeding rooms...");

        foreach ($this->data['rooms'] as $room) {
            $id = DB::table('projects_rooms')->insertGetId([
                'project_id' => $this->refs['project'],
                'name' => $room['name'],
                'cabinet_level' => $room['cabinet_level'],
                'material_category' => $room['material_category'],
                'finish_option' => $room['finish_option'],
                'room_type' => $room['room_type'],
                'floor_number' => $room['floor_number'],
                'pdf_page_number' => $room['pdf_page_number'],
                'pdf_room_label' => $room['pdf_room_label'],
                'pdf_detail_number' => $room['pdf_detail_number'],
                'pdf_notes' => $room['pdf_notes'],
                'notes' => $room['notes'],
                'sort_order' => $room['sort_order'],
                'total_linear_feet_tier_1' => $room['total_linear_feet_tier_1'],
                'total_linear_feet_tier_2' => $room['total_linear_feet_tier_2'],
                'total_linear_feet_tier_3' => $room['total_linear_feet_tier_3'],
                'total_linear_feet_tier_4' => $room['total_linear_feet_tier_4'],
                'total_linear_feet_tier_5' => $room['total_linear_feet_tier_5'],
                'floating_shelves_lf' => $room['floating_shelves_lf'],
                'countertop_sqft' => $room['countertop_sqft'],
                'trim_millwork_lf' => $room['trim_millwork_lf'],
                'material_type' => $room['material_type'],
                'estimated_cabinet_value' => $room['estimated_cabinet_value'],
                'estimated_additional_products' => $room['estimated_additional_products'],
                'estimated_finish_value' => $room['estimated_finish_value'],
                'estimated_project_value' => $room['estimated_project_value'],
                'quoted_price' => $room['quoted_price'],
                'margin_percentage' => $room['margin_percentage'],
                'labor_hours_estimate' => $room['labor_hours_estimate'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs[$room['_ref']] = $id;
            $this->command->info("   ✓ Created room: {$room['name']} (ID: {$id})");
        }
    }

    /**
     * Seed room locations
     */
    protected function seedRoomLocations(): void
    {
        $this->command->info("9. Seeding room locations...");

        foreach ($this->data['room_locations'] as $location) {
            $roomId = $this->refs[$location['_room_ref']] ?? null;

            $id = DB::table('projects_room_locations')->insertGetId([
                'room_id' => $roomId,
                'name' => $location['name'],
                'cabinet_level' => $location['cabinet_level'],
                'material_category' => $location['material_category'],
                'finish_option' => $location['finish_option'],
                'location_type' => $location['location_type'],
                'sequence' => $location['sequence'],
                'elevation_reference' => $location['elevation_reference'],
                'notes' => $location['notes'],
                'sort_order' => $location['sort_order'],
                'material_type' => $location['material_type'],
                'wood_species' => $location['wood_species'] ?? null,
                'door_style' => $location['door_style'] ?? null,
                'finish_type' => $location['finish_type'] ?? null,
                'paint_color' => $location['paint_color'] ?? null,
                'stain_color' => $location['stain_color'] ?? null,
                'overall_width_inches' => $location['overall_width_inches'] ?? null,
                'overall_height_inches' => $location['overall_height_inches'] ?? null,
                'overall_depth_inches' => $location['overall_depth_inches'] ?? null,
                'toe_kick_height_inches' => $location['toe_kick_height_inches'] ?? null,
                'cabinet_count' => $location['cabinet_count'] ?? null,
                'total_linear_feet' => $location['total_linear_feet'] ?? null,
                'cabinet_type_primary' => $location['cabinet_type_primary'] ?? null,
                'has_face_frame' => $location['has_face_frame'] ?? false,
                'face_frame_width_inches' => $location['face_frame_width_inches'] ?? null,
                'has_beaded_face_frame' => $location['has_beaded_face_frame'] ?? false,
                'inset_doors' => $location['inset_doors'] ?? false,
                'overlay_type' => $location['overlay_type'] ?? null,
                'hinge_type' => $location['hinge_type'] ?? null,
                'slide_type' => $location['slide_type'] ?? null,
                'soft_close_doors' => $location['soft_close_doors'] ?? false,
                'soft_close_drawers' => $location['soft_close_drawers'] ?? false,
                'has_appliance_panels' => $location['has_appliance_panels'] ?? false,
                'countertop_type' => $location['countertop_type'] ?? null,
                'countertop_notes' => $location['countertop_notes'] ?? null,
                'approval_status' => $location['approval_status'] ?? 'pending',
                'complexity_tier' => $location['complexity_tier'] ?? null,
                'elevation_view' => $location['elevation_view'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs[$location['_ref']] = $id;
            $this->command->info("   ✓ Created location: {$location['name']}");
        }
    }

    /**
     * Seed cabinet runs
     */
    protected function seedCabinetRuns(): void
    {
        $this->command->info("10. Seeding cabinet runs...");

        foreach ($this->data['cabinet_runs'] as $run) {
            $locationId = $this->refs[$run['_location_ref']] ?? null;

            $id = DB::table('projects_cabinet_runs')->insertGetId([
                'room_location_id' => $locationId,
                'name' => $run['name'],
                'cabinet_level' => $run['cabinet_level'] ?? null,
                'material_category' => $run['material_category'] ?? null,
                'finish_option' => $run['finish_option'] ?? null,
                'run_type' => $run['run_type'] ?? null,
                'total_linear_feet' => $run['total_linear_feet'] ?? 0,
                'start_wall_measurement' => $run['start_wall_measurement'] ?? null,
                'end_wall_measurement' => $run['end_wall_measurement'] ?? null,
                'notes' => $run['notes'] ?? null,
                'sort_order' => $run['sort_order'] ?? 0,  // NOT NULL default 0
                'cabinet_count' => $run['cabinet_count'] ?? 0,  // NOT NULL default 0
                'material_type' => $run['material_type'] ?? null,
                'wood_species' => $run['wood_species'] ?? null,
                'finish_type' => $run['finish_type'] ?? null,
                'sheet_goods_required_sqft' => $run['sheet_goods_required_sqft'] ?? null,
                'solid_wood_required_bf' => $run['solid_wood_required_bf'] ?? null,
                'production_status' => $run['production_status'] ?? 'pending',  // NOT NULL default 'pending'
                'estimated_labor_hours' => $run['estimated_labor_hours'] ?? null,
                'blum_hinges_total' => $run['blum_hinges_total'] ?? 0,  // NOT NULL default 0
                'blum_slides_total' => $run['blum_slides_total'] ?? 0,  // NOT NULL default 0
                'shelf_pins_total' => $run['shelf_pins_total'] ?? 0,  // NOT NULL default 0
                'hardware_kitted' => $run['hardware_kitted'] ?? false,  // NOT NULL default 0
                'ready_for_delivery' => $run['ready_for_delivery'] ?? false,  // NOT NULL default 0
                'primer_type' => $run['primer_type'] ?? null,
                'primer_coats' => $run['primer_coats'] ?? null,
                'topcoat_type' => $run['topcoat_type'] ?? null,
                'topcoat_coats' => $run['topcoat_coats'] ?? null,
                'sheen_level' => $run['sheen_level'] ?? null,
                'finishing_notes' => $run['finishing_notes'] ?? null,
                'cnc_notes' => $run['cnc_notes'] ?? null,
                'labor_notes' => $run['labor_notes'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs[$run['_ref']] = $id;
            $this->command->info("   ✓ Created run: {$run['name']} ({$run['total_linear_feet']} LF)");
        }
    }

    /**
     * Seed cabinet specifications
     */
    protected function seedCabinetSpecifications(): void
    {
        $this->command->info("11. Seeding cabinet specifications...");

        foreach ($this->data['cabinet_specifications'] as $spec) {
            $runId = $this->refs[$spec['_run_ref']] ?? null;
            $roomId = $this->refs[$spec['_room_ref']] ?? null;

            $id = DB::table('projects_cabinet_specifications')->insertGetId([
                'cabinet_run_id' => $runId,
                'room_id' => $roomId,
                'cabinet_number' => $spec['cabinet_number'],
                'cabinet_level' => $spec['cabinet_level'] ?? null,
                'material_category' => $spec['material_category'] ?? null,
                'finish_option' => $spec['finish_option'] ?? null,
                'position_in_run' => $spec['position_in_run'],
                'wall_position_start_inches' => $spec['wall_position_start_inches'] ?? null,
                'length_inches' => $spec['length_inches'],
                'width_inches' => $spec['width_inches'] ?? null,
                'depth_inches' => $spec['depth_inches'] ?? null,
                'height_inches' => $spec['height_inches'],
                'linear_feet' => $spec['linear_feet'],
                // NOT NULL fields with defaults
                'quantity' => $spec['quantity'] ?? 1,
                'has_back_panel' => $spec['has_back_panel'] ?? true,
                'has_face_frame' => $spec['has_face_frame'] ?? true,
                'beaded_face_frame' => $spec['beaded_face_frame'] ?? false,
                'door_count' => $spec['door_count'] ?? 0,
                'has_glass_doors' => $spec['has_glass_doors'] ?? false,
                'drawer_count' => $spec['drawer_count'] ?? 0,
                'dovetail_drawers' => $spec['dovetail_drawers'] ?? true,
                'drawer_soft_close' => $spec['drawer_soft_close'] ?? true,
                'adjustable_shelf_count' => $spec['adjustable_shelf_count'] ?? 0,
                'fixed_shelf_count' => $spec['fixed_shelf_count'] ?? 0,
                'shelf_pin_holes' => $spec['shelf_pin_holes'] ?? 0,
                'hinge_quantity' => $spec['hinge_quantity'] ?? 0,
                'slide_quantity' => $spec['slide_quantity'] ?? 0,
                'has_pullout' => $spec['has_pullout'] ?? false,
                'has_lazy_susan' => $spec['has_lazy_susan'] ?? false,
                'has_tray_dividers' => $spec['has_tray_dividers'] ?? false,
                'has_spice_rack' => $spec['has_spice_rack'] ?? false,
                'appliance_panel' => $spec['appliance_panel'] ?? false,
                'has_microwave_shelf' => $spec['has_microwave_shelf'] ?? false,
                'has_trash_pullout' => $spec['has_trash_pullout'] ?? false,
                'has_hamper' => $spec['has_hamper'] ?? false,
                'has_wine_rack' => $spec['has_wine_rack'] ?? false,
                'has_sink_cutout' => $spec['has_sink_cutout'] ?? false,
                'has_cooktop_cutout' => $spec['has_cooktop_cutout'] ?? false,
                'has_electrical_cutouts' => $spec['has_electrical_cutouts'] ?? false,
                'requires_scribing' => $spec['requires_scribing'] ?? false,
                // Nullable fields
                'unit_price_per_lf' => $spec['unit_price_per_lf'] ?? null,
                'total_price' => $spec['total_price'] ?? null,
                'toe_kick_height' => $spec['toe_kick_height'] ?? null,
                'toe_kick_depth' => $spec['toe_kick_depth'] ?? null,
                'box_material' => $spec['box_material'] ?? null,
                'box_thickness' => $spec['box_thickness'] ?? null,
                'joinery_method' => $spec['joinery_method'] ?? null,
                'back_panel_thickness' => $spec['back_panel_thickness'] ?? null,
                'face_frame_stile_width' => $spec['face_frame_stile_width'] ?? null,
                'face_frame_rail_width' => $spec['face_frame_rail_width'] ?? null,
                'face_frame_material' => $spec['face_frame_material'] ?? null,
                'door_style' => $spec['door_style'] ?? null,
                'door_mounting' => $spec['door_mounting'] ?? null,
                'drawer_sizes_json' => $spec['drawer_sizes_json'] ?? null,
                'drawer_box_material' => $spec['drawer_box_material'] ?? null,
                'shelf_thickness' => $spec['shelf_thickness'] ?? null,
                'shelf_material' => $spec['shelf_material'] ?? null,
                'hinge_model' => $spec['hinge_model'] ?? null,
                'slide_model' => $spec['slide_model'] ?? null,
                'sink_dimensions_json' => $spec['sink_dimensions_json'] ?? null,
                'lazy_susan_model' => $spec['lazy_susan_model'] ?? null,
                'hardware_notes' => $spec['hardware_notes'] ?? null,
                'custom_modifications' => $spec['custom_modifications'] ?? null,
                'shop_notes' => $spec['shop_notes'] ?? null,
                'complexity_tier' => $spec['complexity_tier'] ?? null,
                'base_price_per_lf' => $spec['base_price_per_lf'] ?? null,
                'cabinet_linear_feet' => $spec['cabinet_linear_feet'] ?? null,
                'estimated_cabinet_price' => $spec['estimated_cabinet_price'] ?? null,
                'plywood_sqft' => $spec['plywood_sqft'] ?? null,
                'solid_wood_bf' => $spec['solid_wood_bf'] ?? null,
                'edge_banding_lf' => $spec['edge_banding_lf'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs[$spec['_ref']] = $id;
        }
        $this->command->info("   ✓ Created " . count($this->data['cabinet_specifications']) . " cabinet specifications");
    }

    /**
     * Seed doors
     */
    protected function seedDoors(): void
    {
        $this->command->info("12. Seeding doors...");

        foreach ($this->data['doors'] as $door) {
            $cabinetId = $this->refs[$door['_cabinet_ref']] ?? null;

            DB::table('projects_doors')->insert([
                'cabinet_specification_id' => $cabinetId,
                'door_number' => $door['door_number'],
                'door_name' => $door['door_name'] ?? null,
                'sort_order' => $door['sort_order'] ?? 1,
                'width_inches' => $door['width_inches'],
                'height_inches' => $door['height_inches'],
                'rail_width_inches' => $door['rail_width_inches'] ?? null,
                'style_width_inches' => $door['style_width_inches'] ?? null,
                'profile_type' => $door['profile_type'] ?? null,
                'fabrication_method' => $door['fabrication_method'] ?? null,
                'thickness_inches' => $door['thickness_inches'] ?? null,
                'hinge_type' => $door['hinge_type'] ?? null,
                'hinge_model' => $door['hinge_model'] ?? null,
                'hinge_quantity' => $door['hinge_quantity'] ?? null,
                'hinge_side' => $door['hinge_side'] ?? null,
                'has_glass' => $door['has_glass'] ?? false,
                'finish_type' => $door['finish_type'] ?? null,
                'paint_color' => $door['paint_color'] ?? null,
                'has_decorative_hardware' => $door['has_decorative_hardware'] ?? false,
                'decorative_hardware_model' => $door['decorative_hardware_model'] ?? null,
                'notes' => $door['notes'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['doors']) . " doors");
    }

    /**
     * Seed drawers
     */
    protected function seedDrawers(): void
    {
        $this->command->info("13. Seeding drawers...");

        foreach ($this->data['drawers'] as $drawer) {
            $cabinetId = $this->refs[$drawer['_cabinet_ref']] ?? null;

            DB::table('projects_drawers')->insert([
                'cabinet_specification_id' => $cabinetId,
                'drawer_number' => $drawer['drawer_number'],
                'drawer_name' => $drawer['drawer_name'] ?? null,
                'drawer_position' => $drawer['drawer_position'] ?? null,
                'sort_order' => $drawer['sort_order'] ?? 1,
                'front_width_inches' => $drawer['front_width_inches'] ?? null,
                'front_height_inches' => $drawer['front_height_inches'] ?? null,
                'profile_type' => $drawer['profile_type'] ?? null,
                'fabrication_method' => $drawer['fabrication_method'] ?? null,
                'front_thickness_inches' => $drawer['front_thickness_inches'] ?? null,
                'box_width_inches' => $drawer['box_width_inches'] ?? null,
                'box_depth_inches' => $drawer['box_depth_inches'] ?? null,
                'box_height_inches' => $drawer['box_height_inches'] ?? null,
                'box_material' => $drawer['box_material'] ?? null,
                'box_thickness' => $drawer['box_thickness'] ?? null,
                'joinery_method' => $drawer['joinery_method'] ?? null,
                'slide_type' => $drawer['slide_type'] ?? null,
                'slide_model' => $drawer['slide_model'] ?? null,
                'slide_length_inches' => $drawer['slide_length_inches'] ?? null,
                'slide_quantity' => $drawer['slide_quantity'] ?? null,
                'soft_close' => $drawer['soft_close'] ?? false,
                'finish_type' => $drawer['finish_type'] ?? null,
                'paint_color' => $drawer['paint_color'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['drawers']) . " drawers");
    }

    /**
     * Seed shelves
     */
    protected function seedShelves(): void
    {
        $this->command->info("14. Seeding shelves...");

        foreach ($this->data['shelves'] as $shelf) {
            $cabinetId = $this->refs[$shelf['_cabinet_ref']] ?? null;

            DB::table('projects_shelves')->insert([
                'cabinet_specification_id' => $cabinetId,
                'shelf_number' => $shelf['shelf_number'],
                'shelf_name' => $shelf['shelf_name'] ?? null,
                'sort_order' => $shelf['sort_order'] ?? 1,
                'width_inches' => $shelf['width_inches'],
                'depth_inches' => $shelf['depth_inches'],
                'thickness_inches' => $shelf['thickness_inches'],
                'shelf_type' => $shelf['shelf_type'] ?? 'adjustable',
                'material' => $shelf['material'] ?? null,
                'edge_treatment' => $shelf['edge_treatment'] ?? null,
                'pin_hole_spacing' => $shelf['pin_hole_spacing'] ?? null,
                'number_of_positions' => $shelf['number_of_positions'] ?? null,
                'finish_type' => $shelf['finish_type'] ?? null,
                'stain_color' => $shelf['stain_color'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['shelves']) . " shelves");
    }

    /**
     * Seed pullouts
     */
    protected function seedPullouts(): void
    {
        $this->command->info("15. Seeding pullouts...");

        foreach ($this->data['pullouts'] as $pullout) {
            $cabinetId = $this->refs[$pullout['_cabinet_ref']] ?? null;

            DB::table('projects_pullouts')->insert([
                'cabinet_specification_id' => $cabinetId,
                'pullout_number' => $pullout['pullout_number'],
                'pullout_name' => $pullout['pullout_name'] ?? null,
                'sort_order' => $pullout['sort_order'] ?? 1,
                'pullout_type' => $pullout['pullout_type'],
                'manufacturer' => $pullout['manufacturer'] ?? null,
                'model_number' => $pullout['model_number'] ?? null,
                'description' => $pullout['description'] ?? null,
                'width_inches' => $pullout['width_inches'] ?? null,
                'height_inches' => $pullout['height_inches'] ?? null,
                'depth_inches' => $pullout['depth_inches'] ?? null,
                'mounting_type' => $pullout['mounting_type'] ?? null,
                'slide_type' => $pullout['slide_type'] ?? null,
                'soft_close' => $pullout['soft_close'] ?? false,
                'weight_capacity_lbs' => $pullout['weight_capacity_lbs'] ?? null,
                'unit_cost' => $pullout['unit_cost'] ?? null,
                'quantity' => $pullout['quantity'] ?? 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['pullouts']) . " pullouts");
    }

    /**
     * Seed PDF annotations
     */
    protected function seedPdfAnnotations(): void
    {
        $this->command->info("16. Seeding PDF annotations...");

        foreach ($this->data['pdf_annotations'] as $annotation) {
            $pageId = $this->refs['pdf_page_' . $annotation['_page_number']] ?? null;
            $runId = isset($annotation['_run_ref']) ? ($this->refs[$annotation['_run_ref']] ?? null) : null;
            $cabinetId = isset($annotation['_cabinet_ref']) ? ($this->refs[$annotation['_cabinet_ref']] ?? null) : null;

            DB::table('pdf_page_annotations')->insert([
                'pdf_page_id' => $pageId,
                'cabinet_run_id' => $runId,
                'cabinet_specification_id' => $cabinetId,
                'annotation_type' => $annotation['annotation_type'],
                'view_type' => $annotation['view_type'] ?? null,
                'label' => $annotation['label'],
                'x' => $annotation['x'],
                'y' => $annotation['y'],
                'width' => $annotation['width'],
                'height' => $annotation['height'],
                'measurement_width' => $annotation['measurement_width'] ?? null,
                'measurement_height' => $annotation['measurement_height'] ?? null,
                'visual_properties' => json_encode($annotation['visual_properties'] ?? null),
                'notes' => $annotation['notes'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['pdf_annotations']) . " annotations");
    }

    /**
     * Seed Bill of Materials
     */
    protected function seedBom(): void
    {
        $this->command->info("17. Seeding Bill of Materials...");

        // Get or create a placeholder product for BOM items (product_id is NOT NULL with FK)
        $placeholderProduct = DB::table('products_products')->where('name', 'LIKE', '%Placeholder%')->first();
        if (!$placeholderProduct) {
            $placeholderProduct = DB::table('products_products')->first();
        }

        // If no product exists at all, create a placeholder product
        if (!$placeholderProduct) {
            // Get required FK IDs for products table
            $uomId = DB::table('unit_of_measures')->first()->id ?? 1;
            $categoryId = DB::table('products_categories')->first()->id ?? 1;

            $productId = DB::table('products_products')->insertGetId([
                'name' => 'Placeholder Material',
                'type' => 'consumable',  // Column is 'type' not 'product_type'
                'service_tracking' => 'no',  // NOT NULL
                'is_favorite' => false,  // NOT NULL
                'uom_id' => $uomId,  // NOT NULL FK
                'uom_po_id' => $uomId,  // NOT NULL FK
                'category_id' => $categoryId,  // NOT NULL FK
                'sales_ok' => true,  // NOT NULL
                'purchase_ok' => true,  // NOT NULL
                'suitable_for_paint' => false,  // NOT NULL
                'suitable_for_stain' => false,  // NOT NULL
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->command->info("   ✓ Created placeholder product (ID: {$productId})");
        } else {
            $productId = $placeholderProduct->id;
        }

        foreach ($this->data['bom'] as $item) {
            $runId = $this->refs[$item['_run_ref']] ?? null;

            DB::table('projects_bom')->insert([
                'product_id' => $productId,  // Required NOT NULL field
                'cabinet_run_id' => $runId,
                'component_name' => $item['component_name'],
                'quantity_required' => $item['quantity_required'],
                'unit_of_measure' => $item['unit_of_measure'],
                'waste_factor_percentage' => $item['waste_factor_percentage'],
                'quantity_with_waste' => $item['quantity_with_waste'],
                'quantity_of_components' => $item['quantity_of_components'] ?? 1,
                'total_sqft_required' => $item['total_sqft_required'] ?? null,
                'board_feet_required' => $item['board_feet_required'] ?? null,
                'unit_cost' => $item['unit_cost'],
                'total_material_cost' => $item['total_material_cost'],
                'grain_direction' => $item['grain_direction'] ?? null,
                'requires_edge_banding' => $item['requires_edge_banding'] ?? false,
                'edge_banding_sides' => $item['edge_banding_sides'] ?? null,
                'edge_banding_lf' => $item['edge_banding_lf'] ?? null,
                'machining_operations' => $item['machining_operations'] ?? null,
                'cnc_notes' => $item['cnc_notes'] ?? null,
                'material_allocated' => $item['material_allocated'] ?? false,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['bom']) . " BOM items");
    }

    /**
     * Seed hardware requirements
     */
    protected function seedHardwareRequirements(): void
    {
        $this->command->info("18. Seeding hardware requirements...");

        // Get placeholder product for hardware items (product_id is NOT NULL with FK)
        // This should exist from seedBom() or we create one
        $placeholderProduct = DB::table('products_products')->where('name', 'LIKE', '%Placeholder%')->first();
        if (!$placeholderProduct) {
            $placeholderProduct = DB::table('products_products')->first();
        }

        if (!$placeholderProduct) {
            // Get required FK IDs for products table
            $uomId = DB::table('unit_of_measures')->first()->id ?? 1;
            $categoryId = DB::table('products_categories')->first()->id ?? 1;

            $productId = DB::table('products_products')->insertGetId([
                'name' => 'Placeholder Hardware',
                'type' => 'consumable',
                'service_tracking' => 'no',
                'is_favorite' => false,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'category_id' => $categoryId,
                'sales_ok' => true,
                'purchase_ok' => true,
                'suitable_for_paint' => false,
                'suitable_for_stain' => false,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        } else {
            $productId = $placeholderProduct->id;
        }

        foreach ($this->data['hardware_requirements'] as $hw) {
            $runId = $this->refs[$hw['_run_ref']] ?? null;

            DB::table('hardware_requirements')->insert([
                'product_id' => $productId,  // Required NOT NULL field
                'cabinet_run_id' => $runId,
                'hardware_type' => $hw['hardware_type'],
                'manufacturer' => $hw['manufacturer'] ?? null,
                'model_number' => $hw['model_number'] ?? null,
                'quantity_required' => $hw['quantity_required'],
                'unit_of_measure' => $hw['unit_of_measure'] ?? 'EA',
                'hinge_type' => $hw['hinge_type'] ?? null,
                'hinge_opening_angle' => $hw['hinge_opening_angle'] ?? null,
                'overlay_dimension_mm' => $hw['overlay_dimension_mm'] ?? null,
                'slide_type' => $hw['slide_type'] ?? null,
                'slide_length_inches' => $hw['slide_length_inches'] ?? null,
                'slide_weight_capacity_lbs' => $hw['slide_weight_capacity_lbs'] ?? null,
                'shelf_pin_type' => $hw['shelf_pin_type'] ?? null,
                'shelf_pin_diameter_mm' => $hw['shelf_pin_diameter_mm'] ?? null,
                'finish' => $hw['finish'] ?? null,
                'unit_cost' => $hw['unit_cost'],
                'total_hardware_cost' => $hw['total_hardware_cost'],
                'installation_notes' => $hw['installation_notes'] ?? null,
                'hardware_allocated' => $hw['hardware_allocated'] ?? false,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['hardware_requirements']) . " hardware items");
    }

    /**
     * Seed milestones
     */
    protected function seedMilestones(): void
    {
        $this->command->info("19. Seeding milestones...");

        foreach ($this->data['milestones'] as $milestone) {
            DB::table('projects_milestones')->insert([
                'project_id' => $this->refs['project'],
                'name' => $milestone['name'],
                'deadline' => $milestone['deadline'],
                'production_stage' => $milestone['production_stage'] ?? null,
                'is_critical' => $milestone['is_critical'] ?? false,
                'description' => $milestone['description'] ?? null,
                'sort_order' => $milestone['sort_order'] ?? 1,
                'is_completed' => $milestone['is_completed'] ?? false,
                'completed_at' => $milestone['completed_at'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['milestones']) . " milestones");
    }

    /**
     * Seed tasks
     */
    protected function seedTasks(): void
    {
        $this->command->info("20. Seeding tasks...");

        foreach ($this->data['tasks'] as $task) {
            $roomId = isset($task['_room_ref']) ? ($this->refs[$task['_room_ref']] ?? null) : null;
            $runId = isset($task['_run_ref']) ? ($this->refs[$task['_run_ref']] ?? null) : null;

            // Get task stage ID
            $stageName = strtolower(str_replace(' ', '_', $task['_stage_name'] ?? 'backlog'));
            $stageId = $this->refs['task_stage_' . $stageName] ?? null;

            $id = DB::table('projects_tasks')->insertGetId([
                'project_id' => $this->refs['project'],
                'room_id' => $roomId,
                'cabinet_run_id' => $runId,
                'stage_id' => $stageId,
                'title' => $task['title'],
                'description' => $task['description'] ?? null,
                'color' => $task['color'] ?? null,
                // NOT NULL fields with defaults
                'priority' => $task['priority'] ?? false,
                'state' => $task['state'] ?? 'pending',
                'is_active' => $task['is_active'] ?? true,
                'is_recurring' => $task['is_recurring'] ?? false,
                'working_hours_open' => $task['working_hours_open'] ?? 0,
                'working_hours_close' => $task['working_hours_close'] ?? 0,
                'allocated_hours' => $task['allocated_hours'] ?? 0,
                'remaining_hours' => $task['remaining_hours'] ?? 0,
                'effective_hours' => $task['effective_hours'] ?? 0,
                'total_hours_spent' => $task['total_hours_spent'] ?? 0,
                'overtime' => $task['overtime'] ?? 0,
                'progress' => $task['progress'] ?? 0,
                'subtask_effective_hours' => $task['subtask_effective_hours'] ?? 0,
                // Nullable fields
                'sort' => $task['sort'] ?? 1,
                'deadline' => $task['deadline'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs['task_' . $task['title']] = $id;
        }
        $this->command->info("   ✓ Created " . count($this->data['tasks']) . " tasks");
    }

    /**
     * Seed subtasks
     */
    protected function seedSubtasks(): void
    {
        $this->command->info("21. Seeding subtasks...");

        foreach ($this->data['subtasks'] as $subtask) {
            $parentId = $this->refs['task_' . $subtask['_parent_task_title']] ?? null;
            $roomId = isset($subtask['_room_ref']) ? ($this->refs[$subtask['_room_ref']] ?? null) : null;
            $runId = isset($subtask['_run_ref']) ? ($this->refs[$subtask['_run_ref']] ?? null) : null;

            DB::table('projects_tasks')->insert([
                'project_id' => $this->refs['project'],
                'parent_id' => $parentId,
                'room_id' => $roomId,
                'cabinet_run_id' => $runId,
                'title' => $subtask['title'],
                'description' => $subtask['description'] ?? null,
                // NOT NULL fields with defaults
                'priority' => $subtask['priority'] ?? false,
                'state' => $subtask['state'] ?? 'pending',
                'is_active' => $subtask['is_active'] ?? true,
                'is_recurring' => $subtask['is_recurring'] ?? false,
                'working_hours_open' => $subtask['working_hours_open'] ?? 0,
                'working_hours_close' => $subtask['working_hours_close'] ?? 0,
                'allocated_hours' => $subtask['allocated_hours'] ?? 0,
                'remaining_hours' => $subtask['remaining_hours'] ?? 0,
                'effective_hours' => $subtask['effective_hours'] ?? 0,
                'total_hours_spent' => $subtask['total_hours_spent'] ?? 0,
                'overtime' => $subtask['overtime'] ?? 0,
                'progress' => $subtask['progress'] ?? 0,
                'subtask_effective_hours' => $subtask['subtask_effective_hours'] ?? 0,
                // Nullable fields
                'sort' => $subtask['sort'] ?? 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['subtasks']) . " subtasks");
    }

    /**
     * Seed sales order
     */
    protected function seedSalesOrder(): void
    {
        $this->command->info("22. Seeding sales order...");

        $order = $this->data['sales_order'];

        // Get required IDs for NOT NULL fields
        $companyId = DB::table('companies')->first()->id ?? 1;
        $currencyId = DB::table('currencies')->where('code', 'USD')->first()->id
                   ?? DB::table('currencies')->first()->id ?? 1;

        $id = DB::table('sales_orders')->insertGetId([
            'company_id' => $companyId,  // Required NOT NULL
            'partner_id' => $this->refs['customer'],
            'partner_invoice_id' => $this->refs['customer'],  // Required NOT NULL - same as partner
            'partner_shipping_id' => $this->refs['customer'], // Required NOT NULL - same as partner
            'currency_id' => $currencyId,  // Required NOT NULL
            'project_id' => $this->refs['project'],
            'name' => $order['name'],
            'state' => $order['state'],
            'date_order' => $order['date_order'],
            'validity_date' => $order['validity_date'],
            'client_order_ref' => $order['client_order_ref'],
            'origin' => $order['origin'],
            'currency_rate' => $order['currency_rate'],
            'amount_untaxed' => $order['amount_untaxed'],
            'amount_tax' => $order['amount_tax'],
            'amount_total' => $order['amount_total'],
            'locked' => $order['locked'],
            'require_signature' => $order['require_signature'],
            'require_payment' => $order['require_payment'],
            'invoice_status' => $order['invoice_status'],
            'delivery_status' => $order['delivery_status'] ?? null,
            'woodworking_order_type' => $order['woodworking_order_type'] ?? null,
            'deposit_percentage' => $order['deposit_percentage'] ?? null,
            'deposit_amount' => $order['deposit_amount'] ?? null,
            'balance_percentage' => $order['balance_percentage'] ?? null,
            'balance_amount' => $order['balance_amount'] ?? null,
            'payment_terms' => $order['payment_terms'] ?? null,
            'project_estimated_value' => $order['project_estimated_value'] ?? null,
            'proposal_status' => $order['proposal_status'] ?? 'draft',
            'proposal_sent_at' => $order['proposal_sent_at'] ?? null,
            'proposal_accepted_at' => $order['proposal_accepted_at'] ?? null,
            'production_authorized' => $order['production_authorized'] ?? false,
            'production_authorized_at' => $order['production_authorized_at'] ?? null,
            'is_change_order' => false,
            'note' => $order['note'] ?? null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->refs['sales_order'] = $id;
        $this->command->info("   ✓ Created sales order: {$order['name']} (ID: {$id})");
    }

    /**
     * Seed sales order lines
     */
    protected function seedSalesOrderLines(): void
    {
        $this->command->info("23. Seeding sales order lines...");

        foreach ($this->data['sales_order_lines'] as $line) {
            DB::table('sales_order_lines')->insert([
                'order_id' => $this->refs['sales_order'],
                'sort' => $line['sort'],
                'name' => $line['name'],
                'product_uom_qty' => $line['product_uom_qty'],
                'product_qty' => $line['product_qty'],
                'price_unit' => $line['price_unit'],
                'discount' => $line['discount'] ?? 0,
                'price_subtotal' => $line['price_subtotal'],
                'price_total' => $line['price_total'],
                'state' => $line['state'],
                'invoice_status' => $line['invoice_status'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['sales_order_lines']) . " order lines");
    }

    /**
     * Seed change orders
     */
    protected function seedChangeOrders(): void
    {
        $this->command->info("24. Seeding change orders...");

        // Get required IDs for NOT NULL fields
        $companyId = DB::table('companies')->first()->id ?? 1;
        $currencyId = DB::table('currencies')->where('code', 'USD')->first()->id
                   ?? DB::table('currencies')->first()->id ?? 1;

        foreach ($this->data['change_orders'] as $co) {
            $id = DB::table('sales_orders')->insertGetId([
                'company_id' => $companyId,  // Required NOT NULL
                'partner_id' => $this->refs['customer'],
                'partner_invoice_id' => $this->refs['customer'],  // Required NOT NULL
                'partner_shipping_id' => $this->refs['customer'], // Required NOT NULL
                'currency_id' => $currencyId,  // Required NOT NULL
                'project_id' => $this->refs['project'],
                'original_order_id' => $this->refs['sales_order'],  // Changed from parent_order_id
                'name' => $co['name'],
                'state' => $co['state'],
                'date_order' => $co['date_order'],
                'is_change_order' => $co['is_change_order'],
                'change_order_description' => $co['change_order_description'] ?? null,
                'amount_untaxed' => $co['amount_untaxed'],
                'amount_total' => $co['amount_total'],
                'proposal_status' => $co['proposal_status'] ?? 'draft',
                'locked' => false,
                'require_signature' => false,
                'require_payment' => false,
                'production_authorized' => false,
                'note' => $co['note'] ?? null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->refs['change_order_' . $co['name']] = $id;
            $this->command->info("   ✓ Created change order: {$co['name']}");
        }
    }

    /**
     * Seed change order lines
     */
    protected function seedChangeOrderLines(): void
    {
        $this->command->info("25. Seeding change order lines...");

        foreach ($this->data['change_order_lines'] as $line) {
            $coId = $this->refs['change_order_' . $line['_change_order_ref']] ?? null;

            DB::table('sales_order_lines')->insert([
                'order_id' => $coId,
                'sort' => $line['sort'],
                'name' => $line['name'],
                'product_uom_qty' => $line['product_uom_qty'],
                'price_unit' => $line['price_unit'],
                'price_subtotal' => $line['price_subtotal'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->command->info("   ✓ Created " . count($this->data['change_order_lines']) . " change order lines");
    }
}
