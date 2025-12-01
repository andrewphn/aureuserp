<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * HIGH PRIORITY Stage Gate Fields for TCS Production Workflow:
     * - Discovery → Design: deposit_paid_at on sales_orders
     * - Design → Sourcing: design_approved_at, redline_approved_at on projects
     * - Sourcing → Production: materials_staged_at, all_materials_received_at on projects
     * - Production tracking: face_frame_cut_at, door_fronts_cut_at, hardware_installed_at, edge_banded_at on cabinet_specifications
     * - Delivery completion: bol_created_at, bol_signed_at, delivered_at, closeout_delivered_at, customer_signoff_at on projects
     * - Payment tracking: final_paid_at on sales_orders
     */
    public function up(): void
    {
        // Projects table - Stage gate timestamps
        // Check if table exists (plugin tables may not exist during fresh migrations)
        if (Schema::hasTable('projects_projects')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                // Only add columns if they don't already exist
                if (!Schema::hasColumn('projects_projects', 'design_approved_at')) {
                    // Design stage gates
                    $table->timestamp('design_approved_at')->nullable()->after('current_production_stage')
                        ->comment('Customer approved final design');
                    $table->timestamp('redline_approved_at')->nullable()->after('design_approved_at')
                        ->comment('Final redline changes confirmed');

                    // Sourcing stage gates
                    $table->timestamp('materials_staged_at')->nullable()->after('redline_approved_at')
                        ->comment('All materials staged in shop');
                    $table->timestamp('all_materials_received_at')->nullable()->after('materials_staged_at')
                        ->comment('All POs received and verified');

                    // Delivery stage gates
                    $table->timestamp('bol_created_at')->nullable()->after('all_materials_received_at')
                        ->comment('Bill of lading created');
                    $table->timestamp('bol_signed_at')->nullable()->after('bol_created_at')
                        ->comment('BOL signed by carrier/customer');
                    $table->timestamp('delivered_at')->nullable()->after('bol_signed_at')
                        ->comment('Physical delivery confirmed');
                    $table->timestamp('closeout_delivered_at')->nullable()->after('delivered_at')
                        ->comment('Closeout package delivered to customer');
                    $table->timestamp('customer_signoff_at')->nullable()->after('closeout_delivered_at')
                        ->comment('Customer final signoff received');
                }
            });
        }

        // Sales Orders table - Payment stage gates
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'deposit_paid_at')) {
                    $table->timestamp('deposit_paid_at')->nullable()->after('state')
                        ->comment('Deposit payment received - gates Discovery→Design');
                    $table->timestamp('final_paid_at')->nullable()->after('deposit_paid_at')
                        ->comment('Final payment received - gates project closure');
                }
            });
        }

        // Cabinet Specifications table - Production tracking timestamps
        if (Schema::hasTable('projects_cabinet_specifications')) {
            Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
                if (!Schema::hasColumn('projects_cabinet_specifications', 'face_frame_cut_at')) {
                    // Manual cutting operations
                    $table->timestamp('face_frame_cut_at')->nullable()->after('cnc_cut_at')
                        ->comment('Face frame cutting completed');
                    $table->timestamp('door_fronts_cut_at')->nullable()->after('face_frame_cut_at')
                        ->comment('Door and drawer fronts cut');

                    // Additional production operations
                    $table->timestamp('edge_banded_at')->nullable()->after('door_fronts_cut_at')
                        ->comment('Edge banding applied');
                    $table->timestamp('hardware_installed_at')->nullable()->after('edge_banded_at')
                        ->comment('Hardware installation completed');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects_projects') && Schema::hasColumn('projects_projects', 'design_approved_at')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                $table->dropColumn([
                    'design_approved_at',
                    'redline_approved_at',
                    'materials_staged_at',
                    'all_materials_received_at',
                    'bol_created_at',
                    'bol_signed_at',
                    'delivered_at',
                    'closeout_delivered_at',
                    'customer_signoff_at',
                ]);
            });
        }

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'deposit_paid_at')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn([
                    'deposit_paid_at',
                    'final_paid_at',
                ]);
            });
        }

        if (Schema::hasTable('projects_cabinet_specifications') && Schema::hasColumn('projects_cabinet_specifications', 'face_frame_cut_at')) {
            Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
                $table->dropColumn([
                    'face_frame_cut_at',
                    'door_fronts_cut_at',
                    'edge_banded_at',
                    'hardware_installed_at',
                ]);
            });
        }
    }
};
