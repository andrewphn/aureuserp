<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample Data Cleanup Seeder
 *
 * Removes ALL sample data created by the TCS seeders.
 * Use this to undo seeding on staging or reset to clean state.
 *
 * WHAT GETS REMOVED:
 * - Projects with TFW-* or TCS-* project numbers (sample projects)
 * - CNC programs and parts linked to sample projects
 * - Tasks linked to sample projects
 * - Sales orders linked to sample projects
 * - Sample customers (emails ending in @project.tcswoodwork.com)
 * - Sample material products (SKU starting with TCS-MAT-*)
 * - Sample project tags
 *
 * WHAT IS PRESERVED:
 * - Real inventory data
 * - Real customers
 * - System configuration
 * - Employees
 * - Material mappings (links are cleared, not deleted)
 *
 * Run with: php artisan db:seed --class=SampleDataCleanupSeeder
 */
class SampleDataCleanupSeeder extends Seeder
{
    /**
     * Sample data identifiers
     */
    protected array $sampleProjectPrefixes = ['TFW-', 'TCS-0'];
    protected string $sampleCustomerEmailPattern = '%@project.tcswoodwork.com';
    protected string $sampleProductSkuPattern = 'TCS-MAT-%';

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║        SAMPLE DATA CLEANUP - REMOVING SEEDED DATA         ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');
        $this->command->warn('');
        $this->command->warn('This will remove ALL sample data created by TCS seeders.');
        $this->command->warn('');

        // Count what will be removed
        $this->showPreview();

        // Confirm before proceeding
        if (!$this->command->confirm('Do you want to proceed with cleanup?', false)) {
            $this->command->info('Cleanup cancelled.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->removeSampleProjects();
            $this->removeSampleCustomers();
            $this->removeSampleProducts();
            $this->removeSampleTags();
            $this->clearMaterialMappings();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->command->info('');
            $this->command->info('╔═══════════════════════════════════════════════════════════╗');
            $this->command->info('║              CLEANUP COMPLETE                             ║');
            $this->command->info('╚═══════════════════════════════════════════════════════════╝');

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->command->error('Cleanup failed: ' . $e->getMessage());
        }
    }

    protected function showPreview(): void
    {
        $this->command->info('Preview of data to be removed:');

        // Sample projects
        $projectCount = $this->getSampleProjectQuery()->count();
        $this->command->line("  Projects:       {$projectCount}");

        // CNC Programs
        $projectIds = $this->getSampleProjectQuery()->pluck('id');
        $cncCount = DB::table('projects_cnc_programs')->whereIn('project_id', $projectIds)->count();
        $this->command->line("  CNC Programs:   {$cncCount}");

        // Tasks
        $taskCount = DB::table('projects_tasks')->whereIn('project_id', $projectIds)->count();
        $this->command->line("  Tasks:          {$taskCount}");

        // Sales orders
        $orderCount = DB::table('sales_orders')->whereIn('project_id', $projectIds)->count();
        $this->command->line("  Sales Orders:   {$orderCount}");

        // Sample customers
        $customerCount = DB::table('partners_partners')
            ->where('email', 'like', $this->sampleCustomerEmailPattern)
            ->count();
        $this->command->line("  Customers:      {$customerCount}");

        // Sample products
        $productCount = DB::table('products_products')
            ->where('reference', 'like', $this->sampleProductSkuPattern)
            ->count();
        $this->command->line("  Products:       {$productCount}");

        $this->command->info('');
    }

    protected function getSampleProjectQuery()
    {
        return DB::table('projects_projects')
            ->where(function ($query) {
                foreach ($this->sampleProjectPrefixes as $prefix) {
                    $query->orWhere('project_number', 'like', $prefix . '%');
                }
            });
    }

    protected function removeSampleProjects(): void
    {
        $this->command->info('Removing sample projects...');

        $projectIds = $this->getSampleProjectQuery()->pluck('id')->toArray();

        if (empty($projectIds)) {
            $this->command->line('  No sample projects found');
            return;
        }

        // Remove CNC parts
        $programIds = DB::table('projects_cnc_programs')->whereIn('project_id', $projectIds)->pluck('id');
        $partsDeleted = DB::table('projects_cnc_program_parts')->whereIn('cnc_program_id', $programIds)->delete();
        $this->command->line("  CNC Parts deleted: {$partsDeleted}");

        // Remove CNC programs
        $programsDeleted = DB::table('projects_cnc_programs')->whereIn('project_id', $projectIds)->delete();
        $this->command->line("  CNC Programs deleted: {$programsDeleted}");

        // Remove tasks
        $tasksDeleted = DB::table('projects_tasks')->whereIn('project_id', $projectIds)->delete();
        $this->command->line("  Tasks deleted: {$tasksDeleted}");

        // Remove milestones
        $milestonesDeleted = DB::table('projects_milestones')->whereIn('project_id', $projectIds)->delete();
        $this->command->line("  Milestones deleted: {$milestonesDeleted}");

        // Remove change orders
        $changeOrdersDeleted = DB::table('projects_change_orders')->whereIn('project_id', $projectIds)->delete();
        $this->command->line("  Change Orders deleted: {$changeOrdersDeleted}");

        // Remove sales order lines and orders
        $orderIds = DB::table('sales_orders')->whereIn('project_id', $projectIds)->pluck('id');
        $linesDeleted = DB::table('sales_order_lines')->whereIn('order_id', $orderIds)->delete();
        $this->command->line("  Order Lines deleted: {$linesDeleted}");

        $ordersDeleted = DB::table('sales_orders')->whereIn('project_id', $projectIds)->delete();
        $this->command->line("  Sales Orders deleted: {$ordersDeleted}");

        // Remove projects
        $projectsDeleted = DB::table('projects_projects')->whereIn('id', $projectIds)->delete();
        $this->command->line("  Projects deleted: {$projectsDeleted}");
    }

    protected function removeSampleCustomers(): void
    {
        $this->command->info('Removing sample customers...');

        $deleted = DB::table('partners_partners')
            ->where('email', 'like', $this->sampleCustomerEmailPattern)
            ->delete();

        $this->command->line("  Customers deleted: {$deleted}");
    }

    protected function removeSampleProducts(): void
    {
        $this->command->info('Removing sample material products...');

        $deleted = DB::table('products_products')
            ->where('reference', 'like', $this->sampleProductSkuPattern)
            ->delete();

        $this->command->line("  Products deleted: {$deleted}");
    }

    protected function removeSampleTags(): void
    {
        $this->command->info('Removing sample project tags...');

        // Remove tag associations first
        $tagIds = DB::table('projects_tags')->pluck('id');

        if (DB::getSchemaBuilder()->hasTable('projects_project_tag')) {
            $pivotDeleted = DB::table('projects_project_tag')->whereIn('tag_id', $tagIds)->delete();
            $this->command->line("  Tag associations deleted: {$pivotDeleted}");
        }

        // Note: We don't delete the tags themselves as they may be used by real projects
        $this->command->line("  Tags preserved (may be used by real projects)");
    }

    protected function clearMaterialMappings(): void
    {
        $this->command->info('Clearing material mapping product links...');

        // Get sample product IDs
        $productIds = DB::table('products_products')
            ->where('reference', 'like', $this->sampleProductSkuPattern)
            ->pluck('id');

        // Clear the links but don't delete the mappings
        $updated = DB::table('tcs_material_inventory_mappings')
            ->whereIn('inventory_product_id', $productIds)
            ->update(['inventory_product_id' => null]);

        $this->command->line("  Mapping links cleared: {$updated}");
    }
}
