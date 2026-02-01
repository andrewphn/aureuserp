<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds lock status fields to projects for tracking when design, procurement,
     * and production freezes were applied. Also adds snapshot fields for preserving
     * BOM and pricing state at lock time.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

Schema::table('projects_projects', function (Blueprint $table) {
            // Design lock - prevents cabinet spec edits
            $table->timestamp('design_locked_at')->nullable();
            $table->foreignId('design_locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Procurement lock - prevents BOM quantity changes
            $table->timestamp('procurement_locked_at')->nullable();
            $table->foreignId('procurement_locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Production lock - prevents geometry/dimension changes
            $table->timestamp('production_locked_at')->nullable();
            $table->foreignId('production_locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Snapshots at lock time for comparison and change order tracking
            $table->json('bom_snapshot_json')->nullable();
            $table->json('pricing_snapshot_json')->nullable();
            
            // Index for quick lock status queries
            $table->index(['design_locked_at', 'procurement_locked_at', 'production_locked_at'], 'projects_lock_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropIndex('projects_lock_status_idx');
            
            $table->dropConstrainedForeignId('design_locked_by');
            $table->dropConstrainedForeignId('procurement_locked_by');
            $table->dropConstrainedForeignId('production_locked_by');
            
            $table->dropColumn([
                'design_locked_at',
                'procurement_locked_at',
                'production_locked_at',
                'bom_snapshot_json',
                'pricing_snapshot_json',
            ]);
        });
    }
};
