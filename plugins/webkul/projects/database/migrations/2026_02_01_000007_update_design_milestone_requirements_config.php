<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update Design stage milestone requirements to check Google Drive for files.
     * Adds specific folder paths and file extensions for Rhino and VCarve files.
     */
    public function up(): void
    {
        // Update "Shop Drawings Complete" milestone requirements
        $this->updateRequirementConfig(
            'Rhino 3D model uploaded',
            'Shop Drawings Complete',
            [
                'folder' => '02_Design/DWG_Imports',
                'extensions' => ['3dm', 'dwg', 'dxf'],
                'min_count' => 1,
                'document_type' => 'cad_file',
            ]
        );

        // Add shop drawings requirement if it was renamed
        $this->updateRequirementConfig(
            'Shop drawings uploaded',
            'Shop Drawings Complete',
            [
                'folder' => '02_Design/DWG_Imports',
                'extensions' => ['3dm', 'dwg', 'dxf'],
                'min_count' => 1,
                'document_type' => 'cad_file',
            ]
        );

        // Update "Cut List & Material Takeoff" milestone requirements
        $this->updateRequirementConfig(
            'VCarve CNC files uploaded',
            'Cut List & Material Takeoff',
            [
                'folder' => '04_Production/CNC/VCarve Files',
                'extensions' => ['crv', 'crv3d'],
                'min_count' => 1,
                'document_type' => 'cnc_file',
            ]
        );

        // Update "Initial Design Concepts" requirements
        $this->updateRequirementConfig(
            'Design renderings uploaded',
            'Initial Design Concepts',
            [
                'folder' => '02_Design/DWG_Imports',
                'extensions' => ['3dm', 'dwg', 'dxf', 'pdf', 'png', 'jpg'],
                'min_count' => 1,
                'document_type' => 'design_rendering',
            ]
        );
    }

    /**
     * Update a requirement template's config by name
     */
    protected function updateRequirementConfig(string $requirementName, string $milestoneName, array $config): void
    {
        // Get milestone template ID
        $milestone = DB::table('projects_milestone_templates')
            ->where('name', $milestoneName)
            ->first();

        if (!$milestone) {
            return;
        }

        // Update template requirement
        DB::table('projects_milestone_requirement_templates')
            ->where('milestone_template_id', $milestone->id)
            ->where('name', $requirementName)
            ->update(['config' => json_encode($config)]);

        // Update existing project milestone requirements (instances)
        $milestoneIds = DB::table('projects_milestones')
            ->where('name', $milestoneName)
            ->pluck('id');

        if ($milestoneIds->isNotEmpty()) {
            DB::table('projects_milestone_requirements')
                ->whereIn('milestone_id', $milestoneIds)
                ->where('name', $requirementName)
                ->update(['config' => json_encode($config)]);
        }
    }

    /**
     * Reverse the migrations - restore to manual verification
     */
    public function down(): void
    {
        // Revert to manual verification configs
        $this->updateRequirementConfig(
            'Shop drawings uploaded',
            'Shop Drawings Complete',
            ['document_type' => 'shop_drawings', 'folder' => 'Shop Drawings']
        );

        $this->updateRequirementConfig(
            'Design renderings uploaded',
            'Initial Design Concepts',
            ['document_type' => 'design_rendering', 'folder' => 'Designs']
        );
    }
};
