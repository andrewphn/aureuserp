<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_tags', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name')->index();
        });

        // Update existing lifecycle phase tags with 'lifecycle' type
        DB::table('projects_tags')
            ->whereIn('name', [
                'Awareness & Contact',
                'Intake & Qualify',
                'Bid/Proposal',
                'Agreement & Contract',
                'Kickoff & Deposit',
                'Design & Development',
                'Production',
                'Change Orders',
                'QC & Finishing',
                'Delivery & Install',
                'Acceptance & Payment',
                'Post-Project',
            ])
            ->update(['type' => 'lifecycle']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_tags', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
