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
 * End-to-End Playwright Tests for Phase 3 Annotation System
 *
 * Tests the complete user workflow:
 * 1. Navigate to PDF Review page
 * 2. Interact with annotation type selector
 * 3. Use cascading context dropdowns
 * 4. Draw annotations on PDF canvas
 * 5. Save annotations with context
 * 6. Verify entities are created
 */
class Phase3AnnotationSystemTest extends DuskTestCase
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
            'name' => 'E2E Test Project - Phase 3',
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => 'Webkul\Project\Models\Project',
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
     * Test E2E: Annotation Type Selector is visible and functional
     */
    public function test_annotation_type_selector_is_visible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)
                ->assertVisible('@annotation-type-selector')
                ->assertSeeIn('@annotation-type-selector', 'What are you annotating?')
                ->assertSelectHasOptions('@annotation-type-selector', [
                    'room',
                    'room_location',
                    'cabinet_run',
                    'cabinet',
                    'dimension',
                ]);
        });
    }

    /**
     * Test E2E: Room annotation workflow
     */
    public function test_room_annotation_workflow()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select "Room" annotation type
                ->select('@annotation-type-selector', 'room')
                ->pause(500)

                // Verify only room dropdown is visible
                ->assertVisible('@room-dropdown')
                ->assertMissing('@room-location-dropdown')
                ->assertMissing('@cabinet-run-dropdown')
                ->assertMissing('@cabinet-dropdown')

                // Select a room (if available)
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })

                // Verify context summary shows correct text
                ->assertSee('Creating new rooms in:')

                // Draw annotation on canvas (simulate mouse events)
                ->script("
                    const canvas = document.querySelector('[x-ref=\"annotationCanvas\"]');
                    const event = new MouseEvent('mousedown', {
                        clientX: 100,
                        clientY: 100,
                        bubbles: true
                    });
                    canvas.dispatchEvent(event);

                    const moveEvent = new MouseEvent('mousemove', {
                        clientX: 200,
                        clientY: 200,
                        bubbles: true
                    });
                    canvas.dispatchEvent(moveEvent);

                    const upEvent = new MouseEvent('mouseup', {
                        clientX: 200,
                        clientY: 200,
                        bubbles: true
                    });
                    canvas.dispatchEvent(upEvent);
                ")
                ->pause(500)

                // Verify annotation was created
                ->assertScript('return window.Alpine && window.Alpine.$data(document.querySelector("[x-data]")).annotations.length > 0');
        });
    }

    /**
     * Test E2E: Cascading dropdown filtering for room_location
     */
    public function test_room_location_cascade_filtering()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select "Room Location" annotation type
                ->select('@annotation-type-selector', 'room_location')
                ->pause(500)

                // Verify room and location dropdowns are visible
                ->assertVisible('@room-dropdown')
                ->assertVisible('@room-location-dropdown')
                ->assertMissing('@cabinet-run-dropdown')

                // Location dropdown should be disabled initially
                ->assertAttribute('@room-location-dropdown', 'disabled', 'disabled')

                // Select a room
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)

                // Location dropdown should now be enabled
                ->assertAttributeMissing('@room-location-dropdown', 'disabled')

                // Verify filtered locations appear
                ->script('return document.querySelector("[x-model=\\"selectedRoomLocationId\\"]").options.length > 1')

                // Select a location
                ->whenAvailable('@room-location-dropdown', function ($select) {
                    $select->selectFirstOption();
                })

                // Verify context summary shows hierarchy
                ->assertSee('→') // Arrow indicating hierarchy
                ->screenshot('room-location-cascade');
        });
    }

    /**
     * Test E2E: Full cascade for cabinet annotation
     */
    public function test_cabinet_annotation_full_cascade()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select "Cabinet" annotation type
                ->select('@annotation-type-selector', 'cabinet')
                ->pause(500)

                // Verify all dropdowns are visible
                ->assertVisible('@room-dropdown')
                ->assertVisible('@room-location-dropdown')
                ->assertVisible('@cabinet-run-dropdown')
                ->assertVisible('@cabinet-dropdown')

                // Verify cascade is disabled until parent selections
                ->assertAttribute('@room-location-dropdown', 'disabled', 'disabled')
                ->assertAttribute('@cabinet-run-dropdown', 'disabled', 'disabled')
                ->assertAttribute('@cabinet-dropdown', 'disabled', 'disabled')

                // Navigate cascade: Room
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)

                // Room Location should now be enabled
                ->assertAttributeMissing('@room-location-dropdown', 'disabled')

                // Navigate cascade: Room Location
                ->whenAvailable('@room-location-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)

                // Cabinet Run should now be enabled
                ->assertAttributeMissing('@cabinet-run-dropdown', 'disabled')

                // Navigate cascade: Cabinet Run
                ->whenAvailable('@cabinet-run-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)

                // Cabinet dropdown should now be enabled
                ->assertAttributeMissing('@cabinet-dropdown', 'disabled')

                // Verify context summary shows full hierarchy
                ->assertSee('Run:')
                ->screenshot('cabinet-full-cascade');
        });
    }

    /**
     * Test E2E: Changing annotation type resets child selections
     */
    public function test_annotation_type_change_resets_selections()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select cabinet and navigate full cascade
                ->select('@annotation-type-selector', 'cabinet')
                ->pause(500)
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)

                // Store selected room ID
                ->storeSource('@room-dropdown', 'selectedRoomBefore')

                // Change to room annotation type
                ->select('@annotation-type-selector', 'room')
                ->pause(500)

                // Verify room selection persists but child selections are reset
                ->assertSourceHas('@room-dropdown', $this->retrieve('selectedRoomBefore'))

                // Verify child dropdowns are hidden or reset
                ->assertScript('
                    const component = window.Alpine.$data(document.querySelector("[x-data]"));
                    return component.selectedRoomLocationId === null &&
                           component.selectedCabinetRunId === null &&
                           component.selectedCabinetId === null;
                ')
                ->screenshot('type-change-reset');
        });
    }

    /**
     * Test E2E: Context summary displays correctly
     */
    public function test_context_summary_display()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Test room annotation summary
                ->select('@annotation-type-selector', 'room')
                ->pause(500)
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)
                ->assertSeeIn('@context-summary', 'Creating new rooms in:')

                // Test room_location annotation summary
                ->select('@annotation-type-selector', 'room_location')
                ->pause(500)
                ->whenAvailable('@room-location-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)
                ->assertSeeIn('@context-summary', '→') // Hierarchy arrow

                // Test cabinet run summary
                ->select('@annotation-type-selector', 'cabinet_run')
                ->pause(500)
                ->whenAvailable('@cabinet-run-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)
                ->assertSeeIn('@context-summary', '→')

                ->screenshot('context-summary-variations');
        });
    }

    /**
     * Test E2E: Save annotations with context
     */
    public function test_save_annotations_with_context()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Setup annotation context
                ->select('@annotation-type-selector', 'room')
                ->pause(500)
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })

                // Draw annotation
                ->script("
                    const component = window.Alpine.\$data(document.querySelector('[x-data]'));
                    component.annotations.push({
                        id: 'test-annotation-1',
                        type: 'room',
                        label: 'Test Kitchen',
                        x: 0.1,
                        y: 0.1,
                        width: 0.2,
                        height: 0.2,
                        color: '#3B82F6'
                    });
                ")
                ->pause(500)

                // Click save button
                ->press('@save-annotations-button')
                ->pause(2000)

                // Verify success message
                ->assertSee('Saved')
                ->assertSee('annotations')

                // Verify annotation was saved to database
                ->assertDatabaseHas('pdf_page_annotations', [
                    'pdf_page_id' => $this->pdfPage->id,
                    'annotation_type' => 'room',
                    'label' => 'Test Kitchen',
                ])

                ->screenshot('save-annotations-success');
        });
    }

    /**
     * Test E2E: Disabled states work correctly
     */
    public function test_dropdown_disabled_states()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select cabinet type
                ->select('@annotation-type-selector', 'cabinet')
                ->pause(500)

                // All child dropdowns should be disabled initially
                ->assertAttribute('@room-location-dropdown', 'disabled', 'disabled')
                ->assertAttribute('@cabinet-run-dropdown', 'disabled', 'disabled')
                ->assertAttribute('@cabinet-dropdown', 'disabled', 'disabled')

                // Select room
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)

                // Location should be enabled, others still disabled
                ->assertAttributeMissing('@room-location-dropdown', 'disabled')
                ->assertAttribute('@cabinet-run-dropdown', 'disabled', 'disabled')
                ->assertAttribute('@cabinet-dropdown', 'disabled', 'disabled')

                // Select location
                ->whenAvailable('@room-location-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)

                // Run should be enabled, cabinet still disabled
                ->assertAttributeMissing('@cabinet-run-dropdown', 'disabled')
                ->assertAttribute('@cabinet-dropdown', 'disabled', 'disabled')

                ->screenshot('disabled-states-progression');
        });
    }

    /**
     * Test E2E: Helper text displays correctly
     */
    public function test_helper_text_displays()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->pdfDocument->id}")
                ->waitFor('@annotation-modal', 10)

                // Select cabinet_run type
                ->select('@annotation-type-selector', 'cabinet_run')
                ->pause(500)

                // Verify helper text for disabled dropdowns
                ->assertSee('Select a room first')

                // Select room
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)

                // Verify helper text updates
                ->assertSee('Select a room location first')

                // Test cabinet type helper text
                ->select('@annotation-type-selector', 'cabinet')
                ->pause(500)
                ->whenAvailable('@room-dropdown', function ($select) {
                    $select->selectFirstOption();
                })
                ->pause(500)
                ->whenAvailable('@room-location-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)
                ->whenAvailable('@cabinet-run-dropdown', function ($select) {
                    if ($select->element->options->length > 1) {
                        $select->selectFirstOption();
                    }
                })
                ->pause(500)

                // Verify cabinet helper text
                ->assertSee('Leave empty to create new cabinet')

                ->screenshot('helper-text-variations');
        });
    }
}
