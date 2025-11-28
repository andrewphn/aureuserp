<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Install All Plugins class
 *
 * Combines erp:install + plugins:install for a complete ERP installation.
 */
class InstallAllPlugins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:all {--fresh : Wipe database and start fresh} {--force : Force reseed all plugins without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ERP core and all plugins (erp:install + plugins:install)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting full ERP installation...');
        $this->newLine();

        // Check if fresh install requested
        if ($this->option('fresh')) {
            // Skip confirmation if --force is used
            if (!$this->option('force')) {
                $this->warn('⚠️  This will wipe your database!');
                if (!$this->confirm('Are you sure you want to continue?', false)) {
                    $this->error('Installation cancelled.');
                    return 1;
                }
            }

            $this->info('🗑️  Wiping database...');
            $this->call('db:wipe', ['--force' => true]);
            $this->newLine();
        }

        // Step 1: Install ERP core
        $this->info('📦 Step 1: Installing ERP core...');
        $this->newLine();

        $erpParams = [
            '--admin-name' => 'Admin',
            '--admin-email' => 'info@tcswoodwork.com',
            '--admin-password' => 'Lola2024!',
        ];

        if ($this->option('force')) {
            $erpParams['--force'] = true;
        }

        $exitCode = $this->call('erp:install', $erpParams);

        if ($exitCode !== 0) {
            $this->error('❌ ERP core installation failed');
            return 1;
        }

        $this->info('✅ ERP core installed successfully');
        $this->newLine();

        // Step 2: Install all plugins
        $this->info('📦 Step 2: Installing plugins...');
        $this->newLine();

        $pluginsParams = [];
        if ($this->option('force')) {
            $pluginsParams['--force'] = true;
        }

        $exitCode = $this->call('plugins:install', $pluginsParams);

        if ($exitCode !== 0) {
            $this->error('❌ Some plugins failed to install');
            return 1;
        }

        $this->newLine();
        $this->info('🎉 Full ERP installation completed successfully!');

        return 0;
    }
}
