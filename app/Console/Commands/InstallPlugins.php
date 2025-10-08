<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallPlugins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:plugins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all ERP plugins (run after erp:install)';

    /**
     * Plugin installation order (respects dependencies)
     * 
     * Note: Run erp:install first to set up core tables and users
     */
    protected $pluginOrder = [
        // Foundation plugins (must come before products)
        'employees',
        'contacts',

        // Product & Inventory (required by sales)
        'products',
        'inventories',

        // Accounting (required by sales)
        'accounts',
        'payments',

        // Sales & Invoicing (required by projects)
        'invoices',
        'sales',

        // Projects (depends on sales, products, accounts)
        // PDF migrations are in projects plugin
        'projects',

        // Supporting modules
        'timesheets',
        'time-off',
        'purchases',
        'recruitments',
        'blogs',
        'website',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Installing ERP plugins...');
        $this->newLine();

        // Check if erp is installed
        if (!$this->isErpInstalled()) {
            $this->error('❌ ERP core is not installed. Please run erp:install first.');
            $this->newLine();
            $this->info('💡 Tip: Run erp:install before installing plugins:');
            $this->info('   php artisan erp:install');
            return 1;
        }

        $installed = [];
        $failed = [];
        $skipped = [];

        foreach ($this->pluginOrder as $plugin) {
            $command = "{$plugin}:install";

            $this->info("📦 Installing {$plugin}...");

            try {
                // Check if command exists
                if (!$this->commandExists($command)) {
                    $this->warn("   ⊘ Command '{$command}' not found, skipping...");
                    $skipped[] = $plugin;
                    continue;
                }

                $exitCode = $this->call($command, [
                    '--no-interaction' => true,
                ]);

                if ($exitCode === 0) {
                    $this->info("   ✅ {$plugin} installed successfully");
                    $installed[] = $plugin;
                } else {
                    $this->error("   ❌ {$plugin} installation failed");
                    $failed[] = $plugin;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ {$plugin} installation error: " . $e->getMessage());
                $failed[] = $plugin;
            }

            $this->newLine();
        }

        // Summary
        $this->info('📊 Installation Summary:');
        $this->table(
            ['Status', 'Count', 'Plugins'],
            [
                ['✅ Installed', count($installed), implode(', ', $installed)],
                ['❌ Failed', count($failed), implode(', ', $failed) ?: 'None'],
                ['⊘ Skipped', count($skipped), implode(', ', $skipped) ?: 'None'],
            ]
        );

        if (count($failed) > 0) {
            $this->error('Some plugins failed to install. Please check the errors above.');
            return 1;
        }

        $this->newLine();
        $this->info('🎉 All plugins installed successfully!');
        $this->newLine();
        $this->info('💡 Note: Core plugins (partners, analytics) are installed with erp:install');

        return 0;
    }

    /**
     * Check if artisan command exists
     */
    protected function commandExists(string $command): bool
    {
        try {
            $commands = array_keys($this->getApplication()->all());
            return in_array($command, $commands);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if ERP is installed
     */
    protected function isErpInstalled(): bool
    {
        try {
            // Check if migrations table exists and has core ERP migrations
            return \DB::table('migrations')->where('migration', 'like', '%create_users_table%')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }
}
