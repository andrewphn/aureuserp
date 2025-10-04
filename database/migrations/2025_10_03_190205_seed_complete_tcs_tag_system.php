<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Complete TCS Woodwork Tag System - 119 Total Tags
     * Color-coded by phase families for visual clarity
     *
     * PHASE COLOR FAMILIES:
     * - Discovery (Blue): Trust, planning, initial engagement
     * - Design (Purple): Creativity, problem-solving, innovation
     * - Sourcing (Teal): Precision, procurement, coordination
     * - Production (Orange/Red): Energy, action, execution
     * - Delivery (Green): Completion, success, achievement
     * - Utility (Cross-phase): Priority, Health, Risk, Complexity
     */
    public function up(): void
    {
        $creatorId = 1;
        $timestamp = now();

        // ============================================================================
        // UTILITY TAGS (Cross-Phase) - 32 tags
        // These apply across all phases with universal color meanings
        // ============================================================================

        // Priority Tags (5) - Traffic light + urgency colors
        $priorityTags = [
            ['name' => 'Critical Priority', 'color' => '#DC2626', 'type' => 'priority'],      // Red - Urgent danger
            ['name' => 'High Priority', 'color' => '#F97316', 'type' => 'priority'],          // Orange - Important action
            ['name' => 'Standard Priority', 'color' => '#3B82F6', 'type' => 'priority'],      // Blue - Normal flow
            ['name' => 'Low Priority', 'color' => '#10B981', 'type' => 'priority'],           // Green - Calm proceed
            ['name' => 'On Hold', 'color' => '#6B7280', 'type' => 'priority'],                // Gray - Inactive/paused
        ];

        // Health/Status Tags (6) - RAG system (Red-Amber-Green)
        $healthTags = [
            ['name' => 'On Track', 'color' => '#10B981', 'type' => 'health'],                 // Green - Healthy
            ['name' => 'At Risk', 'color' => '#F59E0B', 'type' => 'health'],                  // Amber - Warning
            ['name' => 'Red Flag', 'color' => '#DC2626', 'type' => 'health'],                 // Red - Critical
            ['name' => 'Blocked', 'color' => '#8B5CF6', 'type' => 'health'],                  // Purple - Different workflow
            ['name' => 'Budget Watch', 'color' => '#F97316', 'type' => 'health'],             // Orange - Financial caution
            ['name' => 'Schedule Watch', 'color' => '#EAB308', 'type' => 'health'],           // Yellow - Time warning
        ];

        // Risk Tags (5) - Warm colors for attention
        $riskTags = [
            ['name' => 'Design Risk', 'color' => '#EC4899', 'type' => 'risk'],                // Pink - Creative risk
            ['name' => 'Material Risk', 'color' => '#92400E', 'type' => 'risk'],              // Brown - Physical risk
            ['name' => 'Coordination Risk', 'color' => '#3B82F6', 'type' => 'risk'],          // Blue - Communication
            ['name' => 'Payment Risk', 'color' => '#DC2626', 'type' => 'risk'],               // Red - Financial danger
            ['name' => 'Capacity Risk', 'color' => '#7C3AED', 'type' => 'risk'],              // Purple - Resource constraint
        ];

        // Complexity Tags (3) - Intensity gradient
        $complexityTags = [
            ['name' => 'Level 1-2: Standard', 'color' => '#10B981', 'type' => 'complexity'],  // Green - Simple/safe
            ['name' => 'Level 3: Advanced', 'color' => '#F59E0B', 'type' => 'complexity'],    // Amber - Moderate challenge
            ['name' => 'Level 4: Master', 'color' => '#DC2626', 'type' => 'complexity'],      // Red - High difficulty
        ];

        // Work Scope Tags (13) - TCS product categories with natural colors
        $workScopeTags = [
            // Cabinet Work - Brown gradient (wood tones)
            ['name' => 'Cabinet Work - Level 1', 'color' => '#D4A574', 'type' => 'work_scope'],  // Light oak
            ['name' => 'Cabinet Work - Level 2', 'color' => '#B8860B', 'type' => 'work_scope'],  // Dark goldenrod
            ['name' => 'Cabinet Work - Level 3', 'color' => '#8B4513', 'type' => 'work_scope'],  // Saddle brown
            ['name' => 'Cabinet Work - Level 4', 'color' => '#654321', 'type' => 'work_scope'],  // Dark wood

            // Material Finishes - Purple family (transformation/finishing)
            ['name' => 'Paint Grade', 'color' => '#A78BFA', 'type' => 'work_scope'],             // Light purple
            ['name' => 'Stain Grade', 'color' => '#8B5CF6', 'type' => 'work_scope'],             // Medium purple
            ['name' => 'Premium Grade', 'color' => '#7C3AED', 'type' => 'work_scope'],           // Deep purple
            ['name' => 'Custom/Exotic', 'color' => '#6D28D9', 'type' => 'work_scope'],           // Rich purple

            // Other Products - Distinct colors
            ['name' => 'Closet Systems', 'color' => '#14B8A6', 'type' => 'work_scope'],          // Teal
            ['name' => 'Floating Shelves', 'color' => '#06B6D4', 'type' => 'work_scope'],        // Cyan
            ['name' => 'Trim & Millwork', 'color' => '#0EA5E9', 'type' => 'work_scope'],         // Sky blue
            ['name' => 'Finishing Services', 'color' => '#8B5CF6', 'type' => 'work_scope'],      // Purple
            ['name' => 'Service/Repair', 'color' => '#6B7280', 'type' => 'work_scope'],          // Gray - maintenance
        ];

        // ============================================================================
        // PHASE 1: DISCOVERY (Blue Family) - 10 tags
        // Awareness, Contact, Intake, Qualify
        // Blue psychology: Trust, intelligence, calm, professional
        // ============================================================================
        $discoveryTags = [
            // Awareness & Contact (3)
            ['name' => 'Initial Inquiry', 'color' => '#3B82F6', 'type' => 'phase_discovery'],
            ['name' => 'Website Lead', 'color' => '#60A5FA', 'type' => 'phase_discovery'],
            ['name' => 'Referral Lead', 'color' => '#93C5FD', 'type' => 'phase_discovery'],

            // Intake & Qualify (4)
            ['name' => 'Pre-Qualified', 'color' => '#2563EB', 'type' => 'phase_discovery'],
            ['name' => 'Info Gathering', 'color' => '#3B82F6', 'type' => 'phase_discovery'],
            ['name' => 'Not a Fit', 'color' => '#1E3A8A', 'type' => 'phase_discovery'],
            ['name' => 'Needs Follow-up', 'color' => '#60A5FA', 'type' => 'phase_discovery'],

            // Bid/Proposal (3)
            ['name' => 'Estimating', 'color' => '#1D4ED8', 'type' => 'phase_discovery'],
            ['name' => 'Bid Submitted', 'color' => '#2563EB', 'type' => 'phase_discovery'],
            ['name' => 'Awaiting Decision', 'color' => '#3B82F6', 'type' => 'phase_discovery'],
        ];

        // ============================================================================
        // PHASE 2: DESIGN (Purple Family) - 13 tags
        // Agreement, Contract, Kickoff, Design Development
        // Purple psychology: Creativity, innovation, quality, sophistication
        // ============================================================================
        $designTags = [
            // Agreement & Contract (3)
            ['name' => 'Contract Draft', 'color' => '#8B5CF6', 'type' => 'phase_design'],
            ['name' => 'Under Review', 'color' => '#A78BFA', 'type' => 'phase_design'],
            ['name' => 'Signed & Executed', 'color' => '#7C3AED', 'type' => 'phase_design'],

            // Kickoff & Deposit (4)
            ['name' => '50% Deposit Received', 'color' => '#6D28D9', 'type' => 'phase_design'],
            ['name' => 'Specs Finalized', 'color' => '#8B5CF6', 'type' => 'phase_design'],
            ['name' => 'Schedule Set', 'color' => '#A78BFA', 'type' => 'phase_design'],
            ['name' => 'Pre-Production Review', 'color' => '#7C3AED', 'type' => 'phase_design'],

            // Design & Development (6)
            ['name' => 'Conceptual Design', 'color' => '#9333EA', 'type' => 'phase_design'],
            ['name' => 'Shop Drawings', 'color' => '#8B5CF6', 'type' => 'phase_design'],
            ['name' => 'Engineering', 'color' => '#A78BFA', 'type' => 'phase_design'],
            ['name' => 'Client Approval', 'color' => '#C4B5FD', 'type' => 'phase_design'],
            ['name' => 'Buildability Review', 'color' => '#7C3AED', 'type' => 'phase_design'],
            ['name' => 'Value Engineering', 'color' => '#6D28D9', 'type' => 'phase_design'],
        ];

        // ============================================================================
        // PHASE 3: SOURCING (Teal Family) - 24 tags
        // Material specification, procurement, inspection
        // Teal psychology: Precision, clarity, balance, coordination
        // ============================================================================
        $sourcingTags = [
            // Material Design Stage (6)
            ['name' => 'Material Spec Pending', 'color' => '#14B8A6', 'type' => 'phase_sourcing'],
            ['name' => 'Material Specified', 'color' => '#2DD4BF', 'type' => 'phase_sourcing'],
            ['name' => 'Material Quoted', 'color' => '#5EEAD4', 'type' => 'phase_sourcing'],
            ['name' => 'Material Approved', 'color' => '#99F6E4', 'type' => 'phase_sourcing'],
            ['name' => 'Material Samples Ordered', 'color' => '#14B8A6', 'type' => 'phase_sourcing'],
            ['name' => 'Material Review', 'color' => '#0D9488', 'type' => 'phase_sourcing'],

            // Procurement Stage (7)
            ['name' => 'Material Ordered', 'color' => '#14B8A6', 'type' => 'phase_sourcing'],
            ['name' => 'Material In Transit', 'color' => '#2DD4BF', 'type' => 'phase_sourcing'],
            ['name' => 'Material Delayed', 'color' => '#F97316', 'type' => 'phase_sourcing'],      // Orange warning
            ['name' => 'Material Received', 'color' => '#10B981', 'type' => 'phase_sourcing'],     // Green success
            ['name' => 'Material Inspection', 'color' => '#14B8A6', 'type' => 'phase_sourcing'],
            ['name' => 'Material Rejected', 'color' => '#DC2626', 'type' => 'phase_sourcing'],     // Red failure
            ['name' => 'Material Staged', 'color' => '#0D9488', 'type' => 'phase_sourcing'],

            // Material Types (8)
            ['name' => 'Hardwood - Domestic', 'color' => '#92400E', 'type' => 'phase_sourcing'],
            ['name' => 'Hardwood - Exotic', 'color' => '#78350F', 'type' => 'phase_sourcing'],
            ['name' => 'Paint Grade Materials', 'color' => '#A78BFA', 'type' => 'phase_sourcing'],
            ['name' => 'Stain Grade Materials', 'color' => '#8B5CF6', 'type' => 'phase_sourcing'],
            ['name' => 'Premium Materials', 'color' => '#7C3AED', 'type' => 'phase_sourcing'],
            ['name' => 'Sheet Materials', 'color' => '#6B7280', 'type' => 'phase_sourcing'],
            ['name' => 'Composite Materials', 'color' => '#9CA3AF', 'type' => 'phase_sourcing'],
            ['name' => 'Veneer', 'color' => '#D1D5DB', 'type' => 'phase_sourcing'],

            // Material Issues (3)
            ['name' => 'Material Shortage', 'color' => '#DC2626', 'type' => 'phase_sourcing'],
            ['name' => 'Material Substitution', 'color' => '#F59E0B', 'type' => 'phase_sourcing'],
            ['name' => 'Material Defect', 'color' => '#EF4444', 'type' => 'phase_sourcing'],
        ];

        // ============================================================================
        // PHASE 4: PRODUCTION (Orange/Red Family) - 18 tags
        // Manufacturing, assembly, finishing, QC
        // Orange/Red psychology: Energy, action, passion, urgency, movement
        // ============================================================================
        $productionTags = [
            // Production Stages (8)
            ['name' => 'Material Prep', 'color' => '#F97316', 'type' => 'phase_production'],
            ['name' => 'Rough Mill', 'color' => '#FB923C', 'type' => 'phase_production'],
            ['name' => 'Primary Assembly', 'color' => '#FDBA74', 'type' => 'phase_production'],
            ['name' => 'Finishing Prep', 'color' => '#FED7AA', 'type' => 'phase_production'],
            ['name' => 'Finish Application', 'color' => '#EA580C', 'type' => 'phase_production'],
            ['name' => 'Hardware Install', 'color' => '#C2410C', 'type' => 'phase_production'],
            ['name' => 'Final Assembly', 'color' => '#9A3412', 'type' => 'phase_production'],
            ['name' => 'Production Complete', 'color' => '#7C2D12', 'type' => 'phase_production'],

            // Material Production Types (5)
            ['name' => 'Rough Lumber', 'color' => '#92400E', 'type' => 'phase_production'],
            ['name' => 'Dimensioned Stock', 'color' => '#78350F', 'type' => 'phase_production'],
            ['name' => 'Finish Material', 'color' => '#8B5CF6', 'type' => 'phase_production'],
            ['name' => 'Hardware/Fasteners', 'color' => '#6B7280', 'type' => 'phase_production'],
            ['name' => 'Sheet Goods', 'color' => '#A16207', 'type' => 'phase_production'],

            // QC & Finishing (5)
            ['name' => 'Quality Inspection', 'color' => '#DC2626', 'type' => 'phase_production'],
            ['name' => 'Touch-up Required', 'color' => '#F97316', 'type' => 'phase_production'],
            ['name' => 'Final Finish', 'color' => '#FB923C', 'type' => 'phase_production'],
            ['name' => 'QC Passed', 'color' => '#10B981', 'type' => 'phase_production'],          // Green success
            ['name' => 'Punch List', 'color' => '#EAB308', 'type' => 'phase_production'],         // Yellow attention
        ];

        // ============================================================================
        // PHASE 5: DELIVERY (Green Family) - 14 tags
        // Delivery, installation, acceptance, payment, post-project
        // Green psychology: Success, completion, growth, achievement
        // ============================================================================
        $deliveryTags = [
            // Delivery & Install (6)
            ['name' => 'Delivery Scheduled', 'color' => '#10B981', 'type' => 'phase_delivery'],
            ['name' => 'Delivered', 'color' => '#34D399', 'type' => 'phase_delivery'],
            ['name' => 'Installation In Progress', 'color' => '#6EE7B7', 'type' => 'phase_delivery'],
            ['name' => 'Hardware Adjustment', 'color' => '#A7F3D0', 'type' => 'phase_delivery'],
            ['name' => 'Site Coordination', 'color' => '#059669', 'type' => 'phase_delivery'],
            ['name' => 'Install Complete', 'color' => '#047857', 'type' => 'phase_delivery'],

            // Acceptance & Payment (4)
            ['name' => 'Client Walkthrough', 'color' => '#10B981', 'type' => 'phase_delivery'],
            ['name' => 'Punch List Items', 'color' => '#34D399', 'type' => 'phase_delivery'],
            ['name' => 'Final Payment Due', 'color' => '#059669', 'type' => 'phase_delivery'],
            ['name' => 'Project Closed', 'color' => '#047857', 'type' => 'phase_delivery'],

            // Post-Project (4) - Lighter greens for follow-up
            ['name' => 'Warranty Service', 'color' => '#6EE7B7', 'type' => 'phase_delivery'],
            ['name' => 'Portfolio Photos', 'color' => '#A7F3D0', 'type' => 'phase_delivery'],
            ['name' => 'Testimonial Received', 'color' => '#D1FAE5', 'type' => 'phase_delivery'],
            ['name' => 'Follow-up Opportunity', 'color' => '#10B981', 'type' => 'phase_delivery'],
        ];

        // ============================================================================
        // SPECIAL STATUS TAGS (Gray - Neutral) - 8 tags
        // Change orders, revisions, issues - separate from phase flow
        // Gray psychology: Neutral, professional, deviation from normal flow
        // ============================================================================
        $specialStatusTags = [
            ['name' => 'Change Request', 'color' => '#6B7280', 'type' => 'special_status'],
            ['name' => 'Pricing Change Order', 'color' => '#9CA3AF', 'type' => 'special_status'],
            ['name' => 'Change Approved', 'color' => '#10B981', 'type' => 'special_status'],      // Green approved
            ['name' => 'Change Declined', 'color' => '#DC2626', 'type' => 'special_status'],      // Red declined
            ['name' => 'Revision Requested', 'color' => '#F59E0B', 'type' => 'special_status'],   // Amber revision
            ['name' => 'Color Match Issue', 'color' => '#EC4899', 'type' => 'special_status'],    // Pink issue
            ['name' => 'Lessons Learned', 'color' => '#6B7280', 'type' => 'special_status'],
            ['name' => 'Material Selection', 'color' => '#8B5CF6', 'type' => 'special_status'],   // Purple creative
        ];

        // ============================================================================
        // INSERT ALL TAGS
        // ============================================================================

        $allTags = array_merge(
            $priorityTags,
            $healthTags,
            $riskTags,
            $complexityTags,
            $workScopeTags,
            $discoveryTags,
            $designTags,
            $sourcingTags,
            $productionTags,
            $deliveryTags,
            $specialStatusTags
        );

        foreach ($allTags as $tag) {
            $exists = DB::table('projects_tags')
                ->where('name', $tag['name'])
                ->exists();

            if (!$exists) {
                DB::table('projects_tags')->insert([
                    'name' => $tag['name'],
                    'color' => $tag['color'],
                    'type' => $tag['type'],
                    'creator_id' => $creatorId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tagNames = [
            // Utility
            'Critical Priority', 'High Priority', 'Standard Priority', 'Low Priority', 'On Hold',
            'On Track', 'At Risk', 'Red Flag', 'Blocked', 'Budget Watch', 'Schedule Watch',
            'Design Risk', 'Material Risk', 'Coordination Risk', 'Payment Risk', 'Capacity Risk',
            'Level 1-2: Standard', 'Level 3: Advanced', 'Level 4: Master',
            'Cabinet Work - Level 1', 'Cabinet Work - Level 2', 'Cabinet Work - Level 3', 'Cabinet Work - Level 4',
            'Paint Grade', 'Stain Grade', 'Premium Grade', 'Custom/Exotic',
            'Closet Systems', 'Floating Shelves', 'Trim & Millwork', 'Finishing Services', 'Service/Repair',

            // Discovery
            'Initial Inquiry', 'Website Lead', 'Referral Lead',
            'Pre-Qualified', 'Info Gathering', 'Not a Fit', 'Needs Follow-up',
            'Estimating', 'Bid Submitted', 'Awaiting Decision',

            // Design
            'Contract Draft', 'Under Review', 'Signed & Executed',
            '50% Deposit Received', 'Specs Finalized', 'Schedule Set', 'Pre-Production Review',
            'Conceptual Design', 'Shop Drawings', 'Engineering', 'Client Approval', 'Buildability Review', 'Value Engineering',

            // Sourcing
            'Material Spec Pending', 'Material Specified', 'Material Quoted', 'Material Approved', 'Material Samples Ordered', 'Material Review',
            'Material Ordered', 'Material In Transit', 'Material Delayed', 'Material Received', 'Material Inspection', 'Material Rejected', 'Material Staged',
            'Hardwood - Domestic', 'Hardwood - Exotic', 'Paint Grade Materials', 'Stain Grade Materials', 'Premium Materials', 'Sheet Materials', 'Composite Materials', 'Veneer',
            'Material Shortage', 'Material Substitution', 'Material Defect',

            // Production
            'Material Prep', 'Rough Mill', 'Primary Assembly', 'Finishing Prep', 'Finish Application', 'Hardware Install', 'Final Assembly', 'Production Complete',
            'Rough Lumber', 'Dimensioned Stock', 'Finish Material', 'Hardware/Fasteners', 'Sheet Goods',
            'Quality Inspection', 'Touch-up Required', 'Final Finish', 'QC Passed', 'Punch List',

            // Delivery
            'Delivery Scheduled', 'Delivered', 'Installation In Progress', 'Hardware Adjustment', 'Site Coordination', 'Install Complete',
            'Client Walkthrough', 'Punch List Items', 'Final Payment Due', 'Project Closed',
            'Warranty Service', 'Portfolio Photos', 'Testimonial Received', 'Follow-up Opportunity',

            // Special Status
            'Change Request', 'Pricing Change Order', 'Change Approved', 'Change Declined', 'Revision Requested', 'Color Match Issue', 'Lessons Learned', 'Material Selection',
        ];

        DB::table('projects_tags')
            ->whereIn('name', $tagNames)
            ->delete();
    }
};
