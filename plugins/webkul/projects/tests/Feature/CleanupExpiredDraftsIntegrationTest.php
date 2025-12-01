<?php

namespace Webkul\Project\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\ProjectDraft;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use Carbon\Carbon;

/**
 * Integration tests for CleanupExpiredDrafts scheduled task
 *
 * Uses DatabaseTransactions instead of RefreshDatabase to avoid
 * full migration resets on each test.
 */
class CleanupExpiredDraftsIntegrationTest extends TestCase
{
    use DatabaseTransactions;
    /** @test */
    public function it_is_scheduled_to_run_daily_at_2am()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $cleanupEvent = $events->first(function ($event) {
            return str_contains($event->command, 'projects:cleanup-drafts');
        });

        $this->assertNotNull($cleanupEvent, 'Cleanup command should be scheduled');
        $this->assertEquals('0 2 * * *', $cleanupEvent->expression);
    }

    /** @test */
    public function it_handles_large_batch_of_expired_drafts()
    {
        // Create 100 expired drafts to test batch deletion
        ProjectDraft::factory()->expired()->count(100)->create();

        // Create 10 active drafts
        ProjectDraft::factory()->active()->count(10)->create();

        $this->assertEquals(110, ProjectDraft::count());

        // Run command
        $exitCode = Artisan::call('projects:cleanup-drafts');

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(10, ProjectDraft::count());
    }

    /** @test */
    public function it_preserves_draft_form_data_for_active_drafts()
    {
        $formData = [
            'project_type' => 'residential',
            'partner_id' => 123,
            'city' => 'Nantucket',
            'budget_range' => '100k-150k',
        ];

        // Create active draft with specific form data
        $draft = ProjectDraft::factory()->active()->create([
            'form_data' => $formData,
        ]);

        // Create expired drafts
        ProjectDraft::factory()->expired()->count(5)->create();

        // Run cleanup
        Artisan::call('projects:cleanup-drafts');

        // Verify active draft still exists with intact data
        $draft->refresh();
        $this->assertEquals($formData, $draft->form_data);
    }

    /** @test */
    public function it_correctly_handles_mixed_expiration_states()
    {
        // Expired yesterday
        ProjectDraft::factory()->create(['expires_at' => now()->subDay()]);

        // Expires tomorrow
        ProjectDraft::factory()->create(['expires_at' => now()->addDay()]);

        // Expired 1 week ago
        ProjectDraft::factory()->create(['expires_at' => now()->subWeek()]);

        // No expiration set (should not be deleted)
        ProjectDraft::factory()->create(['expires_at' => null]);

        // Expires in 1 week
        ProjectDraft::factory()->create(['expires_at' => now()->addWeek()]);

        $this->assertEquals(5, ProjectDraft::count());

        // Run cleanup
        Artisan::call('projects:cleanup-drafts');

        // Should delete 2 (expired yesterday, expired 1 week ago)
        // Should keep 3 (expires tomorrow, no expiration, expires in 1 week)
        $this->assertEquals(3, ProjectDraft::count());
    }

    /** @test */
    public function it_handles_edge_case_of_just_expired_draft()
    {
        // Create draft that expired exactly now
        ProjectDraft::factory()->create(['expires_at' => now()]);

        // This should be deleted as it's past expiration
        Carbon::setTestNow(now()->addSecond());

        Artisan::call('projects:cleanup-drafts');

        $this->assertEquals(0, ProjectDraft::count());

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function older_than_option_works_with_active_drafts()
    {
        // Create drafts at different ages
        $oldDraft = ProjectDraft::factory()->create([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
            'expires_at' => now()->addDays(10), // Still "active" by expires_at
        ]);

        $recentDraft = ProjectDraft::factory()->create([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
            'expires_at' => now()->addDays(10),
        ]);

        // Run with --older-than=30 (should delete old draft despite expires_at)
        Artisan::call('projects:cleanup-drafts', ['--older-than' => 30]);

        $this->assertEquals(1, ProjectDraft::count());
        $this->assertTrue(ProjectDraft::find($recentDraft->id) !== null);
        $this->assertNull(ProjectDraft::find($oldDraft->id));
    }

    /** @test */
    public function it_runs_without_errors_on_empty_database()
    {
        $this->assertEquals(0, ProjectDraft::count());

        $exitCode = Artisan::call('projects:cleanup-drafts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No expired drafts found', Artisan::output());
    }

    /** @test */
    public function dry_run_respects_limit_on_table_output()
    {
        // Create 25 expired drafts
        ProjectDraft::factory()->expired()->count(25)->create();

        // Run with --dry-run
        $exitCode = Artisan::call('projects:cleanup-drafts', ['--dry-run' => true]);

        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('[DRY RUN] Would delete 25 expired draft(s)', $output);
        $this->assertStringContainsString('... and 5 more', $output);

        // Verify nothing was deleted
        $this->assertEquals(25, ProjectDraft::count());
    }

    /** @test */
    public function command_returns_correct_exit_codes()
    {
        // Success with deletions
        ProjectDraft::factory()->expired()->count(3)->create();
        $this->assertEquals(0, Artisan::call('projects:cleanup-drafts'));

        // Success with no deletions
        $this->assertEquals(0, Artisan::call('projects:cleanup-drafts'));

        // Success with dry run
        ProjectDraft::factory()->expired()->count(2)->create();
        $this->assertEquals(0, Artisan::call('projects:cleanup-drafts', ['--dry-run' => true]));
    }

    /** @test */
    public function it_can_be_run_multiple_times_safely()
    {
        ProjectDraft::factory()->expired()->count(5)->create();
        ProjectDraft::factory()->active()->count(3)->create();

        // First run
        Artisan::call('projects:cleanup-drafts');
        $this->assertEquals(3, ProjectDraft::count());

        // Second run (should find nothing to delete)
        Artisan::call('projects:cleanup-drafts');
        $this->assertEquals(3, ProjectDraft::count());

        // Add more expired drafts
        ProjectDraft::factory()->expired()->count(2)->create();

        // Third run
        Artisan::call('projects:cleanup-drafts');
        $this->assertEquals(3, ProjectDraft::count());
    }
}
