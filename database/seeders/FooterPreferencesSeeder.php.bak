<?php

namespace Database\Seeders;

use App\Models\FooterPreference;
use App\Services\FooterPreferenceService;
use Illuminate\Database\Seeder;
use Webkul\Security\Models\User;

class FooterPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder sets up default footer preferences for all existing users.
     * It can optionally apply persona templates based on user roles.
     */
    public function run(): void
    {
        $service = app(FooterPreferenceService::class);
        $users = User::all();

        $this->command->info("Setting up footer preferences for {$users->count()} users...");

        $contexts = ['project', 'sale', 'inventory', 'production'];

        foreach ($users as $user) {
            $this->command->info("  Processing user: {$user->name} ({$user->email})");

            // Check if user already has preferences
            $existingPrefs = FooterPreference::where('user_id', $user->id)->count();

            if ($existingPrefs > 0) {
                $this->command->warn("    User already has {$existingPrefs} preferences, skipping...");
                continue;
            }

            // Apply persona template based on user role (if available)
            $persona = $this->detectPersona($user);

            if ($persona) {
                $this->command->info("    Applying '{$persona}' persona template");
                $service->applyPersonaTemplate($user, $persona);
            } else {
                // Apply default preferences for all contexts
                $this->command->info('    Applying default preferences');
                foreach ($contexts as $context) {
                    $service->resetToDefaults($user, $context);
                }
            }

            $this->command->info("    ✓ Preferences created for {$user->name}");
        }

        $this->command->info("\n✓ Footer preferences seeding completed!");
        $this->command->info('Total preferences created: ' . FooterPreference::count());
    }

    /**
     * Detect persona based on user attributes.
     * This can be customized based on your user roles/permissions system.
     */
    protected function detectPersona(User $user): ?string
    {
        // Example: detect based on email, role, or other attributes
        // Customize this based on your actual user role system

        $email = strtolower($user->email);
        $name = strtolower($user->name);

        // Owner/Admin detection
        if (str_contains($email, 'owner') ||
            str_contains($email, 'bryan') ||
            str_contains($name, 'owner') ||
            str_contains($name, 'bryan')) {
            return 'owner';
        }

        // Project Manager detection
        if (str_contains($email, 'pm') ||
            str_contains($email, 'project') ||
            str_contains($email, 'david') ||
            str_contains($name, 'project manager') ||
            str_contains($name, 'david')) {
            return 'project_manager';
        }

        // Sales detection
        if (str_contains($email, 'sales') ||
            str_contains($email, 'trott') ||
            str_contains($name, 'sales') ||
            str_contains($name, 'trott')) {
            return 'sales';
        }

        // Shop/Inventory detection
        if (str_contains($email, 'shop') ||
            str_contains($email, 'inventory') ||
            str_contains($email, 'ricky') ||
            str_contains($name, 'shop') ||
            str_contains($name, 'inventory') ||
            str_contains($name, 'ricky')) {
            return 'inventory';
        }

        // If you have a roles table, you can also check:
        // if ($user->hasRole('owner')) return 'owner';
        // if ($user->hasRole('project_manager')) return 'project_manager';
        // etc.

        // Default: return null to use system defaults
        return null;
    }
}
