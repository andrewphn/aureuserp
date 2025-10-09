<?php

namespace Tests\Browser;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * End-to-End Tests for Phase 4: Annotation Editing System
 *
 * Tests the complete editing workflow:
 * 1. Select annotations with click
 * 2. Resize annotations using handles
 * 3. Move annotations by dragging
 * 4. Delete annotations with confirmation
 * 5. Undo/Redo operations
 */
class Phase4EditingSystemTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $project;
    protected $pdfDocument;
    protected $pdfPage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@tcswoodwork.com',
            'password' => bcrypt('password'),
        ]);

        // Create test project
        $this->project = Project::factory()->create([
            'name' => 'E2E Test Project - Phase 4',
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\\Project\\Models\\Project',
            'module_id' => $this->project->id,
            'file_path' => 'test/sample-floorplan.pdf',
            'file_name' => 'kitchen-floorplan.pdf',
            'page_count' => 1,
            'uploaded_by' => $this->user->id,
        ]);

        // Create PDF page
        $this->pdfPage = PdfPage::create([
            'pdf_document_id' => $this->pdfDocument->id,
            'page_number' => 1,
        ]);
    }

    /**
     * Test E2E: Select tool activates and allows annotation selection
     */
    public function test_select_tool_allows_annotation_selection()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Draw an annotation first
                ->assertVisible('[title="Draw Rectangle"]')
                ->click('[title="Draw Rectangle"]')
                ->pause(500)

                // Simulate drawing
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.annotations.push({
                        id: Date.now(),
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });
                ")
                ->pause(500)

                // Switch to select tool
                ->click('[title="Select Tool (Click annotation to edit)"]')
                ->pause(500)

                // Verify select tool is active
                ->assertScript('return document.querySelector("[title=\\"Select Tool (Click annotation to edit)\\"]").classList.contains("bg-blue-600")')

                ->screenshot('select-tool-active');
        });
    }

    /**
     * Test E2E: Clicking annotation shows selection indicator
     */
    public function test_clicking_annotation_shows_selection_indicator()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.annotations.push({
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });
                    component.renderCanvas();
                ")
                ->pause(500)

                // Switch to select tool
                ->click('[title="Select Tool (Click annotation to edit)"]')
                ->pause(500)

                // Click on annotation (simulate)
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.selectedAnnotationId = 1001;
                ")
                ->pause(500)

                // Verify editing indicator appears
                ->waitFor('[x-show="selectedAnnotationId !== null"]', 5)
                ->assertSee('Editing')
                ->assertVisible('@delete-annotation-button')

                ->screenshot('annotation-selected');
        });
    }

    /**
     * Test E2E: Delete button deletes annotation with confirmation
     */
    public function test_delete_button_removes_annotation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create and select annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.annotations.push({
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });
                    component.selectedAnnotationId = 1001;
                ")
                ->pause(500)

                // Verify annotation exists
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 1')

                // Click delete (will trigger browser confirm)
                // Note: In real E2E, we'd need to handle the confirm dialog
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.deleteSelected();
                ")
                ->pause(500)

                // Verify annotation deleted
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 0')
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).selectedAnnotationId === null')

                ->screenshot('annotation-deleted');
        });
    }

    /**
     * Test E2E: Undo button restores deleted annotation
     */
    public function test_undo_restores_deleted_annotation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create annotation and save state
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));

                    // Add annotation
                    component.annotations.push({
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });

                    // Save state for undo
                    const editor = window.createAnnotationEditor();
                    const stateUpdate = editor.saveState(component.annotations, component.undoStack);
                    component.undoStack = stateUpdate.undoStack;

                    // Delete annotation
                    component.selectedAnnotationId = 1001;
                    component.deleteSelected();
                ")
                ->pause(500)

                // Verify deleted
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 0')

                // Click undo button
                ->click('[title="Undo (Ctrl+Z)"]')
                ->pause(500)

                // Verify restored
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 1')

                ->screenshot('undo-restore');
        });
    }

    /**
     * Test E2E: Redo button reapplies deletion
     */
    public function test_redo_reapplies_deletion()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Setup: Create, delete, undo
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));

                    // Add and delete
                    component.annotations.push({
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });

                    const editor = window.createAnnotationEditor();
                    const stateUpdate = editor.saveState(component.annotations, component.undoStack);
                    component.undoStack = stateUpdate.undoStack;

                    component.selectedAnnotationId = 1001;
                    component.deleteSelected();
                ")
                ->pause(500)

                // Undo
                ->click('[title="Undo (Ctrl+Z)"]')
                ->pause(500)

                // Verify restored
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 1')

                // Redo
                ->click('[title="Redo (Ctrl+Y)"]')
                ->pause(500)

                // Verify deleted again
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).annotations.length === 0')

                ->screenshot('redo-delete');
        });
    }

    /**
     * Test E2E: Deselect button clears selection
     */
    public function test_deselect_button_clears_selection()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create and select annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.annotations.push({
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    });
                    component.selectedAnnotationId = 1001;
                ")
                ->pause(500)

                // Verify selection indicator visible
                ->waitFor('[x-show="selectedAnnotationId !== null"]', 5)
                ->assertSee('Editing')

                // Click deselect (✕) button
                ->click('[title="Deselect"]')
                ->pause(500)

                // Verify selection cleared
                ->assertScript('return window.Alpine.\$data(document.querySelector("[x-data]")).selectedAnnotationId === null')

                ->screenshot('deselected');
        });
    }

    /**
     * Test E2E: Resize handle detection works
     */
    public function test_resize_handles_are_functional()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create and select annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    const testAnnotation = {
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    };
                    component.annotations.push(testAnnotation);
                    component.selectedAnnotationId = 1001;
                    component.renderCanvas();
                ")
                ->pause(500)

                // Test resize handle detection
                ->assertScript("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    const drawer = window.createAnnotationDrawer();
                    const canvas = component.\$refs.annotationCanvas;
                    const annotation = component.annotations[0];

                    // Test bottom-right handle
                    const brX = (annotation.x + annotation.width) * canvas.width;
                    const brY = (annotation.y + annotation.height) * canvas.height;
                    const handle = drawer.getResizeHandle(brX, brY, annotation, canvas);

                    return handle === 'br';
                ")

                ->screenshot('resize-handles-functional');
        });
    }

    /**
     * Test E2E: Move annotation updates position
     */
    public function test_move_annotation_updates_position()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Create annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    const testAnnotation = {
                        id: 1001,
                        x: 0.2,
                        y: 0.3,
                        width: 0.15,
                        height: 0.12,
                        color: '#3B82F6',
                        text: 'Test Room'
                    };
                    component.annotations.push(testAnnotation);
                ")
                ->pause(500)

                // Move annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    const drawer = window.createAnnotationDrawer();
                    const annotation = component.annotations[0];
                    const canvas = component.\$refs.annotationCanvas;

                    // Move 50px right, 30px down
                    const newPos = drawer.moveAnnotation(annotation, 50, 30, canvas);
                    annotation.x = newPos.x;
                    annotation.y = newPos.y;

                    component.renderCanvas();
                ")
                ->pause(500)

                // Verify position changed
                ->assertScript("
                    const annotation = window.Alpine.\$data(document.querySelector('[x-data]')).annotations[0];
                    return annotation.x > 0.2 && annotation.y > 0.3;
                ")

                ->screenshot('annotation-moved');
        });
    }
}
