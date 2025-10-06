<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix company ID 1 to be "The Carpenter's Son Woodworking LLC"
        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'The Carpenter\'s Son Woodworking LLC',
                'acronym' => 'TCS',
                'updated_at' => now(),
            ]);

        // Update Trottier to be "Trottier's Son" and set parent_id to TCS
        $tcsId = DB::table('companies')->where('acronym', 'TCS')->value('id');
        
        DB::table('companies')
            ->where('email', 'jeremybtrottier@gmail.com')
            ->update([
                'name' => 'Trottier\'s Son',
                'parent_id' => $tcsId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old names
        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'TCS Woodwork',
                'updated_at' => now(),
            ]);

        DB::table('companies')
            ->where('email', 'jeremybtrottier@gmail.com')
            ->update([
                'name' => 'Trottier Fine Woodworking',
                'parent_id' => null,
                'updated_at' => now(),
            ]);
    }
};
