<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration renames:
     * - Table: projects_cabinet_specifications → projects_cabinets (if not already done)
     * - Foreign key column in all related tables: cabinet_specification_id → cabinet_id
     */
    public function up(): void
    {
        // 1. Rename the main table (if not already renamed)
        if (Schema::hasTable('projects_cabinet_specifications') && !Schema::hasTable('projects_cabinets')) {
            Schema::rename('projects_cabinet_specifications', 'projects_cabinets');
        }

        // 2. Rename foreign key columns in related tables
        // Using explicit FK constraint names found in the database

        // hardware_requirements
        if (Schema::hasTable('hardware_requirements') && Schema::hasColumn('hardware_requirements', 'cabinet_specification_id')) {
            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->dropForeign('hardware_requirements_cabinet_specification_id_foreign');
            });

            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // pdf_page_annotations
        if (Schema::hasTable('pdf_page_annotations') && Schema::hasColumn('pdf_page_annotations', 'cabinet_specification_id')) {
            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->dropForeign('pdf_page_annotations_cabinet_specification_id_foreign');
            });

            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->nullOnDelete();
            });
        }

        // projects_bom
        if (Schema::hasTable('projects_bom') && Schema::hasColumn('projects_bom', 'cabinet_specification_id')) {
            Schema::table('projects_bom', function (Blueprint $table) {
                $table->dropForeign('projects_bom_cabinet_specification_id_foreign');
            });

            Schema::table('projects_bom', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_bom', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // projects_doors
        if (Schema::hasTable('projects_doors') && Schema::hasColumn('projects_doors', 'cabinet_specification_id')) {
            Schema::table('projects_doors', function (Blueprint $table) {
                $table->dropForeign('projects_doors_cabinet_specification_id_foreign');
            });

            Schema::table('projects_doors', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_doors', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // projects_drawers
        if (Schema::hasTable('projects_drawers') && Schema::hasColumn('projects_drawers', 'cabinet_specification_id')) {
            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->dropForeign('projects_drawers_cabinet_specification_id_foreign');
            });

            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // projects_pullouts
        if (Schema::hasTable('projects_pullouts') && Schema::hasColumn('projects_pullouts', 'cabinet_specification_id')) {
            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->dropForeign('projects_pullouts_cabinet_specification_id_foreign');
            });

            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // projects_shelves
        if (Schema::hasTable('projects_shelves') && Schema::hasColumn('projects_shelves', 'cabinet_specification_id')) {
            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->dropForeign('projects_shelves_cabinet_specification_id_foreign');
            });

            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }

        // projects_tasks
        if (Schema::hasTable('projects_tasks') && Schema::hasColumn('projects_tasks', 'cabinet_specification_id')) {
            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->dropForeign('projects_tasks_cabinet_specification_id_foreign');
            });

            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->nullOnDelete();
            });
        }

        // sales_order_line_items
        if (Schema::hasTable('sales_order_line_items') && Schema::hasColumn('sales_order_line_items', 'cabinet_specification_id')) {
            // Check if FK exists before trying to drop
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = 'aureuserp'
                AND TABLE_NAME = 'sales_order_line_items'
                AND COLUMN_NAME = 'cabinet_specification_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($fkExists)) {
                Schema::table('sales_order_line_items', function (Blueprint $table) use ($fkExists) {
                    $table->dropForeign($fkExists[0]->CONSTRAINT_NAME);
                });
            }

            Schema::table('sales_order_line_items', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            Schema::table('sales_order_line_items', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename columns back and then the table

        // sales_order_line_items
        if (Schema::hasTable('sales_order_line_items') && Schema::hasColumn('sales_order_line_items', 'cabinet_id')) {
            Schema::table('sales_order_line_items', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('sales_order_line_items', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('sales_order_line_items', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->nullOnDelete();
            });
        }

        // projects_tasks
        if (Schema::hasTable('projects_tasks') && Schema::hasColumn('projects_tasks', 'cabinet_id')) {
            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_tasks', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->nullOnDelete();
            });
        }

        // projects_shelves
        if (Schema::hasTable('projects_shelves') && Schema::hasColumn('projects_shelves', 'cabinet_id')) {
            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_shelves', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // projects_pullouts
        if (Schema::hasTable('projects_pullouts') && Schema::hasColumn('projects_pullouts', 'cabinet_id')) {
            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_pullouts', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // projects_drawers
        if (Schema::hasTable('projects_drawers') && Schema::hasColumn('projects_drawers', 'cabinet_id')) {
            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // projects_doors
        if (Schema::hasTable('projects_doors') && Schema::hasColumn('projects_doors', 'cabinet_id')) {
            Schema::table('projects_doors', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_doors', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_doors', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // projects_bom
        if (Schema::hasTable('projects_bom') && Schema::hasColumn('projects_bom', 'cabinet_id')) {
            Schema::table('projects_bom', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('projects_bom', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('projects_bom', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // pdf_page_annotations
        if (Schema::hasTable('pdf_page_annotations') && Schema::hasColumn('pdf_page_annotations', 'cabinet_id')) {
            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->nullOnDelete();
            });
        }

        // hardware_requirements
        if (Schema::hasTable('hardware_requirements') && Schema::hasColumn('hardware_requirements', 'cabinet_id')) {
            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            Schema::table('hardware_requirements', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinet_specifications')
                    ->onDelete('cascade');
            });
        }

        // Rename the main table back
        if (Schema::hasTable('projects_cabinets') && !Schema::hasTable('projects_cabinet_specifications')) {
            Schema::rename('projects_cabinets', 'projects_cabinet_specifications');
        }
    }
};
