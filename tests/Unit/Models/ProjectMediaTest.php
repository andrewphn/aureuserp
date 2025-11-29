<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Webkul\Project\Models\Project;

/**
 * Unit tests for Project model media collections
 *
 * Tests the Spatie Media Library integration on the Project model,
 * verifying that interfaces and traits are properly implemented.
 *
 * Note: These are pure unit tests that don't require database.
 * For integration tests with file uploads, use the test database.
 */
class ProjectMediaTest extends TestCase
{
    /** @test */
    public function project_implements_has_media_interface(): void
    {
        $interfaces = class_implements(Project::class);

        $this->assertContains(
            \Spatie\MediaLibrary\HasMedia::class,
            $interfaces,
            'Project should implement HasMedia interface'
        );
    }

    /** @test */
    public function project_uses_interacts_with_media_trait(): void
    {
        $traits = class_uses_recursive(Project::class);

        $this->assertContains(
            \Spatie\MediaLibrary\InteractsWithMedia::class,
            $traits,
            'Project should use InteractsWithMedia trait'
        );
    }

    /** @test */
    public function project_has_register_media_collections_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'registerMediaCollections'),
            'Project should have registerMediaCollections method'
        );
    }

    /** @test */
    public function project_has_register_media_conversions_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'registerMediaConversions'),
            'Project should have registerMediaConversions method'
        );
    }

    /** @test */
    public function project_class_has_media_relationship_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'media'),
            'Project should have media relationship method'
        );
    }

    /** @test */
    public function project_class_has_add_media_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'addMedia'),
            'Project should have addMedia method from InteractsWithMedia trait'
        );
    }

    /** @test */
    public function project_class_has_get_media_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'getMedia'),
            'Project should have getMedia method from InteractsWithMedia trait'
        );
    }

    /** @test */
    public function project_class_has_clear_media_collection_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'clearMediaCollection'),
            'Project should have clearMediaCollection method'
        );
    }

    /** @test */
    public function project_class_has_get_first_media_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'getFirstMedia'),
            'Project should have getFirstMedia method'
        );
    }

    /** @test */
    public function project_class_has_get_first_media_url_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'getFirstMediaUrl'),
            'Project should have getFirstMediaUrl method'
        );
    }

    /** @test */
    public function project_class_has_add_media_from_url_method(): void
    {
        $this->assertTrue(
            method_exists(Project::class, 'addMediaFromUrl'),
            'Project should have addMediaFromUrl method'
        );
    }
}
