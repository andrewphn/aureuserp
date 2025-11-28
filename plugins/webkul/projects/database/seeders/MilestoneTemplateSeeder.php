<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Project\Models\MilestoneTemplate;

/**
 * Milestone Template Seeder database seeder
 *
 */
class MilestoneTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Discovery Stage (0-14 days)
            [
                'name' => 'Initial Client Consultation',
                'production_stage' => 'discovery',
                'is_critical' => true,
                'description' => 'Meet with client to understand project requirements, style preferences, and budget constraints.',
                'relative_days' => 2,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Site Measurement & Assessment',
                'production_stage' => 'discovery',
                'is_critical' => true,
                'description' => 'Take detailed measurements of installation site, assess structural conditions, and document existing conditions.',
                'relative_days' => 5,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Material & Finish Selection',
                'production_stage' => 'discovery',
                'is_critical' => false,
                'description' => 'Client selects wood species, finishes, hardware, and other material preferences.',
                'relative_days' => 10,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Project Scope Finalization',
                'production_stage' => 'discovery',
                'is_critical' => true,
                'description' => 'Finalize project scope, confirm specifications, and obtain client approval on requirements.',
                'relative_days' => 14,
                'sort_order' => 4,
                'is_active' => true,
            ],

            // Design Stage (14-35 days)
            [
                'name' => 'Initial Design Concepts',
                'production_stage' => 'design',
                'is_critical' => true,
                'description' => 'Create preliminary design concepts and 3D renderings for client review.',
                'relative_days' => 18,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Design Revisions & Approval',
                'production_stage' => 'design',
                'is_critical' => true,
                'description' => 'Incorporate client feedback and obtain final design approval.',
                'relative_days' => 25,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Shop Drawings Complete',
                'production_stage' => 'design',
                'is_critical' => true,
                'description' => 'Complete detailed shop drawings with all dimensions, joinery details, and material specifications.',
                'relative_days' => 32,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Cut List & Material Takeoff',
                'production_stage' => 'design',
                'is_critical' => false,
                'description' => 'Generate detailed cut lists and material requirements for sourcing.',
                'relative_days' => 35,
                'sort_order' => 4,
                'is_active' => true,
            ],

            // Sourcing Stage (35-56 days)
            [
                'name' => 'Lumber Order Placed',
                'production_stage' => 'sourcing',
                'is_critical' => true,
                'description' => 'Order primary lumber materials from suppliers based on material takeoff.',
                'relative_days' => 38,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware & Accessories Ordered',
                'production_stage' => 'sourcing',
                'is_critical' => true,
                'description' => 'Order all hardware, hinges, drawer slides, and specialty accessories.',
                'relative_days' => 42,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Materials Received & Inspected',
                'production_stage' => 'sourcing',
                'is_critical' => true,
                'description' => 'Receive all materials, inspect for quality and completeness, report any issues.',
                'relative_days' => 52,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Material Acclimation Complete',
                'production_stage' => 'sourcing',
                'is_critical' => false,
                'description' => 'Allow lumber to acclimate to shop environment to prevent warping.',
                'relative_days' => 56,
                'sort_order' => 4,
                'is_active' => true,
            ],

            // Production Stage (56-112 days)
            [
                'name' => 'Rough Mill Complete',
                'production_stage' => 'production',
                'is_critical' => true,
                'description' => 'Complete initial rough milling of all lumber to approximate dimensions.',
                'relative_days' => 63,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Cabinet Boxes Complete',
                'production_stage' => 'production',
                'is_critical' => true,
                'description' => 'Complete fabrication and assembly of all cabinet boxes.',
                'relative_days' => 77,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Doors & Drawer Fronts Complete',
                'production_stage' => 'production',
                'is_critical' => true,
                'description' => 'Complete fabrication of all doors and drawer fronts.',
                'relative_days' => 84,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Sanding & Prep for Finish',
                'production_stage' => 'production',
                'is_critical' => false,
                'description' => 'Complete all sanding and surface preparation for finishing.',
                'relative_days' => 91,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Finishing Complete',
                'production_stage' => 'production',
                'is_critical' => true,
                'description' => 'Complete all staining, sealing, and topcoat applications.',
                'relative_days' => 105,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware Installation & QC',
                'production_stage' => 'production',
                'is_critical' => true,
                'description' => 'Install all hardware and perform final quality control inspection.',
                'relative_days' => 112,
                'sort_order' => 6,
                'is_active' => true,
            ],

            // Delivery Stage (112-126 days)
            [
                'name' => 'Pre-Installation Site Check',
                'production_stage' => 'delivery',
                'is_critical' => true,
                'description' => 'Verify installation site is ready, coordinate access, and confirm schedule.',
                'relative_days' => 115,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Delivery & Installation',
                'production_stage' => 'delivery',
                'is_critical' => true,
                'description' => 'Deliver cabinets to site and complete professional installation.',
                'relative_days' => 119,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Final Adjustments & Touch-ups',
                'production_stage' => 'delivery',
                'is_critical' => false,
                'description' => 'Make final adjustments to doors, drawers, and any required touch-ups.',
                'relative_days' => 122,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Client Walkthrough & Sign-off',
                'production_stage' => 'delivery',
                'is_critical' => true,
                'description' => 'Conduct final walkthrough with client, demonstrate features, and obtain project sign-off.',
                'relative_days' => 126,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            MilestoneTemplate::create($template);
        }
    }
}
