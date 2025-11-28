<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix phase tag colors to match their parent project stage colors:
     * - Sourcing: Teal → Amber (#F59E0B family)
     * - Production: Orange → Green (#10B981 family)
     * - Delivery: Green → Teal (#14B8A6 family)
     */
    public function up(): void
    {
        // Sourcing Phase Tags - Change to Amber family (matching Sourcing stage #F59E0B)
        $sourcingTags = [
            // Core sourcing workflow
            'Material Spec Pending' => '#F59E0B',
            'Material Ordered' => '#F59E0B',
            'Material Received' => '#FBBF24',
            'Material Approved' => '#FCD34D',
            'Material Rejected' => '#F59E0B',

            // Material types
            'Lumber' => '#F59E0B',
            'Hardwood' => '#FBBF24',
            'Plywood' => '#F59E0B',
            'MDF' => '#FBBF24',
            'Veneer' => '#F59E0B',
            'Hardware' => '#FBBF24',
            'Finish Materials' => '#F59E0B',
            'Adhesives' => '#FBBF24',

            // Material issues
            'Material Shortage' => '#DC2626',
            'Wrong Material Delivered' => '#EF4444',
            'Material Damage' => '#F87171',
            'Material Back-Ordered' => '#DC2626',

            // Additional sourcing
            'Vendor Quote Pending' => '#F59E0B',
            'PO Issued' => '#FBBF24',
            'Awaiting Delivery' => '#F59E0B',
            'In Transit' => '#FBBF24',
            'Quality Check Required' => '#F59E0B',
            'Return to Vendor' => '#DC2626',
        ];

        // Production Phase Tags - Change to Green family (matching Production stage #10B981)
        $productionTags = [
            'Material Prep' => '#10B981',
            'Rough Mill' => '#34D399',
            'Cut to Size' => '#10B981',
            'Joinery' => '#34D399',
            'Assembly' => '#10B981',
            'Sanding' => '#34D399',
            'Finish Prep' => '#10B981',
            'Staining' => '#34D399',
            'Sealing' => '#10B981',
            'Topcoat' => '#34D399',
            'Drying' => '#10B981',
            'Final Assembly' => '#34D399',
            'QC Inspection' => '#10B981',
            'Touch-ups Required' => '#F59E0B',
            'Rework Needed' => '#EF4444',
            'Production Hold' => '#DC2626',
            'Rush Production' => '#EF4444',
            'Production Complete' => '#10B981',
        ];

        // Delivery Phase Tags - Change to Teal family (matching Delivery stage #14B8A6)
        $deliveryTags = [
            'Delivery Scheduled' => '#14B8A6',
            'Delivered' => '#5EEAD4',
            'Installation Scheduled' => '#14B8A6',
            'Installation In Progress' => '#2DD4BF',
            'Installation Complete' => '#5EEAD4',
            'Customer Walkthrough' => '#14B8A6',
            'Punch List Created' => '#F59E0B',
            'Punch List Complete' => '#5EEAD4',
            'Final Payment Pending' => '#F59E0B',
            'Final Payment Received' => '#5EEAD4',
            'Warranty Active' => '#14B8A6',
            'Project Closed' => '#5EEAD4',
            'Delivery Issue' => '#EF4444',
            'Installation Delayed' => '#F59E0B',
        ];

        // Update Sourcing tags
        foreach ($sourcingTags as $name => $color) {
            DB::table('projects_tags')
                ->where('name', $name)
                ->where('type', 'phase_sourcing')
                ->update(['color' => $color]);
        }

        // Update Production tags
        foreach ($productionTags as $name => $color) {
            DB::table('projects_tags')
                ->where('name', $name)
                ->where('type', 'phase_production')
                ->update(['color' => $color]);
        }

        // Update Delivery tags
        foreach ($deliveryTags as $name => $color) {
            DB::table('projects_tags')
                ->where('name', $name)
                ->where('type', 'phase_delivery')
                ->update(['color' => $color]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original colors

        // Revert Sourcing tags to Teal
        DB::table('projects_tags')
            ->where('type', 'phase_sourcing')
            ->update(['color' => '#14B8A6']);

        // Revert Production tags to Orange
        DB::table('projects_tags')
            ->where('type', 'phase_production')
            ->update(['color' => '#F97316']);

        // Revert Delivery tags to Green
        DB::table('projects_tags')
            ->where('type', 'phase_delivery')
            ->update(['color' => '#10B981']);
    }
};
