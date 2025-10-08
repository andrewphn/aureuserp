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
     */
    protected $pluginOrder = [
        // Core ERP first
        'erp',

        // Foundation plugins
        'employees',
        'partners',
        'contacts',

        // Product & Inventory
        'products',
        'inventories',

        // Accounting
        'accounts',
        'payments',

        // Sales & Invoicing
        'invoices',
        'sales',

        // Projects (depends on sales)
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
}
