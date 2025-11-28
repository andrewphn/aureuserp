<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallAllPlugins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:all {--fresh : Wipe database and start fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all ERP plugins in the correct dependency order';

    /**
     * Plugin installation order (respects dependencies)
     *
     * IMPORTANT: Do NOT run erp:install separately!
     * This command handles the complete installation in the correct order.
     */
    protected $pluginOrder = [
        // Core ERP first
        'erp',

        // Foundation plugins (must come before products)
        'employees',
        'partners',
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
        'analytics',
        'blogs',
        'website',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting full ERP installation...');
        $this->newLine();

        // Check if already installed
        if (!$this->option('fresh') && $this->isAlreadyInstalled()) {
            $this->warn('⚠️  ERP appears to be already installed.');
            $this->warn('   Running individual plugin installs may cause dependency issues.');
            $this->newLine();

            if (!$this->confirm('Continue anyway?', false)) {
                $this->info('💡 Tip: Use --fresh flag for a clean installation:');
                $this->info('   php artisan install:all --fresh');
                return 0;
            }
            $this->newLine();
        }

        // Check if fresh install requested
        if ($this->option('fresh')) {
            $this->warn('⚠️  This will wipe your database!');
            if (!$this->confirm('Are you sure you want to continue?', false)) {
                $this->error('Installation cancelled.');
                return 1;
            }

            $this->info('🗑️  Wiping database...');
            $this->call('db:wipe', ['--force' => true]);
            $this->newLine();
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

                // Special handling for erp:install when running fresh
                $params = ['--no-interaction' => true];
                if ($plugin === 'erp' && $this->option('fresh')) {
                    $params['--force'] = true;
                    $params['--admin-name'] = 'Admin';
                    $params['--admin-email'] = 'info@tcswoodwork.com';
                    $params['--admin-password'] = 'Lola2024!';
                }

                $exitCode = $this->call($command, $params);

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
     * Check if ERP is already installed
     */
    protected function isAlreadyInstalled(): bool
    {
        try {
            // Check if migrations table exists and has entries
            return \DB::table('migrations')->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
