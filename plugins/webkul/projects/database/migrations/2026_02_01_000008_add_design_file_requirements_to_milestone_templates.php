<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add Rhino and VCarve file requirements to Design stage milestone templates.
     */
    public function up(): void
    {
        // Get milestone template IDs
        $shopDrawingsMilestone = DB::table('projects_milestone_templates')
            ->where('name', 'Shop Drawings Complete')
            ->first();

        $cutListMilestone = DB::table('projects_milestone_templates')
            ->where('name', 'Cut List & Material Takeoff')
            ->first();

        $initialDesignMilestone = DB::table('projects_milestone_templates')
            ->where('name', 'Initial Design Concepts')
            ->first();

        // Add Rhino 3D model requirement to Shop Drawings Complete
        if ($shopDrawingsMilestone) {
            // Check if already exists
            $exists = DB::table('projects_milestone_requirement_templates')
                ->where('milestone_template_id', $shopDrawingsMilestone->id)
                ->where('name', 'Rhino 3D model uploaded')
                ->exists();

            if (!$exists) {
                DB::table('projects_milestone_requirement_templates')->insert([
                    'milestone_template_id' => $shopDrawingsMilestone->id,
                    'name' => 'Rhino 3D model uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'Rhino .3dm files uploaded to CAD folder in Google Drive',
                    'config' => json_encode([
                        'folder' => '02_Design/DWG_Imports',
                        'extensions' => ['3dm', 'dwg', 'dxf'],
                        'min_count' => 1,
                        'document_type' => 'cad_file',
                    ]),
                    'sort_order' => 0,
                    'is_required' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update existing shop drawings requirement if it exists
            DB::table('projects_milestone_requirement_templates')
                ->where('milestone_template_id', $shopDrawingsMilestone->id)
                ->where('name', 'Shop drawings uploaded')
                ->update([
                    'name' => 'Shop drawings PDF uploaded',
                    'config' => json_encode([
                        'folder' => '04_Production/Job Cards/Build_PDFs',
                        'extensions' => 'pdf',
                        'min_count' => 1,
                        'document_type' => 'shop_drawings',
                    ]),
                    'is_required' => false,
                ]);
        }

        // Add VCarve CNC files requirement to Cut List & Material Takeoff
        if ($cutListMilestone) {
            $exists = DB::table('projects_milestone_requirement_templates')
                ->where('milestone_template_id', $cutListMilestone->id)
                ->where('name', 'VCarve CNC files uploaded')
                ->exists();

            if (!$exists) {
                DB::table('projects_milestone_requirement_templates')->insert([
                    'milestone_template_id' => $cutListMilestone->id,
                    'name' => 'VCarve CNC files uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'VCarve .crv files uploaded to CNC folder in Google Drive',
                    'config' => json_encode([
                        'folder' => '04_Production/CNC/VCarve Files',
                        'extensions' => ['crv', 'crv3d'],
                        'min_count' => 1,
                        'document_type' => 'cnc_file',
                    ]),
                    'sort_order' => 0,
                    'is_required' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Add CNC toolpath requirement (optional)
            $existsToolpath = DB::table('projects_milestone_requirement_templates')
                ->where('milestone_template_id', $cutListMilestone->id)
                ->where('name', 'CNC toolpath files generated')
                ->exists();

            if (!$existsToolpath) {
                DB::table('projects_milestone_requirement_templates')->insert([
                    'milestone_template_id' => $cutListMilestone->id,
                    'name' => 'CNC toolpath files generated',
                    'requirement_type' => 'document_upload',
                    'description' => 'G-code toolpath files for CNC machine',
                    'config' => json_encode([
                        'folder' => '04_Production/CNC/ToolPaths',
                        'extensions' => ['sbp', 'nc', 'gcode', 'tap'],
                        'min_count' => 1,
                        'document_type' => 'toolpath',
                    ]),
                    'sort_order' => 1,
                    'is_required' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Update Initial Design Concepts requirement
        if ($initialDesignMilestone) {
            DB::table('projects_milestone_requirement_templates')
                ->where('milestone_template_id', $initialDesignMilestone->id)
                ->where('name', 'Design renderings uploaded')
                ->update([
                    'name' => 'Design renderings or sketches uploaded',
                    'config' => json_encode([
                        'folder' => '02_Design/DWG_Imports',
                        'extensions' => ['3dm', 'dwg', 'dxf', 'pdf', 'png', 'jpg'],
                        'min_count' => 1,
                        'document_type' => 'design_rendering',
                    ]),
                    'is_required' => false,
                ]);
        }

        // Also update existing project milestone requirements (instances)
        $this->updateExistingProjectRequirements();
    }

    /**
     * Update existing project milestone requirements with new configs
     */
    protected function updateExistingProjectRequirements(): void
    {
        // Update Shop Drawings Complete requirements
        $shopDrawingsMilestones = DB::table('projects_milestones')
            ->where('name', 'Shop Drawings Complete')
            ->pluck('id');

        if ($shopDrawingsMilestones->isNotEmpty()) {
            // Add Rhino requirement if missing
            foreach ($shopDrawingsMilestones as $milestoneId) {
                $exists = DB::table('projects_milestone_requirements')
                    ->where('milestone_id', $milestoneId)
                    ->where('name', 'Rhino 3D model uploaded')
                    ->exists();

                if (!$exists) {
                    DB::table('projects_milestone_requirements')->insert([
                        'milestone_id' => $milestoneId,
                        'name' => 'Rhino 3D model uploaded',
                        'requirement_type' => 'document_upload',
                        'description' => 'Rhino .3dm files uploaded to CAD folder in Google Drive',
                        'config' => json_encode([
                            'folder' => '02_Design/DWG_Imports',
                            'extensions' => ['3dm', 'dwg', 'dxf'],
                            'min_count' => 1,
                            'document_type' => 'cad_file',
                        ]),
                        'sort_order' => 0,
                        'is_required' => true,
                        'is_verified' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update existing shop drawings requirement
            DB::table('projects_milestone_requirements')
                ->whereIn('milestone_id', $shopDrawingsMilestones)
                ->where('name', 'Shop drawings uploaded')
                ->update([
                    'name' => 'Shop drawings PDF uploaded',
                    'config' => json_encode([
                        'folder' => '04_Production/Job Cards/Build_PDFs',
                        'extensions' => 'pdf',
                        'min_count' => 1,
                        'document_type' => 'shop_drawings',
                    ]),
                    'is_required' => false,
                ]);
        }

        // Update Cut List & Material Takeoff requirements
        $cutListMilestones = DB::table('projects_milestones')
            ->where('name', 'Cut List & Material Takeoff')
            ->pluck('id');

        if ($cutListMilestones->isNotEmpty()) {
            foreach ($cutListMilestones as $milestoneId) {
                // Add VCarve requirement if missing
                $exists = DB::table('projects_milestone_requirements')
                    ->where('milestone_id', $milestoneId)
                    ->where('name', 'VCarve CNC files uploaded')
                    ->exists();

                if (!$exists) {
                    DB::table('projects_milestone_requirements')->insert([
                        'milestone_id' => $milestoneId,
                        'name' => 'VCarve CNC files uploaded',
                        'requirement_type' => 'document_upload',
                        'description' => 'VCarve .crv files uploaded to CNC folder in Google Drive',
                        'config' => json_encode([
                            'folder' => '04_Production/CNC/VCarve Files',
                            'extensions' => ['crv', 'crv3d'],
                            'min_count' => 1,
                            'document_type' => 'cnc_file',
                        ]),
                        'sort_order' => 0,
                        'is_required' => true,
                        'is_verified' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Add toolpath requirement if missing
                $existsToolpath = DB::table('projects_milestone_requirements')
                    ->where('milestone_id', $milestoneId)
                    ->where('name', 'CNC toolpath files generated')
                    ->exists();

                if (!$existsToolpath) {
                    DB::table('projects_milestone_requirements')->insert([
                        'milestone_id' => $milestoneId,
                        'name' => 'CNC toolpath files generated',
                        'requirement_type' => 'document_upload',
                        'description' => 'G-code toolpath files for CNC machine',
                        'config' => json_encode([
                            'folder' => '04_Production/CNC/ToolPaths',
                            'extensions' => ['sbp', 'nc', 'gcode', 'tap'],
                            'min_count' => 1,
                            'document_type' => 'toolpath',
                        ]),
                        'sort_order' => 1,
                        'is_required' => false,
                        'is_verified' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Update Initial Design Concepts requirements
        $initialDesignMilestones = DB::table('projects_milestones')
            ->where('name', 'Initial Design Concepts')
            ->pluck('id');

        if ($initialDesignMilestones->isNotEmpty()) {
            DB::table('projects_milestone_requirements')
                ->whereIn('milestone_id', $initialDesignMilestones)
                ->where('name', 'Design renderings uploaded')
                ->update([
                    'name' => 'Design renderings or sketches uploaded',
                    'config' => json_encode([
                        'folder' => '02_Design/DWG_Imports',
                        'extensions' => ['3dm', 'dwg', 'dxf', 'pdf', 'png', 'jpg'],
                        'min_count' => 1,
                        'document_type' => 'design_rendering',
                    ]),
                    'is_required' => false,
                ]);
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Remove the added requirements
        DB::table('projects_milestone_requirement_templates')
            ->where('name', 'Rhino 3D model uploaded')
            ->delete();

        DB::table('projects_milestone_requirement_templates')
            ->where('name', 'VCarve CNC files uploaded')
            ->delete();

        DB::table('projects_milestone_requirement_templates')
            ->where('name', 'CNC toolpath files generated')
            ->delete();

        DB::table('projects_milestone_requirements')
            ->where('name', 'Rhino 3D model uploaded')
            ->delete();

        DB::table('projects_milestone_requirements')
            ->where('name', 'VCarve CNC files uploaded')
            ->delete();

        DB::table('projects_milestone_requirements')
            ->where('name', 'CNC toolpath files generated')
            ->delete();
    }
};
