<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Real TCS Data Seeder - Master Seeder
 *
 * Orchestrates import of all real TCS data from multiple sources:
 * 1. Notion CSV export (9 active projects) - NotionProjectsImportSeeder
 * 2. Google Drive folders (active + completed projects) - GoogleDriveProjectsSeeder
 * 3. Proposal HTML files (clients + pricing) - ProposalClientsSeeder
 * 4. Sample employee data (Aedan + others) - RealEmployeesSeeder
 *
 * Run with: php artisan db:seed --class=RealTCSDataSeeder
 *
 * Or run individual seeders:
 *   php artisan db:seed --class=NotionProjectsImportSeeder
 *   php artisan db:seed --class=GoogleDriveProjectsSeeder
 *   php artisan db:seed --class=ProposalClientsSeeder
 *   php artisan db:seed --class=RealEmployeesSeeder
 */
class RealTCSDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PRODUCTION GUARD - This seeder is for development/staging only
        if (app()->environment('production')) {
            $this->command->error('⛔ This seeder cannot run in production!');
            $this->command->error('   RealTCSDataSeeder is for development data only.');
            return;
        }

        $this->command->info("\n");
        $this->command->info("╔══════════════════════════════════════════════════════════════╗");
        $this->command->info("║        TCS WOODWORK - COMPREHENSIVE DATA IMPORT              ║");
        $this->command->info("╠══════════════════════════════════════════════════════════════╣");
        $this->command->info("║  This seeder imports REAL TCS data from multiple sources:    ║");
        $this->command->info("║                                                              ║");
        $this->command->info("║  1. Notion Export     - Active projects with delivery dates  ║");
        $this->command->info("║  2. Google Drive      - Local folder structure analysis      ║");
        $this->command->info("║  3. Proposals         - Real clients, pricing, project specs ║");
        $this->command->info("║  4. Employees         - Staff from sample PDFs               ║");
        $this->command->info("╚══════════════════════════════════════════════════════════════╝");
        $this->command->info("\n");

        // Track what was seeded
        $results = [];

        // 1. Import Notion projects (existing 9 projects)
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("  STEP 1/4: Notion Projects Import");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        try {
            $this->call(NotionProjectsImportSeeder::class);
            $results['Notion Projects'] = '✓ Success';
        } catch (\Exception $e) {
            $results['Notion Projects'] = '✗ Failed: ' . $e->getMessage();
            $this->command->error("Notion import failed: " . $e->getMessage());
        }

        // 2. Import Google Drive projects
        $this->command->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("  STEP 2/4: Google Drive Projects Import");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        try {
            $this->call(GoogleDriveProjectsSeeder::class);
            $results['Google Drive Projects'] = '✓ Success';
        } catch (\Exception $e) {
            $results['Google Drive Projects'] = '✗ Failed: ' . $e->getMessage();
            $this->command->error("Google Drive import failed: " . $e->getMessage());
        }

        // 3. Import proposal clients and projects
        $this->command->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("  STEP 3/4: Proposal Clients Import");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        try {
            $this->call(ProposalClientsSeeder::class);
            $results['Proposal Clients'] = '✓ Success';
        } catch (\Exception $e) {
            $results['Proposal Clients'] = '✗ Failed: ' . $e->getMessage();
            $this->command->error("Proposal import failed: " . $e->getMessage());
        }

        // 4. Import employees
        $this->command->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("  STEP 4/4: Real Employees Import");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        try {
            $this->call(RealEmployeesSeeder::class);
            $results['Employees'] = '✓ Success';
        } catch (\Exception $e) {
            $results['Employees'] = '✗ Failed: ' . $e->getMessage();
            $this->command->error("Employee import failed: " . $e->getMessage());
        }

        // Print final summary
        $this->printFinalSummary($results);
    }

    /**
     * Print final summary
     */
    protected function printFinalSummary(array $results): void
    {
        $this->command->info("\n");
        $this->command->info("╔══════════════════════════════════════════════════════════════╗");
        $this->command->info("║                    IMPORT SUMMARY                            ║");
        $this->command->info("╠══════════════════════════════════════════════════════════════╣");

        foreach ($results as $source => $status) {
            $line = sprintf("║  %-25s %s", $source . ":", $status);
            $padding = 64 - strlen($line);
            $this->command->info($line . str_repeat(' ', max(0, $padding)) . "║");
        }

        $this->command->info("╠══════════════════════════════════════════════════════════════╣");

        // Count totals from database
        $projectCount = \Webkul\Project\Models\Project::count();
        $partnerCount = \Webkul\Partner\Models\Partner::where('sub_type', 'customer')->count();
        $employeeCount = \Webkul\Employee\Models\Employee::count();
        $orderCount = \Webkul\Sale\Models\Order::count();

        $this->command->info("║                                                              ║");
        $this->command->info(sprintf("║  Total Projects:     %-5d                                  ║", $projectCount));
        $this->command->info(sprintf("║  Total Customers:    %-5d                                  ║", $partnerCount));
        $this->command->info(sprintf("║  Total Employees:    %-5d                                  ║", $employeeCount));
        $this->command->info(sprintf("║  Total Sales Orders: %-5d                                  ║", $orderCount));
        $this->command->info("║                                                              ║");
        $this->command->info("╚══════════════════════════════════════════════════════════════╝");
        $this->command->info("\n");

        $this->command->info("Data Sources Used:");
        $this->command->info("  • Notion CSV:    notion_import/extracted/content/Private & Shared/");
        $this->command->info("  • Google Drive:  /Users/andrewphan/tcsadmin/Google_Drive/Nantucket Projects/");
        $this->command->info("  • Proposals:     /Users/andrewphan/tcsadmin/templates/proposals/");
        $this->command->info("  • Employees:     sample/sample employee/*.pdf");
        $this->command->info("\n");
    }
}
