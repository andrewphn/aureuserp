<?php

namespace Webkul\Project;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Webkul\Project\Console\Commands\CleanupExpiredDrafts;
use Webkul\Project\Console\Commands\RecalculateComplexityScores;
use Webkul\Project\Events\ProjectStageChanged;
use Webkul\Project\Listeners\HandleProjectStageChange;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\HardwareRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Observers\ComplexityScoreObserver;
use Webkul\Project\Observers\HardwareRequirementObserver;
use Webkul\Project\Observers\ProjectObserver;
use Webkul\Project\Services\ComplexityScoreService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveAuthService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveFolderService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveSyncService;
use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Project Service Provider service provider
 *
 */
class ProjectServiceProvider extends PackageServiceProvider
{
    public static string $name = 'projects';

    /**
     * Configure Custom Package
     *
     * @param Package $package
     * @return void
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasDependencies([
                'sales',
            ])
            ->hasMigrations([
                '2024_12_12_074920_create_projects_project_stages_table',
                '2024_12_12_074929_create_projects_projects_table',
                '2024_12_12_074930_create_projects_milestones_table',
                '2024_12_12_100227_create_projects_user_project_favorites_table',
                '2024_12_12_100230_create_projects_tags_table',
                '2024_12_12_100232_create_projects_project_tag_table',
                '2024_12_12_101340_create_projects_task_stages_table',
                '2024_12_12_101344_create_projects_tasks_table',
                '2024_12_12_101350_create_projects_task_users_table',
                '2024_12_12_101352_create_projects_task_tag_table',
                '2024_12_18_145142_add_columns_to_analytic_records_table',
                '2025_09_24_062711_remove_tags_column_from_projects_tasks_table',
                '2025_09_30_222912_add_color_to_project_stages_table',
                '2025_10_01_153856_seed_tcs_workflow_stages',
                '2025_10_01_155056_create_projects_project_addresses_table',
                '2025_10_01_163524_add_project_type_to_projects_table',
                '2025_10_01_194346_add_use_customer_address_to_projects_table',
                '2025_10_01_195720_add_project_number_to_projects_table',
                '2025_10_02_142001_create_project_drafts_table',
                '2025_10_02_143140_add_metadata_columns_to_project_inspiration_images',
                '2025_10_02_155915_create_projects_production_estimates_table',
                '2025_10_02_182214_add_desired_completion_date_to_projects_table',
                '2025_10_02_182436_add_estimated_linear_feet_to_projects_table',
                '2025_10_03_174043_add_type_to_projects_tags_table',
                '2025_10_03_174043_5_add_description_to_projects_tags_table',
                '2025_10_03_174044_seed_tcs_project_tags',
                '2025_10_03_190205_seed_complete_tcs_tag_system',
                '2025_10_03_193000_fix_phase_tag_colors_to_match_stages',
                '2025_10_04_124625_create_projects_cabinet_specifications_table',
                '2025_10_06_000003_add_project_id_to_sales_orders',
                '2025_10_07_131429_add_attribute_selections_to_sales_order_lines',
                '2025_10_07_161656_create_projects_rooms_table',
                '2025_10_07_161931_create_projects_room_locations_table',
                '2025_10_07_161947_create_projects_cabinet_runs_table',
                '2025_10_07_162003_add_room_and_run_columns_to_projects_cabinet_specifications',
                '2025_10_07_193846_create_pdf_pages_table',
                '2025_10_07_194258_create_pdf_document_tag_pivot_table',
                '2025_10_07_200202_create_pdf_page_rooms_table',
                '2025_10_08_000001_create_pdf_page_annotations_table',
                '2025_10_08_173125_add_room_fields_to_pdf_page_annotations_table',
                '2025_10_13_173038_add_branch_id_to_projects_projects_table',
                '2025_10_20_162000_create_pdf_annotation_history_table',
                '2025_10_23_000001_add_view_types_and_multi_parent_support',
                '2025_10_24_214500_add_current_production_stage_to_projects_table',
                '2025_10_25_000001_add_production_fields_to_milestones',
                '2025_10_25_145337_create_projects_milestone_templates_table',
                '2025_10_26_000001_add_pricing_columns_to_projects_rooms_table',
                '2025_10_26_000002_add_woodworking_columns_to_projects_room_locations_table',
                '2025_10_26_000003_add_production_columns_to_projects_cabinet_runs_table',
                '2025_10_26_000004_add_detailed_specs_to_projects_cabinet_specifications_table',
                '2025_10_26_000008_create_cabinet_materials_bom_table',
                '2025_10_26_000009_create_hardware_requirements_table',
                '2025_10_28_115827_rename_cabinet_materials_bom_to_projects_bom',
                '2025_10_28_122833_make_product_variant_id_nullable_in_cabinet_specifications',
                '2025_10_28_130000_add_tcs_pricing_fields_to_project_entities',
                '2025_10_28_140000_create_tcs_material_inventory_mappings_table',
                '2025_10_29_111825_make_total_linear_feet_nullable_in_cabinet_runs_table',
                '2025_10_29_113649_make_cabinet_spec_dimensions_and_pricing_nullable',
                '2025_11_20_115206_add_hierarchy_columns_to_tasks_table',
                '2025_11_20_121238_add_search_indexes_to_hierarchy_tables',
                '2025_11_21_000001_create_projects_cabinet_sections_table',
                '2025_11_21_000002_create_projects_doors_table',
                '2025_11_21_000003_create_projects_drawers_table',
                '2025_11_21_000004_create_projects_shelves_table',
                '2025_11_21_000005_create_projects_pullouts_table',
                '2025_11_21_000006_add_section_and_component_to_projects_tasks_table',
                '2025_11_21_000007_add_product_links_to_cabinets_and_components',
                '2025_11_26_183527_add_timestamps_to_projects_project_tag_table',
                '2025_11_28_114137_add_warehouse_id_to_projects_table',
                '2025_11_28_114322_create_projects_material_reservations_table',
                '2025_11_28_114620_add_quote_tracking_columns',
                '2025_11_28_115000_add_inventory_stages_and_stage_keys',
                '2025_11_29_000001_rename_hardware_columns_to_generic',
                '2025_10_02_141007_create_project_inspiration_images_table',
                '2025_11_29_180000_add_room_id_and_title_to_project_inspiration_images',
                '2025_12_20_000001_create_projects_project_dependencies_table',
                '2025_12_24_100000_add_google_drive_columns_to_projects_table',
                '2025_12_31_175144_add_complexity_score_columns',
            ])
            ->runsMigrations()
            ->hasSettings([
                '2024_12_16_094021_create_project_task_settings',
                '2024_12_16_094021_create_project_time_settings',
                '2025_12_31_create_complexity_scoring_settings',
            ])
            ->runsSettings()
            ->hasSeeder('Webkul\\Project\\Database\Seeders\\DatabaseSeeder')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    /**
     * Package Booted
     *
     * @return void
     */
    public function packageBooted(): void
    {
        // Load views with webkul-project namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webkul-project');

        // Load translations with webkul-project namespace (overrides parent's 'projects' namespace)
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webkul-project');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        // Register Livewire components
        \Livewire\Livewire::component('annotation-editor', \Webkul\Project\Livewire\AnnotationEditor::class);
        \Livewire\Livewire::component('hierarchy-builder-modal', \Webkul\Project\Livewire\HierarchyBuilderModal::class);
        \Livewire\Livewire::component('milestone-timeline', \Webkul\Project\Livewire\MilestoneTimeline::class);
        \Livewire\Livewire::component('production-timeline', \Webkul\Project\Livewire\ProductionTimeline::class);
        \Livewire\Livewire::component('project-summary-sidebar', \Webkul\Project\Livewire\ProjectSummarySidebar::class);
        \Livewire\Livewire::component('inspiration-gallery', \Webkul\Project\Livewire\InspirationGalleryComponent::class);
        \Livewire\Livewire::component('pdf-document-manager', \Webkul\Project\Livewire\PdfDocumentManagerComponent::class);
        \Livewire\Livewire::component('cabinet-spec-builder', \Webkul\Project\Livewire\CabinetSpecBuilder::class);
        \Livewire\Livewire::component('cabinet-ai-assistant', \Webkul\Project\Livewire\CabinetAiAssistant::class);
        \Livewire\Livewire::component('project-gantt-chart', \Webkul\Project\Livewire\ProjectGanttChart::class);
        \Livewire\Livewire::component('quick-actions-panel', \Webkul\Project\Livewire\QuickActions\QuickActionsPanel::class);
        \Livewire\Livewire::component('project-data-cards', \Webkul\Project\Livewire\ProjectDataCards::class);
        \Livewire\Livewire::component('webkul-project::opening-configurator', \Webkul\Project\Livewire\OpeningConfigurator::class);
        \Livewire\Livewire::component('webkul-project::cabinet-configurator', \Webkul\Project\Livewire\CabinetConfigurator::class);
        // Old Livewire Kanban archived - now using mokhosh/filament-kanban package
        // \Livewire\Livewire::component('project-kanban-board', \Webkul\Project\Livewire\ProjectKanbanBoard::class);

        // Register event listeners for project stage changes
        Event::listen(ProjectStageChanged::class, HandleProjectStageChange::class);

        // Register model observers for auto-syncing hardware totals
        HardwareRequirement::observe(HardwareRequirementObserver::class);

        // Register Project observer for Google Drive folder creation
        Project::observe(ProjectObserver::class);

        // Register ComplexityScoreService as singleton
        $this->app->singleton(ComplexityScoreService::class);

        // Register complexity score observer for component models
        // This triggers cascade recalculation when components are modified
        Door::observe(ComplexityScoreObserver::class);
        Drawer::observe(ComplexityScoreObserver::class);
        Shelf::observe(ComplexityScoreObserver::class);
        Pullout::observe(ComplexityScoreObserver::class);

        // Register Google Drive services as singletons
        $this->app->singleton(GoogleDriveAuthService::class);
        $this->app->singleton(GoogleDriveFolderService::class, function ($app) {
            return new GoogleDriveFolderService($app->make(GoogleDriveAuthService::class));
        });
        $this->app->singleton(GoogleDriveSyncService::class, function ($app) {
            return new GoogleDriveSyncService(
                $app->make(GoogleDriveAuthService::class),
                $app->make(GoogleDriveFolderService::class)
            );
        });
        $this->app->singleton(GoogleDriveService::class, function ($app) {
            $service = new GoogleDriveService(
                $app->make(GoogleDriveAuthService::class),
                $app->make(GoogleDriveFolderService::class)
            );
            // Inject sync service after construction to avoid circular dependency
            $service->setSyncService($app->make(GoogleDriveSyncService::class));
            return $service;
        });

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupExpiredDrafts::class,
                RecalculateComplexityScores::class,
            ]);

            // Schedule the draft cleanup to run daily at 2:00 AM
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('projects:cleanup-drafts')
                    ->dailyAt('02:00')
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/draft-cleanup.log'));
            });
        }
    }
}
