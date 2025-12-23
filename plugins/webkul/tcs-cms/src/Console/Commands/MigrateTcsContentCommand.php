<?php

namespace Webkul\TcsCms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\TcsCms\Models\Faq;
use Webkul\TcsCms\Models\HomeSection;
use Webkul\TcsCms\Models\Journal;
use Webkul\TcsCms\Models\Material;
use Webkul\TcsCms\Models\PortfolioProject;
use Webkul\TcsCms\Models\Service;
use Webkul\TcsCms\Models\TeamMember;

class MigrateTcsContentCommand extends Command
{
    protected $signature = 'tcs:migrate-cms
                            {--connection=tcswebsite : The database connection for the source TCS Website}
                            {--dry-run : Preview changes without actually migrating}
                            {--table=all : Specific table to migrate (all, works, journals, services, materials, faqs, teams, home_sections)}';

    protected $description = 'Migrate content from TCS Website database to AureusERP TCS CMS plugin';

    protected int $migrated = 0;

    protected int $skipped = 0;

    public function handle(): int
    {
        $connection = $this->option('connection');
        $dryRun = $this->option('dry-run');
        $table = $this->option('table');

        $this->info("Starting TCS CMS content migration from '{$connection}' database...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Check if source connection exists
        try {
            DB::connection($connection)->getPdo();
        } catch (\Exception $e) {
            $this->error("Cannot connect to source database '{$connection}'");
            $this->error('Add the connection to config/database.php or use --connection=mysql');

            return self::FAILURE;
        }

        $tables = $table === 'all'
            ? ['works', 'journals', 'services', 'materials', 'faqs', 'teams', 'home_sections']
            : [$table];

        foreach ($tables as $sourceTable) {
            $this->migrateTable($sourceTable, $connection, $dryRun);
        }

        $this->newLine();
        $this->info("Migration complete! Migrated: {$this->migrated}, Skipped: {$this->skipped}");

        return self::SUCCESS;
    }

    protected function migrateTable(string $table, string $connection, bool $dryRun): void
    {
        $this->newLine();
        $this->info("=== Migrating {$table} ===");

        $method = 'migrate'.ucfirst(str_replace('_', '', ucwords($table, '_')));

        if (! method_exists($this, $method)) {
            $this->warn("No migration handler for table: {$table}");

            return;
        }

        try {
            $this->$method($connection, $dryRun);
        } catch (\Exception $e) {
            $this->error("Error migrating {$table}: ".$e->getMessage());
        }
    }

    protected function migrateWorks(string $connection, bool $dryRun): void
    {
        $works = DB::connection($connection)->table('works')->get();

        $this->info("Found {$works->count()} portfolio projects");

        foreach ($works as $work) {
            // Check if already exists
            $exists = PortfolioProject::where('slug', $work->slug)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$work->title} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                PortfolioProject::create([
                    'title' => $work->title,
                    'slug' => $work->slug,
                    'client_name' => $work->client, // Renamed field
                    'summary' => $work->summary,
                    'description' => $work->description,
                    'category' => $work->category,
                    'materials' => json_decode($work->materials, true),
                    'techniques' => json_decode($work->techniques, true),
                    'dimensions' => $work->dimensions ? ['raw' => $work->dimensions] : null, // Convert string to JSON
                    'timeline' => $work->timeline,
                    'featured_image' => $work->featured_image,
                    'gallery' => json_decode($work->gallery, true),
                    'featured' => $work->featured,
                    'is_published' => $work->is_published,
                    'published_at' => $work->published_at,
                    'status' => $work->status,
                    'meta_title' => $work->meta_title,
                    'meta_description' => $work->meta_description,
                    'portfolio_order' => $work->position, // Renamed field
                    'created_at' => $work->created_at,
                    'updated_at' => $work->updated_at,
                    'deleted_at' => $work->deleted_at,
                ]);
            }

            $this->line("  [OK] {$work->title}");
            $this->migrated++;
        }
    }

    protected function migrateJournals(string $connection, bool $dryRun): void
    {
        $journals = DB::connection($connection)->table('journals')->get();

        $this->info("Found {$journals->count()} journal entries");

        foreach ($journals as $journal) {
            $exists = Journal::where('slug', $journal->slug)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$journal->title} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                Journal::create([
                    'title' => $journal->title,
                    'slug' => $journal->slug,
                    'excerpt' => $journal->excerpt,
                    'content' => $journal->content,
                    'category' => $journal->category,
                    'tags' => json_decode($journal->tags, true),
                    'featured_image' => $journal->featured_image,
                    'gallery' => json_decode($journal->gallery, true),
                    'is_published' => $journal->is_published,
                    'published_at' => $journal->published_at,
                    'status' => $journal->status,
                    'meta_title' => $journal->meta_title,
                    'meta_description' => $journal->meta_description,
                    'created_at' => $journal->created_at,
                    'updated_at' => $journal->updated_at,
                    'deleted_at' => $journal->deleted_at,
                ]);
            }

            $this->line("  [OK] {$journal->title}");
            $this->migrated++;
        }
    }

    protected function migrateServices(string $connection, bool $dryRun): void
    {
        $services = DB::connection($connection)->table('services')->get();

        $this->info("Found {$services->count()} services");

        foreach ($services as $service) {
            $exists = Service::where('slug', $service->slug)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$service->title} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                Service::create([
                    'title' => $service->title,
                    'slug' => $service->slug,
                    'summary' => $service->summary,
                    'description' => $service->description,
                    'category' => $service->category,
                    'features' => json_decode($service->features, true),
                    'price_range' => $service->price_range,
                    'timeline' => $service->timeline,
                    'featured_image' => $service->featured_image,
                    'gallery' => json_decode($service->gallery, true),
                    'is_published' => $service->is_published,
                    'published_at' => $service->published_at,
                    'status' => $service->status,
                    'meta_title' => $service->meta_title,
                    'meta_description' => $service->meta_description,
                    'position' => $service->position,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                    'deleted_at' => $service->deleted_at,
                ]);
            }

            $this->line("  [OK] {$service->title}");
            $this->migrated++;
        }
    }

    protected function migrateMaterials(string $connection, bool $dryRun): void
    {
        $materials = DB::connection($connection)->table('materials')->get();

        $this->info("Found {$materials->count()} materials");

        foreach ($materials as $material) {
            $exists = Material::where('slug', $material->slug)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$material->title} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                Material::create([
                    'name' => $material->title, // Renamed field
                    'slug' => $material->slug,
                    'scientific_name' => $material->scientific_name,
                    'description' => $material->description,
                    'content' => $material->content,
                    'characteristics' => $material->content, // Also copy to characteristics
                    'featured_image' => $material->featured_image,
                    'gallery' => json_decode($material->gallery, true),
                    'properties' => json_decode($material->properties, true),
                    'sustainability' => $material->sustainability,
                    'sustainability_rating' => $material->sustainability, // Also copy to new field
                    'applications' => json_decode($material->applications, true),
                    'position' => $material->position,
                    'featured' => $material->featured,
                    'is_published' => true, // Original doesn't have this field
                    'created_at' => $material->created_at,
                    'updated_at' => $material->updated_at,
                    'deleted_at' => $material->deleted_at,
                ]);
            }

            $this->line("  [OK] {$material->title}");
            $this->migrated++;
        }
    }

    protected function migrateFaqs(string $connection, bool $dryRun): void
    {
        $faqs = DB::connection($connection)->table('faqs')->get();

        $this->info("Found {$faqs->count()} FAQs");

        foreach ($faqs as $faq) {
            // Check by question since FAQs don't have slugs
            $exists = Faq::where('question', $faq->question)->exists();

            if ($exists) {
                $this->line('  [SKIP] '.substr($faq->question, 0, 50).'... - already exists');
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                Faq::create([
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                    'status' => $faq->status === 'published' ? 'published' : $faq->status,
                    'is_published' => $faq->status === 'published',
                    'view_count' => $faq->view_count ?? 0,
                    'created_at' => $faq->created_at,
                    'updated_at' => $faq->updated_at,
                    'deleted_at' => $faq->deleted_at,
                ]);
            }

            $this->line('  [OK] '.substr($faq->question, 0, 50).'...');
            $this->migrated++;
        }
    }

    protected function migrateTeams(string $connection, bool $dryRun): void
    {
        $teams = DB::connection($connection)->table('teams')->get();

        $this->info("Found {$teams->count()} team members");

        foreach ($teams as $team) {
            // Generate slug from name
            $slug = \Illuminate\Support\Str::slug($team->name);
            $exists = TeamMember::where('slug', $slug)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$team->name} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                TeamMember::create([
                    'name' => $team->name,
                    'slug' => $slug,
                    'role' => $team->position, // Original 'position' is job title -> 'role'
                    'bio' => $team->bio,
                    'position' => $team->order, // Original 'order' -> 'position' for sorting
                    'is_published' => true,
                    'zoho_employee_id' => $team->zoho_employee_id,
                    'zoho_department' => $team->zoho_department,
                    'zoho_role' => $team->zoho_role,
                    'zoho_status' => $team->zoho_status,
                    'zoho_join_date' => $team->zoho_join_date,
                    'zoho_leave_date' => $team->zoho_leave_date,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                ]);
            }

            $this->line("  [OK] {$team->name}");
            $this->migrated++;
        }
    }

    protected function migrateHomeSections(string $connection, bool $dryRun): void
    {
        $sections = DB::connection($connection)->table('home_sections')->get();

        $this->info("Found {$sections->count()} home sections");

        foreach ($sections as $section) {
            // Generate section key from title or type
            $sectionKey = \Illuminate\Support\Str::slug($section->title ?: $section->section_type);
            $exists = HomeSection::where('section_key', $sectionKey)->exists();

            if ($exists) {
                $this->line("  [SKIP] {$sectionKey} - already exists");
                $this->skipped++;

                continue;
            }

            if (! $dryRun) {
                HomeSection::create([
                    'section_key' => $sectionKey,
                    'section_type' => $section->section_type,
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'content' => $section->content,
                    'cta_text' => $section->cta_text,
                    'cta_link' => $section->cta_link,
                    'background_image' => $section->background_image,
                    'additional_images' => json_decode($section->additional_images, true),
                    'position' => $section->position,
                    'is_active' => $section->is_active,
                    'created_at' => $section->created_at,
                    'updated_at' => $section->updated_at,
                    'deleted_at' => $section->deleted_at,
                ]);
            }

            $this->line("  [OK] {$sectionKey}");
            $this->migrated++;
        }
    }
}
