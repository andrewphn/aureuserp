<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearLoginRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:clear-rate-limit {email? : Specific email to clear rate limit for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear login rate limit cache for all users or a specific email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if ($email) {
            // Clear rate limit for specific email
            $this->clearRateLimitForEmail($email);
            $this->info("Rate limit cleared for: {$email}");
        } else {
            // Clear all login rate limits
            $this->clearAllRateLimits();
            $this->info('All login rate limits cleared');
        }

        return Command::SUCCESS;
    }

    /**
     * Clear rate limit for a specific email
     */
    protected function clearRateLimitForEmail(string $email): void
    {
        // LivewireRateLimiting uses cache keys like: livewire-rate-limit:{component}:{identifier}
        // For login, the identifier is typically the email or IP
        $patterns = [
            "livewire-rate-limit:*:{$email}",
            "livewire-rate-limit:*:*{$email}*",
            "livewire-rate-limit:filament.*login*:{$email}",
            "livewire-rate-limit:filament.*login*:*{$email}*",
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Clear all login rate limits
     */
    protected function clearAllRateLimits(): void
    {
        // Clear all Livewire rate limit cache entries
        $patterns = [
            'livewire-rate-limit:*',
            'livewire-rate-limit:filament.*login*:*',
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Clear cache entries matching a pattern
     * Note: This is a simplified approach - Laravel cache doesn't support wildcard deletion
     * In production, you might need to use Redis SCAN or flush the entire cache
     */
    protected function clearCachePattern(string $pattern): void
    {
        // If using Redis, we can use SCAN to find matching keys
        if (config('cache.default') === 'redis') {
            $redis = Cache::getStore()->getRedis();
            $keys = [];
            $cursor = 0;

            do {
                [$cursor, $foundKeys] = $redis->scan($cursor, ['match' => $pattern, 'count' => 100]);
                $keys = array_merge($keys, $foundKeys);
            } while ($cursor !== 0);

            if (!empty($keys)) {
                $redis->del($keys);
            }
        } else {
            // For other cache drivers, flush the entire cache
            // This is less precise but will work
            Cache::flush();
        }
    }
}
