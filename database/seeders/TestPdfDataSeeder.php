<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Mock seeder for testing PDF annotation flow
 * Creates test users, project and PDF document for E2E testing
 *
 * To run: php artisan db:seed --class=TestPdfDataSeeder
 * To rollback: Delete project ID 9 and related PDF records
 */
class TestPdfDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users if they don't exist
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id');

        if (!$userId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'TCS Admin',
                'email' => 'info@tcswoodwork.com',
                'password' => Hash::make('Lola2024!'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created user: info@tcswoodwork.com (ID: {$userId})");
        }

        // Create second test user for multi-user tests
        $user2Id = DB::table('users')->where('email', 'info@andrewphan.com')->value('id');

        if (!$user2Id) {
            $user2Id = DB::table('users')->insertGetId([
                'name' => 'Andrew Phan',
                'email' => 'info@andrewphan.com',
                'password' => Hash::make('Lola2024!'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created user: info@andrewphan.com (ID: {$user2Id})");
        }

        // Get stage ID - use first available stage
        $stageId = DB::table('projects_project_stages')->orderBy('id')->value('id');
        if (!$stageId) {
            // Create a default stage if none exists
            $stageId = DB::table('projects_project_stages')->insertGetId([
                'name' => 'Discovery',
                'is_active' => true,
                'sort' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create test project with ID 9 (matching test fixture)
        $projectId = DB::table('projects_projects')->insertGetId([
            'id' => 9, // Explicitly set to match test fixture
            'name' => 'Test PDF Annotation Project',
            'project_number' => 'TCS-TEST-001',
            'project_type' => 'residential',
            'description' => 'Test project for E2E PDF annotation testing',
            'visibility' => 'portal',
            'is_active' => true,
            'stage_id' => $stageId,
            'user_id' => $userId,
            'creator_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a test PDF document
        $pdfDocumentId = DB::table('pdf_documents')->insertGetId([
            'id' => 1, // Explicitly set to match test fixture ?pdf=1
            'module_type' => 'project',
            'module_id' => $projectId,
            'file_name' => 'test-floor-plan.pdf',
            'document_type' => 'floor_plan',
            'file_path' => 'pdf-documents/test-floor-plan.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'version_number' => 1,
            'is_latest_version' => true,
            'is_primary_reference' => true,
            'uploaded_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create PDF pages
        for ($pageNum = 1; $pageNum <= 3; $pageNum++) {
            DB::table('pdf_pages')->insert([
                'document_id' => $pdfDocumentId,
                'page_number' => $pageNum,
                'page_type' => 'floor_plan',
                'width' => 612, // Letter size
                'height' => 792,
                'rotation' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("Created test project ID: {$projectId}");
        $this->command->info("Created test PDF document ID: {$pdfDocumentId}");
        $this->command->info("Created 3 PDF pages");
        $this->command->info("Test URL: /admin/project/projects/{$projectId}/annotate-v2/1?pdf={$pdfDocumentId}");
    }
}
