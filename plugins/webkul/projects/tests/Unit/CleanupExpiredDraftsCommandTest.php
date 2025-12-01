<?php

namespace Webkul\Project\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\ProjectDraft;
use Illuminate\Support\Facades\Artisan;

/**
 * Unit tests for CleanupExpiredDrafts Artisan command
 *
 * Uses DatabaseTransactions instead of RefreshDatabase to avoid
 * full migration resets on each test.
 */
class CleanupExpiredDraftsCommandTest extends TestCase
{
    use DatabaseTransactions;
    /** @test */
    public function it_is_registered_as_artisan_command()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('projects:cleanup-drafts', $commands);
    }

    /** @test */
    public function it_deletes_expired_drafts()
    {
        // Create expired drafts
        ProjectDraft::factory()->expired()->count(3)->create();

        // Create active drafts (should not be deleted)
        ProjectDraft::factory()->active()->count(2)->create();

        // Assert initial count
        $this->assertEquals(5, ProjectDraft::count());

        // Run command
        $this->artisan('projects:cleanup-drafts')
            ->expectsOutput('Starting project draft cleanup...')
            ->expectsOutput('Successfully deleted 3 expired draft(s).')
            ->assertExitCode(0);

        // Assert only active drafts remain
        $this->assertEquals(2, ProjectDraft::count());
    }

    /** @test */
    public function it_reports_when_no_expired_drafts_found()
    {
        // Create only active drafts
        ProjectDraft::factory()->active()->count(2)->create();

        // Run command
        $this->artisan('projects:cleanup-drafts')
            ->expectsOutput('Starting project draft cleanup...')
            ->expectsOutput('No expired drafts found. Database is clean!')
            ->assertExitCode(0);

        // Assert no drafts deleted
        $this->assertEquals(2, ProjectDraft::count());
    }

    /** @test */
    public function it_supports_dry_run_option()
    {
        // Create expired drafts
        ProjectDraft::factory()->expired()->count(3)->create();

        // Run command with --dry-run
        $this->artisan('projects:cleanup-drafts', ['--dry-run' => true])
            ->expectsOutput('Starting project draft cleanup...')
            ->expectsOutputToContain('[DRY RUN] Would delete 3 expired draft(s):')
            ->assertExitCode(0);

        // Assert no drafts were actually deleted
        $this->assertEquals(3, ProjectDraft::count());
    }

    /** @test */
    public function it_supports_older_than_option()
    {
        // Create old drafts (30 days old)
        ProjectDraft::factory()->old(30)->count(2)->create();

        // Create recent drafts (5 days old)
        ProjectDraft::factory()->old(5)->count(2)->create();

        // Run command with --older-than=20
        $this->artisan('projects:cleanup-drafts', ['--older-than' => 20])
            ->expectsOutput('Starting project draft cleanup...')
            ->expectsOutput('Successfully deleted 2 expired draft(s).')
            ->assertExitCode(0);

        // Assert only recent drafts remain
        $this->assertEquals(2, ProjectDraft::count());
    }

    /** @test */
    public function it_deletes_recently_expired_drafts()
    {
        // Create draft that expired just 2 hours ago
        ProjectDraft::factory()->recentlyExpired()->create();

        // Run command
        $this->artisan('projects:cleanup-drafts')
            ->expectsOutput('Successfully deleted 1 expired draft(s).')
            ->assertExitCode(0);

        $this->assertEquals(0, ProjectDraft::count());
    }

    /** @test */
    public function it_does_not_delete_drafts_without_expiration()
    {
        // Create drafts without expiration set
        ProjectDraft::factory()->noExpiration()->count(2)->create();

        // Run command
        $this->artisan('projects:cleanup-drafts')
            ->expectsOutput('No expired drafts found. Database is clean!')
            ->assertExitCode(0);

        // Drafts without expiration should remain
        $this->assertEquals(2, ProjectDraft::count());
    }

    /** @test */
    public function dry_run_shows_draft_details_in_table()
    {
        // Create expired draft with known user
        $draft = ProjectDraft::factory()->expired()->create([
            'user_id' => 1, // Admin user created in setUp
            'current_step' => 2,
        ]);

        // Run command with --dry-run
        $this->artisan('projects:cleanup-drafts', ['--dry-run' => true])
            ->expectsTable(
                ['ID', 'User', 'Created', 'Expires', 'Step'],
                [[$draft->id, 'Admin', $draft->created_at->toDateTimeString(), $draft->expires_at->toDateTimeString(), 2]]
            )
            ->assertExitCode(0);
    }
}
