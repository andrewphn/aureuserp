<?php

namespace Webkul\Project;

use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

class ProjectServiceProvider extends PackageServiceProvider
{
    public static string $name = 'projects';

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
                '2025_10_07_161656_create_projects_rooms_table',
                '2025_10_07_161931_create_projects_room_locations_table',
                '2025_10_07_161947_create_projects_cabinet_runs_table',
                '2025_10_07_162003_add_room_and_run_columns_to_projects_cabinet_specifications',
                '2025_10_06_000003_add_project_id_to_sales_orders',
                '2025_10_07_131429_add_attribute_selections_to_sales_order_lines',
                '2025_10_07_193846_create_pdf_pages_table',
                '2025_10_07_194258_create_pdf_document_tag_pivot_table',
                '2025_10_07_200202_create_pdf_page_rooms_table',
                '2025_10_08_000001_create_pdf_page_annotations_table',
                '2025_10_08_173125_add_room_fields_to_pdf_page_annotations_table',
                '2025_10_08_180053_add_versioning_to_pdf_documents_table',
            ])
            ->runsMigrations()
            ->hasSettings([
                '2024_12_16_094021_create_project_task_settings',
                '2024_12_16_094021_create_project_time_settings',
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

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webkul-project');

        // Register Livewire components
        \Livewire\Livewire::component('annotation-editor', \Webkul\Project\Livewire\AnnotationEditor::class);
    }
}
