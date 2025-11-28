<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
     * Seeds comprehensive project tagging system for TCS Woodwork.
     * Based on: /docs/project-tagging-system-recommendations.md
     *
     * Categories:
     * 1. Lifecycle Phase (12) - Already created
     * 2. Priority/Urgency (5) - Phase 1
     * 3. Project Health (6) - Phase 1
     * 4. Risk/Issue (5) - Phase 1
     * 5. Work Scope/Type (7) - Phase 2
     * 6. Quality/Complexity (3) - Phase 2
     */
    public function up(): void
    {
        // Get a system user ID for creator (default to ID 1 or first user)
        $creatorId = DB::table('users')->first()->id ?? null;

        $now = now();

        // ==================================================================
        // PHASE 1: PRIORITY/URGENCY TAGS (5)
        // ==================================================================
        // Purpose: Address Bryan's need to filter noise and focus on critical items

        $priorityTags = [
            [
                'name' => 'Critical Priority',
                'color' => '#DC2626',
                'type' => 'priority',
                'description' => 'Revenue at risk, safety issues, client escalations - immediate action required',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'High Priority',
                'color' => '#EA580C',
                'type' => 'priority',
                'description' => 'Contractual deadlines, major milestones approaching - complete within 48 hours',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Standard Priority',
                'color' => '#EAB308',
                'type' => 'priority',
                'description' => 'Normal workflow, no special urgency - standard queue processing',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Low Priority',
                'color' => '#94A3B8',
                'type' => 'priority',
                'description' => 'Nice-to-have, flexible timeline - complete when capacity allows',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'On Hold',
                'color' => '#475569',
                'type' => 'priority',
                'description' => 'Waiting on client/vendor, paused projects - no active work until unblocked',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ==================================================================
        // PHASE 1: PROJECT HEALTH TAGS (6)
        // ==================================================================
        // Purpose: Early warning system for Bryan; proactive problem-solving for David

        $healthTags = [
            [
                'name' => 'On Track',
                'color' => '#16A34A',
                'type' => 'health',
                'description' => 'Meeting milestones, budget healthy, no blockers - continue monitoring',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'At Risk',
                'color' => '#CA8A04',
                'type' => 'health',
                'description' => 'Minor delays (<1 week), budget variance <10%, solvable issues - PM attention needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Red Flag',
                'color' => '#DC2626',
                'type' => 'health',
                'description' => 'Major delays (>1 week), budget overrun >10%, critical issues - immediate escalation',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Blocked',
                'color' => '#9333EA',
                'type' => 'health',
                'description' => 'Waiting on approvals, materials, client decisions - daily follow-up required',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Budget Watch',
                'color' => '#EA580C',
                'type' => 'health',
                'description' => 'Budget variance 5-10%, trending toward overrun - cost review meeting needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Schedule Watch',
                'color' => '#F59E0B',
                'type' => 'health',
                'description' => 'Deadline in <2 weeks, dependencies at risk - resource reallocation needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ==================================================================
        // PHASE 1: RISK/ISSUE TAGS (5)
        // ==================================================================
        // Purpose: Proactive tracking of common problems; prevent issues from becoming crises

        $riskTags = [
            [
                'name' => 'Design Risk',
                'color' => '#DB2777',
                'type' => 'risk',
                'description' => 'Complex/novel designs, unclear specs, multiple revisions - extra design review needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Material Risk',
                'color' => '#A16207',
                'type' => 'risk',
                'description' => 'Long lead times, availability issues, quality concerns - early ordering needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Coordination Risk',
                'color' => '#2563EB',
                'type' => 'risk',
                'description' => 'Multiple stakeholders, GC dependencies, designer conflicts - communication plan needed',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Payment Risk',
                'color' => '#DC2626',
                'type' => 'risk',
                'description' => 'Deposit delays, credit concerns, disputed change orders - strict payment milestones',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Capacity Risk',
                'color' => '#4F46E5',
                'type' => 'risk',
                'description' => 'Tight timeline, overlapping projects, specialty skills needed - resource leveling required',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ==================================================================
        // PHASE 2: WORK SCOPE/TYPE TAGS (7)
        // ==================================================================
        // Purpose: Complement project_type field with specific work classifications

        $workScopeTags = [
            [
                'name' => 'Custom Cabinetry',
                'color' => '#92400E',
                'type' => 'work_scope',
                'description' => 'Built-in cabinets, kitchens, vanities - shop capacity planning',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Architectural Millwork',
                'color' => '#1E3A8A',
                'type' => 'work_scope',
                'description' => 'Wainscoting, crown molding, trim packages - finish scheduling',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Custom Furniture',
                'color' => '#78350F',
                'type' => 'work_scope',
                'description' => 'Tables, chairs, built-in seating - different workflow/timeline',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Doors & Casework',
                'color' => '#115E59',
                'type' => 'work_scope',
                'description' => 'Custom doors, frames, specialty casework - hardware coordination',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Commercial Fixtures',
                'color' => '#0F766E',
                'type' => 'work_scope',
                'description' => 'Retail displays, office millwork - commercial specs/compliance',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Service/Repair',
                'color' => '#475569',
                'type' => 'work_scope',
                'description' => 'Warranty work, modifications, fixes - fast-track priority',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Finishing Only',
                'color' => '#7C2D94',
                'type' => 'work_scope',
                'description' => 'Refinishing, touch-up, finishing services - schedule around production',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ==================================================================
        // PHASE 2: QUALITY/COMPLEXITY TAGS (3)
        // ==================================================================
        // Purpose: Help Miguel plan builds and mentor team; assist David with realistic scheduling

        $complexityTags = [
            [
                'name' => 'Standard Complexity',
                'color' => '#16A34A',
                'type' => 'complexity',
                'description' => 'Proven designs, standard materials, experienced team capable - normal timeline',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Advanced Complexity',
                'color' => '#CA8A04',
                'type' => 'complexity',
                'description' => 'Custom designs, specialty materials, requires lead craftsman - +25% timeline',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Master Complexity',
                'color' => '#DC2626',
                'type' => 'complexity',
                'description' => 'Novel techniques, exotic materials, Miguel-led builds only - +50% timeline',
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ==================================================================
        // INSERT ALL TAGS
        // ==================================================================

        $allTags = array_merge($priorityTags, $healthTags, $riskTags, $workScopeTags, $complexityTags);

        // Insert all tags, checking for duplicates
        foreach ($allTags as $tag) {
            $exists = DB::table('projects_tags')
                ->where('name', $tag['name'])
                ->exists();

            if (!$exists) {
                DB::table('projects_tags')->insert($tag);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define all tag names to remove
        $tagNames = [
            // Priority/Urgency
            'Critical Priority',
            'High Priority',
            'Standard Priority',
            'Low Priority',
            'On Hold',

            // Project Health
            'On Track',
            'At Risk',
            'Red Flag',
            'Blocked',
            'Budget Watch',
            'Schedule Watch',

            // Risk/Issue
            'Design Risk',
            'Material Risk',
            'Coordination Risk',
            'Payment Risk',
            'Capacity Risk',

            // Work Scope/Type
            'Custom Cabinetry',
            'Architectural Millwork',
            'Custom Furniture',
            'Doors & Casework',
            'Commercial Fixtures',
            'Service/Repair',
            'Finishing Only',

            // Quality/Complexity
            'Standard Complexity',
            'Advanced Complexity',
            'Master Complexity',
        ];

        // Remove tags (but NOT the lifecycle tags which were created earlier)
        DB::table('projects_tags')
            ->whereIn('name', $tagNames)
            ->delete();
    }
};
