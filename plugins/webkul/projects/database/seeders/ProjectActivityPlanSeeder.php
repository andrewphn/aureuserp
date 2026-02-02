<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Support\Models\ActivityPlan;
use Webkul\Support\Models\ActivityPlanTemplate;
use Webkul\Support\Models\ActivityType;

/**
 * Seeds Activity Plans for the Projects plugin.
 *
 * Activity Plans are templated sequences of follow-up reminders
 * (calls, emails, meetings, to-dos) that can be triggered on any project.
 */
class ProjectActivityPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Get activity type IDs
        $callType = ActivityType::where('name', 'Call')->first()?->id ?? 4;
        $todoType = ActivityType::where('name', 'To-Do')->first()?->id ?? 3;
        $meetingType = ActivityType::where('name', 'Meeting')->first()?->id ?? 1;

        $plans = [
            // 1. New Project Kickoff
            [
                'name' => 'New Project Kickoff',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Send welcome email with project questionnaire',
                        'activity_type_id' => $todoType,
                        'delay_count' => 0,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Send the standard project questionnaire to gather client preferences, timeline expectations, and design requirements.',
                    ],
                    [
                        'summary' => 'Schedule initial consultation call',
                        'activity_type_id' => $callType,
                        'delay_count' => 1,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call to introduce yourself, confirm project scope, and schedule the site visit.',
                    ],
                    [
                        'summary' => 'Schedule site measurement visit',
                        'activity_type_id' => $meetingType,
                        'delay_count' => 3,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Coordinate site visit for measurements and photos.',
                    ],
                    [
                        'summary' => 'Follow up on questionnaire if not received',
                        'activity_type_id' => $todoType,
                        'delay_count' => 5,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Check if client has completed the questionnaire. Send reminder if needed.',
                    ],
                ],
            ],

            // 2. Design Approval Follow-up
            [
                'name' => 'Design Approval Follow-up',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Send design package for approval',
                        'activity_type_id' => $todoType,
                        'delay_count' => 0,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Email the complete design package including 3D renders, shop drawings, and material selections.',
                    ],
                    [
                        'summary' => 'First follow-up call on design approval',
                        'activity_type_id' => $callType,
                        'delay_count' => 3,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call to check if client has reviewed the designs and answer any questions.',
                    ],
                    [
                        'summary' => 'Second follow-up email',
                        'activity_type_id' => $todoType,
                        'delay_count' => 5,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Send follow-up email requesting design approval or revision feedback.',
                    ],
                    [
                        'summary' => 'Escalate to PM if no response',
                        'activity_type_id' => $todoType,
                        'delay_count' => 7,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'If still no response, escalate to Project Manager for direct client contact.',
                    ],
                ],
            ],

            // 3. Material Order Tracking
            [
                'name' => 'Material Order Tracking',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Confirm PO sent to supplier',
                        'activity_type_id' => $todoType,
                        'delay_count' => 0,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Verify purchase order was sent and acknowledged by supplier.',
                    ],
                    [
                        'summary' => 'Check delivery ETA with supplier',
                        'activity_type_id' => $callType,
                        'delay_count' => 7,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call supplier to confirm delivery date and any potential delays.',
                    ],
                    [
                        'summary' => 'Verify materials received',
                        'activity_type_id' => $todoType,
                        'delay_count' => 14,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Check with warehouse that materials arrived and match the order.',
                    ],
                    [
                        'summary' => 'Complete quality inspection',
                        'activity_type_id' => $todoType,
                        'delay_count' => 15,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Inspect materials for damage, correct quantities, and quality standards.',
                    ],
                ],
            ],

            // 4. Pre-Delivery Checklist
            [
                'name' => 'Pre-Delivery Checklist',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Confirm delivery date with client',
                        'activity_type_id' => $callType,
                        'delay_count' => 0,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call client to confirm delivery date, time window, and site access.',
                    ],
                    [
                        'summary' => 'Final QC inspection',
                        'activity_type_id' => $todoType,
                        'delay_count' => 2,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Complete final quality control checklist before loading.',
                    ],
                    [
                        'summary' => 'Prepare delivery manifest and BOL',
                        'activity_type_id' => $todoType,
                        'delay_count' => 3,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Generate delivery manifest and bill of lading documents.',
                    ],
                    [
                        'summary' => 'Load and prep for delivery',
                        'activity_type_id' => $todoType,
                        'delay_count' => 4,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Load truck, verify all items against manifest, secure for transport.',
                    ],
                ],
            ],

            // 5. Post-Delivery Follow-up
            [
                'name' => 'Post-Delivery Follow-up',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Post-delivery satisfaction call',
                        'activity_type_id' => $callType,
                        'delay_count' => 2,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call client to ensure delivery went smoothly and address any concerns.',
                    ],
                    [
                        'summary' => 'Request customer feedback/review',
                        'activity_type_id' => $todoType,
                        'delay_count' => 7,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Send email requesting Google review or testimonial.',
                    ],
                    [
                        'summary' => 'Final invoice follow-up',
                        'activity_type_id' => $todoType,
                        'delay_count' => 14,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Confirm final invoice was received and check on payment status.',
                    ],
                ],
            ],

            // 6. Change Order Communication
            [
                'name' => 'Change Order Communication',
                'is_active' => true,
                'templates' => [
                    [
                        'summary' => 'Notify PM of change order request',
                        'activity_type_id' => $todoType,
                        'delay_count' => 0,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Alert Project Manager about the change order for review.',
                    ],
                    [
                        'summary' => 'Send change order to client for approval',
                        'activity_type_id' => $todoType,
                        'delay_count' => 1,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Email change order document with cost and timeline impact.',
                    ],
                    [
                        'summary' => 'Follow up on change order approval',
                        'activity_type_id' => $callType,
                        'delay_count' => 3,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Call client to discuss change order and get signature.',
                    ],
                    [
                        'summary' => 'Update production schedule',
                        'activity_type_id' => $todoType,
                        'delay_count' => 4,
                        'delay_unit' => 'days',
                        'delay_from' => 'current_date',
                        'note' => 'Once approved, update the production schedule and notify shop floor.',
                    ],
                ],
            ],
        ];

        $company = DB::table('companies')->where('is_default', true)->first();
        $companyId = $company?->id ?? 1;

        foreach ($plans as $planData) {
            // Check if plan already exists
            $existing = ActivityPlan::where('name', $planData['name'])
                ->where('plugin', 'projects')
                ->first();

            if ($existing) {
                $this->command->info("Activity Plan '{$planData['name']}' already exists, skipping.");
                continue;
            }

            // Create the plan
            $plan = ActivityPlan::create([
                'name' => $planData['name'],
                'plugin' => 'projects',
                'is_active' => $planData['is_active'],
                'company_id' => $companyId,
                'creator_id' => 1,
            ]);

            // Create templates
            $sort = 1;
            foreach ($planData['templates'] as $templateData) {
                ActivityPlanTemplate::create([
                    'plan_id' => $plan->id,
                    'activity_type_id' => $templateData['activity_type_id'],
                    'summary' => $templateData['summary'],
                    'delay_count' => $templateData['delay_count'],
                    'delay_unit' => $templateData['delay_unit'],
                    'delay_from' => $templateData['delay_from'],
                    'note' => $templateData['note'],
                    'sort' => $sort++,
                    'creator_id' => 1,
                ]);
            }

            $this->command->info("Created Activity Plan: {$planData['name']} with " . count($planData['templates']) . " activities");
        }
    }
}
