<?php

namespace Webkul\Project\Tests;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Test Case class
 *
 * Note: Due to complex plugin migration dependencies, this TestCase creates
 * only the essential tables needed for testing the Projects plugin directly.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Create essential project tables for testing without running full plugin migrations.
     */
    protected function afterRefreshingDatabase(): void
    {
        $this->createPartnersTable();
        $this->createProjectsTables();
        $this->createPdfDocumentsTables();
    }

    /**
     * Create partners_partners table
     */
    protected function createPartnersTable(): void
    {
        if (!Schema::hasTable('partners_partners')) {
            Schema::create('partners_partners', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sub_type')->nullable();
                $table->string('street1')->nullable();
                $table->string('city')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Create projects plugin tables
     */
    protected function createProjectsTables(): void
    {
        // Project stages table
        if (!Schema::hasTable('projects_project_stages')) {
            Schema::create('projects_project_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('color')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Projects table
        if (!Schema::hasTable('projects_projects')) {
            Schema::create('projects_projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('project_number')->nullable();
                $table->foreignId('partner_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('creator_id')->nullable();
                $table->foreignId('stage_id')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort')->default(0); // For Sortable trait
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Rooms table
        if (!Schema::hasTable('projects_rooms')) {
            Schema::create('projects_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id');
                $table->string('name');
                $table->string('room_type')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Room locations table
        if (!Schema::hasTable('projects_room_locations')) {
            Schema::create('projects_room_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id');
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Cabinet runs table
        if (!Schema::hasTable('projects_cabinet_runs')) {
            Schema::create('projects_cabinet_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id');
                $table->string('name');
                $table->string('run_type')->nullable();
                $table->string('cabinet_level')->default('2');
                $table->decimal('total_linear_feet', 8, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Project drafts table
        if (!Schema::hasTable('projects_project_drafts')) {
            Schema::create('projects_project_drafts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('session_id');
                $table->string('wizard_type');
                $table->json('form_data')->nullable();
                $table->integer('current_step')->default(1);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Create PDF documents tables
     */
    protected function createPdfDocumentsTables(): void
    {
        if (!Schema::hasTable('pdf_documents')) {
            Schema::create('pdf_documents', function (Blueprint $table) {
                $table->id();
                $table->string('module_type');
                $table->unsignedBigInteger('module_id');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type')->default('application/pdf');
                $table->integer('page_count')->default(0);
                $table->boolean('is_primary_reference')->default(false);
                $table->boolean('is_latest_version')->default(true);
                $table->foreignId('uploaded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('pdf_pages')) {
            Schema::create('pdf_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id');
                $table->integer('page_number');
                $table->string('page_type')->nullable();
                $table->integer('width')->default(612);
                $table->integer('height')->default(792);
                $table->integer('rotation')->default(0);
                $table->text('extracted_text')->nullable();
                $table->json('page_metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user directly (without seeding) to avoid transaction conflicts
        \App\Models\User::create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }
}
