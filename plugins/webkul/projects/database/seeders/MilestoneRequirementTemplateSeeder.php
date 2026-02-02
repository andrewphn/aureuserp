<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Project\Models\MilestoneRequirementTemplate;
use Webkul\Project\Models\MilestoneTemplate;

/**
 * Seeds verification requirements for each milestone template.
 *
 * These define what must be confirmed/verified before a milestone
 * can be marked complete.
 */
class MilestoneRequirementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            // ==================== DISCOVERY STAGE ====================
            
            'Initial Client Consultation' => [
                [
                    'name' => 'Client contact information recorded',
                    'requirement_type' => 'field_check',
                    'description' => 'Ensure client contact details are saved in partner record',
                    'config' => ['relation' => 'partner', 'field' => 'email'],
                ],
                [
                    'name' => 'Project location documented',
                    'requirement_type' => 'field_check',
                    'description' => 'Site address recorded for the project',
                    'config' => ['field' => 'site_address'],
                ],
                [
                    'name' => 'Budget range discussed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Confirm budget expectations were discussed with client',
                    'config' => ['instructions' => 'Verify budget range was discussed and documented in notes'],
                ],
            ],

            'Site Measurement & Assessment' => [
                [
                    'name' => 'Site measurements uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'Upload site measurement documents or photos',
                    'config' => ['document_type' => 'site_measurements', 'folder' => 'Measurements'],
                ],
                [
                    'name' => 'Rooms created in system',
                    'requirement_type' => 'relation_exists',
                    'description' => 'At least one room must be defined for the project',
                    'config' => ['relation' => 'rooms', 'min_count' => 1],
                ],
            ],

            'Material & Finish Selection' => [
                [
                    'name' => 'Wood species selected',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Client has selected primary wood species',
                    'config' => ['instructions' => 'Confirm wood species selection is documented'],
                    'is_required' => false,
                ],
                [
                    'name' => 'Finish type selected',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Client has selected finish type (stain, paint, natural)',
                    'config' => ['instructions' => 'Confirm finish selection is documented'],
                    'is_required' => false,
                ],
                [
                    'name' => 'Hardware style selected',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Client has selected hardware style preferences',
                    'config' => ['instructions' => 'Confirm hardware preferences documented'],
                    'is_required' => false,
                ],
            ],

            'Project Scope Finalization' => [
                [
                    'name' => 'Sales order created',
                    'requirement_type' => 'relation_exists',
                    'description' => 'Sales order must exist for the project',
                    'config' => ['relation' => 'orders', 'min_count' => 1],
                ],
                [
                    'name' => 'Proposal accepted by client',
                    'requirement_type' => 'field_check',
                    'description' => 'Client has accepted the proposal',
                    'config' => ['relation' => 'orders', 'field' => 'proposal_accepted_at'],
                ],
                [
                    'name' => 'Deposit received',
                    'requirement_type' => 'field_check',
                    'description' => 'Initial deposit payment has been received',
                    'config' => ['relation' => 'orders', 'field' => 'deposit_paid_at'],
                ],
                [
                    'name' => 'All cabinet specifications confirmed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Verify all cabinet specs (dimensions, features) are finalized',
                    'config' => ['instructions' => 'Review and confirm all cabinet specifications with client sign-off'],
                ],
            ],

            // ==================== DESIGN STAGE ====================

            'Initial Design Concepts' => [
                [
                    'name' => 'Design renderings or sketches uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => '3D renderings, concept sketches, or DWG imports uploaded',
                    'config' => [
                        'folder' => '02_Design/DWG_Imports',
                        'extensions' => ['3dm', 'dwg', 'dxf', 'pdf', 'png', 'jpg'],
                        'min_count' => 1,
                        'document_type' => 'design_rendering',
                    ],
                    'is_required' => false,
                ],
                [
                    'name' => 'Cabinets defined in system',
                    'requirement_type' => 'relation_exists',
                    'description' => 'Cabinet records created with basic dimensions',
                    'config' => ['relation' => 'cabinets', 'min_count' => 1],
                ],
            ],

            'Design Revisions & Approval' => [
                [
                    'name' => 'Design approved by client',
                    'requirement_type' => 'field_check',
                    'description' => 'Client has formally approved the design',
                    'config' => ['field' => 'design_approved_at'],
                ],
                [
                    'name' => 'Design approval document signed',
                    'requirement_type' => 'document_upload',
                    'description' => 'Signed design approval form uploaded',
                    'config' => ['document_type' => 'design_approval', 'folder' => 'Approvals'],
                    'is_required' => false,
                ],
            ],

            'Shop Drawings Complete' => [
                [
                    'name' => 'Rhino 3D model uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'Rhino .3dm files uploaded to CAD folder in Google Drive',
                    'config' => [
                        'folder' => '02_Design/DWG_Imports',
                        'extensions' => ['3dm', 'dwg', 'dxf'],
                        'min_count' => 1,
                        'document_type' => 'cad_file',
                    ],
                ],
                [
                    'name' => 'Shop drawings PDF uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'Shop drawing PDFs uploaded for production reference',
                    'config' => [
                        'folder' => '04_Production/Job Cards/Build_PDFs',
                        'extensions' => 'pdf',
                        'min_count' => 1,
                        'document_type' => 'shop_drawings',
                    ],
                    'is_required' => false,
                ],
                [
                    'name' => 'All cabinets have dimensions',
                    'requirement_type' => 'relation_complete',
                    'description' => 'Every cabinet must have width, height, depth defined',
                    'config' => ['relation' => 'cabinets', 'fields' => ['width', 'height', 'depth']],
                ],
                [
                    'name' => 'Redline review complete',
                    'requirement_type' => 'field_check',
                    'description' => 'Final redline changes have been reviewed and approved',
                    'config' => ['field' => 'redline_approved_at'],
                ],
            ],

            'Cut List & Material Takeoff' => [
                [
                    'name' => 'VCarve CNC files uploaded',
                    'requirement_type' => 'document_upload',
                    'description' => 'VCarve .crv files uploaded to CNC folder in Google Drive',
                    'config' => [
                        'folder' => '04_Production/CNC/VCarve Files',
                        'extensions' => ['crv', 'crv3d'],
                        'min_count' => 1,
                        'document_type' => 'cnc_file',
                    ],
                ],
                [
                    'name' => 'CNC toolpath files generated',
                    'requirement_type' => 'document_upload',
                    'description' => 'G-code toolpath files for CNC machine',
                    'config' => [
                        'folder' => '04_Production/CNC/ToolPaths',
                        'extensions' => ['sbp', 'nc', 'gcode', 'tap'],
                        'min_count' => 1,
                        'document_type' => 'toolpath',
                    ],
                    'is_required' => false,
                ],
                [
                    'name' => 'BOM generated',
                    'requirement_type' => 'relation_exists',
                    'description' => 'Bill of Materials has been generated for the project',
                    'config' => ['relation' => 'bomLines', 'min_count' => 1],
                ],
                [
                    'name' => 'Cut list exported',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Cut list has been generated and reviewed',
                    'config' => ['instructions' => 'Verify cut list has been generated from cabinet data'],
                ],
            ],

            // ==================== SOURCING STAGE ====================

            'Lumber Order Placed' => [
                [
                    'name' => 'Lumber purchase order created',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Purchase order for lumber materials has been created',
                    'config' => ['instructions' => 'Verify lumber PO exists and has been sent to supplier'],
                ],
                [
                    'name' => 'Sheet goods order placed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Plywood/sheet goods ordered',
                    'config' => ['instructions' => 'Verify sheet goods PO exists'],
                    'is_required' => false,
                ],
            ],

            'Hardware & Accessories Ordered' => [
                [
                    'name' => 'All cabinets have hardware assigned',
                    'requirement_type' => 'relation_complete',
                    'description' => 'Hardware selections made for all cabinets',
                    'config' => ['relation' => 'cabinets', 'check' => 'has_hardware_selections'],
                ],
                [
                    'name' => 'Hardware purchase order created',
                    'requirement_type' => 'checklist_item',
                    'description' => 'PO for hardware (hinges, slides, knobs) created',
                    'config' => ['instructions' => 'Verify hardware PO has been created and sent'],
                ],
                [
                    'name' => 'Specialty items ordered',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Any specialty items (appliance panels, glass, etc.) ordered',
                    'config' => ['instructions' => 'Verify any specialty items have been ordered'],
                    'is_required' => false,
                ],
            ],

            'Materials Received & Inspected' => [
                [
                    'name' => 'Lumber received and inspected',
                    'requirement_type' => 'checklist_item',
                    'description' => 'All lumber deliveries received and quality checked',
                    'config' => ['instructions' => 'Inspect lumber for defects, verify quantities match PO'],
                ],
                [
                    'name' => 'Hardware received',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Hardware deliveries received and verified',
                    'config' => ['instructions' => 'Check hardware against PO, report any shortages'],
                ],
                [
                    'name' => 'All materials received flag set',
                    'requirement_type' => 'field_check',
                    'description' => 'Project marked as having all materials received',
                    'config' => ['field' => 'all_materials_received_at'],
                ],
            ],

            'Material Acclimation Complete' => [
                [
                    'name' => 'Acclimation period elapsed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Lumber has acclimated to shop environment (typically 3-7 days)',
                    'config' => ['instructions' => 'Verify lumber has been in shop environment long enough to stabilize'],
                    'is_required' => false,
                ],
                [
                    'name' => 'Materials staged for production',
                    'requirement_type' => 'field_check',
                    'description' => 'Materials have been organized and staged for production',
                    'config' => ['field' => 'materials_staged_at'],
                    'is_required' => false,
                ],
            ],

            // ==================== PRODUCTION STAGE ====================

            'Rough Mill Complete' => [
                [
                    'name' => 'Rough milling started',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Initial rough cutting and milling has begun',
                    'config' => ['instructions' => 'Verify rough mill process has been completed for all lumber'],
                ],
            ],

            'Cabinet Boxes Complete' => [
                [
                    'name' => 'All cabinet boxes assembled',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Cabinet carcasses are built and assembled',
                    'config' => ['instructions' => 'Verify all cabinet boxes are assembled and square'],
                ],
                [
                    'name' => 'Box assembly QC passed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Quality check on cabinet box assembly',
                    'config' => ['instructions' => 'Check for square, proper joints, correct dimensions'],
                ],
            ],

            'Doors & Drawer Fronts Complete' => [
                [
                    'name' => 'All doors fabricated',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Door panels are complete',
                    'config' => ['instructions' => 'Verify all doors are fabricated to spec'],
                ],
                [
                    'name' => 'All drawer fronts fabricated',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Drawer fronts are complete',
                    'config' => ['instructions' => 'Verify all drawer fronts are fabricated to spec'],
                ],
            ],

            'Sanding & Prep for Finish' => [
                [
                    'name' => 'All components sanded',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Sanding complete on all visible surfaces',
                    'config' => ['instructions' => 'Verify sanding progression (80-120-180-220) complete'],
                    'is_required' => false,
                ],
            ],

            'Finishing Complete' => [
                [
                    'name' => 'Stain/paint applied',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Color coat application complete',
                    'config' => ['instructions' => 'Verify stain or paint has been applied per spec'],
                ],
                [
                    'name' => 'Topcoat complete',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Clear coat / topcoat applied and cured',
                    'config' => ['instructions' => 'Verify final topcoat applied and properly cured'],
                ],
                [
                    'name' => 'Finish QC passed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Finish quality inspection passed',
                    'config' => ['instructions' => 'Inspect for runs, drips, missed spots, proper sheen'],
                ],
            ],

            'Hardware Installation & QC' => [
                [
                    'name' => 'Hinges installed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Door hinges installed and adjusted',
                    'config' => ['instructions' => 'Verify all hinges installed and doors aligned'],
                ],
                [
                    'name' => 'Drawer slides installed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Drawer slides/glides installed',
                    'config' => ['instructions' => 'Verify drawer slides installed and operating smoothly'],
                ],
                [
                    'name' => 'Pulls/knobs installed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Hardware pulls and knobs installed',
                    'config' => ['instructions' => 'Verify all pulls/knobs installed at correct positions'],
                ],
                [
                    'name' => 'Final QC inspection passed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Complete quality control inspection',
                    'config' => ['instructions' => 'Full inspection: fit, finish, function, hardware alignment'],
                ],
                [
                    'name' => 'QC passed timestamp',
                    'requirement_type' => 'field_check',
                    'description' => 'Project marked as passing final QC',
                    'config' => ['field' => 'qc_passed_at'],
                ],
            ],

            // ==================== DELIVERY STAGE ====================

            'Pre-Installation Site Check' => [
                [
                    'name' => 'Site ready for installation',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Verify installation site is prepared',
                    'config' => ['instructions' => 'Confirm: counters removed, old cabinets out, walls prepped, utilities accessible'],
                ],
                [
                    'name' => 'Delivery scheduled with client',
                    'requirement_type' => 'field_check',
                    'description' => 'Delivery date confirmed',
                    'config' => ['field' => 'scheduled_delivery_date'],
                ],
                [
                    'name' => 'Installation crew assigned',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Installation team assigned to project',
                    'config' => ['instructions' => 'Confirm installation crew is scheduled'],
                ],
            ],

            'Delivery & Installation' => [
                [
                    'name' => 'Cabinets delivered to site',
                    'requirement_type' => 'checklist_item',
                    'description' => 'All cabinets transported to installation site',
                    'config' => ['instructions' => 'Verify all cabinets delivered without damage'],
                ],
                [
                    'name' => 'Cabinets installed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Cabinets mounted, leveled, and secured',
                    'config' => ['instructions' => 'Verify all cabinets installed, level, and secure'],
                ],
                [
                    'name' => 'Delivered timestamp',
                    'requirement_type' => 'field_check',
                    'description' => 'Project marked as delivered',
                    'config' => ['field' => 'delivered_at'],
                ],
            ],

            'Final Adjustments & Touch-ups' => [
                [
                    'name' => 'Doors adjusted',
                    'requirement_type' => 'checklist_item',
                    'description' => 'All doors aligned and adjusted',
                    'config' => ['instructions' => 'Fine-tune door alignment after installation'],
                    'is_required' => false,
                ],
                [
                    'name' => 'Touch-ups completed',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Any finish touch-ups from installation completed',
                    'config' => ['instructions' => 'Address any scratches or marks from installation'],
                    'is_required' => false,
                ],
            ],

            'Client Walkthrough & Sign-off' => [
                [
                    'name' => 'Walkthrough completed with client',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Final walkthrough conducted with client',
                    'config' => ['instructions' => 'Walk client through all cabinets, demonstrate features'],
                ],
                [
                    'name' => 'Punch list items resolved',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Any issues from walkthrough addressed',
                    'config' => ['instructions' => 'All punch list items have been resolved'],
                ],
                [
                    'name' => 'Client sign-off obtained',
                    'requirement_type' => 'document_upload',
                    'description' => 'Signed completion/acceptance document',
                    'config' => ['document_type' => 'completion_signoff', 'folder' => 'Approvals'],
                ],
                [
                    'name' => 'Final payment collected',
                    'requirement_type' => 'checklist_item',
                    'description' => 'Final payment received or invoiced',
                    'config' => ['instructions' => 'Verify final payment has been collected or invoice sent'],
                ],
            ],
        ];

        foreach ($requirements as $milestoneName => $reqs) {
            $template = MilestoneTemplate::where('name', $milestoneName)->first();
            
            if (!$template) {
                $this->command->warn("Milestone template not found: {$milestoneName}");
                continue;
            }

            foreach ($reqs as $index => $req) {
                MilestoneRequirementTemplate::create([
                    'milestone_template_id' => $template->id,
                    'name' => $req['name'],
                    'requirement_type' => $req['requirement_type'],
                    'description' => $req['description'] ?? null,
                    'config' => $req['config'] ?? null,
                    'sort_order' => $index + 1,
                    'is_required' => $req['is_required'] ?? true,
                    'is_active' => true,
                ]);
            }

            $this->command->info("Created " . count($reqs) . " requirements for: {$milestoneName}");
        }
    }
}
