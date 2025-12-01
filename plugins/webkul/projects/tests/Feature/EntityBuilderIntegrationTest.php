<?php

namespace Webkul\Project\Tests\Feature;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\User;
use Livewire\Livewire;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ReviewPdfAndPrice;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Tests\TestCase;
use Webkul\Support\Models\Company;

/**
 * Integration Tests for Entity Builder Component
 *
 * Tests the Entity Builder sidebar component on the PDF Review page,
 * including:
 * - Rendering the relationship tree
 * - Displaying room and cabinet run counts
 * - Calculating linear feet totals
 * - Tier breakdown display
 * - Real-time updates when form data changes
 */
class EntityBuilderIntegrationTest extends TestCase
{
    protected User $user;
    protected Project $project;
    protected PdfDocument $pdfDocument;
    protected Partner $customer;
    protected Company $company;
    protected ProjectStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project stage (required for project)
        $this->stage = ProjectStage::create([
            'name' => 'Discovery',
            'slug' => 'discovery',
            'color' => '#3b82f6',
            'is_active' => true,
            'sort' => 1,
        ]);

        // Use the admin user created in TestCase
        $this->user = User::find(1);

        // Create company directly (without factory)
        $this->company = Company::create([
            'name' => 'TCS Woodwork',
            'acronym' => 'TCS',
            'is_default' => true,
        ]);

        // Create customer directly (without factory)
        $this->customer = Partner::create([
            'name' => 'Jane Doe',
            'sub_type' => 'customer',
            'street1' => '25 Friendship Lane',
            'city' => 'Nantucket',
            'phone' => '555-0125',
        ]);

        // Create project directly (without factory)
        $this->project = Project::create([
            'name' => '25 Friendship Lane - Kitchen Remodel',
            'project_number' => 'TCS-025-FL',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'stage_id' => $this->stage->id,
        ]);

        // Create PDF document
        $this->pdfDocument = PdfDocument::create([
            'module_type' => Project::class,
            'module_id' => $this->project->id,
            'file_name' => 'friendship-lane-plans.pdf',
            'file_path' => 'pdf-documents/' . $this->project->id . '/friendship-lane-plans.pdf',
            'file_size' => 2048000,
            'mime_type' => 'application/pdf',
            'page_count' => 8,
            'is_primary_reference' => true,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        // Create PDF pages
        $pageTypes = ['cover_page', 'plan_view', 'elevation', 'detail', 'detail', 'detail', 'detail', 'countertops'];
        for ($i = 1; $i <= 8; $i++) {
            PdfPage::create([
                'document_id' => $this->pdfDocument->id,
                'page_number' => $i,
                'page_type' => $pageTypes[$i - 1],
                'width' => 612,
                'height' => 792,
            ]);
        }
    }

    /** @test */
    public function it_renders_entity_builder_section()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        $component->assertSee('Entity Builder');
    }

    /** @test */
    public function it_shows_empty_state_when_no_rooms()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        $component->assertSee('No entities yet');
        $component->assertSee('Add rooms in Step 2');
    }

    /** @test */
    public function it_displays_room_count_badge()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add rooms
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [],
            ],
            [
                'room_name' => 'Pantry',
                'room_type' => 'pantry',
                'cabinet_runs' => [],
            ],
        ]);

        // Should show 2 rooms
        $component->assertSeeHtml('2'); // Room count badge
    }

    /** @test */
    public function it_displays_cabinet_run_count_badge()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with cabinet runs
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '10', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '8', 'cabinet_level' => '3'],
                    ['run_name' => 'Island', 'linear_feet' => '6', 'cabinet_level' => '4'],
                ],
            ],
        ]);

        // Should show 3 cabinet runs
        $component->assertSeeHtml('3'); // Run count badge
    }

    /** @test */
    public function it_displays_total_linear_feet()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add rooms with cabinet runs
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '15.5', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '10.0', 'cabinet_level' => '3'],
                ],
            ],
            [
                'room_name' => 'Bathroom',
                'room_type' => 'bathroom',
                'cabinet_runs' => [
                    ['run_name' => 'Vanity', 'linear_feet' => '4.5', 'cabinet_level' => '2'],
                ],
            ],
        ]);

        // Should show 30.0 LF total (15.5 + 10.0 + 4.5)
        $component->assertSee('30.0 LF');
    }

    /** @test */
    public function it_displays_room_in_relationship_tree()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add a room
        $component->set('data.rooms', [
            [
                'room_name' => 'Master Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [],
            ],
        ]);

        // Should display room name in tree
        $component->assertSee('Master Kitchen');
    }

    /** @test */
    public function it_displays_cabinet_runs_nested_under_rooms()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with cabinet runs
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Sink Wall Cabinets', 'linear_feet' => '12', 'cabinet_level' => '3'],
                    ['run_name' => 'Fridge Wall Cabinets', 'linear_feet' => '8', 'cabinet_level' => '3'],
                ],
            ],
        ]);

        // Should display cabinet run names
        $component->assertSee('Sink Wall Cabinets');
        $component->assertSee('Fridge Wall Cabinets');
    }

    /** @test */
    public function it_displays_tier_badges_for_cabinet_runs()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with different tier cabinet runs
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Basic Cabinets', 'linear_feet' => '10', 'cabinet_level' => '1'],
                    ['run_name' => 'Standard Cabinets', 'linear_feet' => '10', 'cabinet_level' => '2'],
                    ['run_name' => 'Premium Cabinets', 'linear_feet' => '10', 'cabinet_level' => '4'],
                ],
            ],
        ]);

        // Should show tier badges
        $component->assertSee('T1');
        $component->assertSee('T2');
        $component->assertSee('T4');
    }

    /** @test */
    public function it_displays_tier_breakdown_section()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with multiple tiers
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '15', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '10', 'cabinet_level' => '3'],
                    ['run_name' => 'Island', 'linear_feet' => '8', 'cabinet_level' => '4'],
                ],
            ],
        ]);

        // Should show "By Tier" section header
        $component->assertSee('By Tier');
        $component->assertSee('Tier 2');
        $component->assertSee('Tier 3');
        $component->assertSee('Tier 4');
    }

    /** @test */
    public function it_calculates_per_room_linear_feet()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with cabinet runs totaling 25 LF
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '15', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '10', 'cabinet_level' => '3'],
                ],
            ],
        ]);

        // Should show 25.0 LF for kitchen
        $component->assertSee('25.0 LF');
    }

    /** @test */
    public function it_shows_no_cabinet_runs_message_for_empty_room()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room without cabinet runs
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [],
            ],
        ]);

        // Should show empty state message
        $component->assertSee('No cabinet runs yet');
    }

    /** @test */
    public function it_updates_entity_builder_when_room_is_added()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Start with no rooms
        $component->assertSee('No entities yet');

        // Add a room
        $component->set('data.rooms', [
            [
                'room_name' => 'New Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [],
            ],
        ]);

        // Should now show the room
        $component->assertSee('New Kitchen');
        $component->assertDontSee('No entities yet');
    }

    /** @test */
    public function it_displays_pdf_document_info_card()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Should show PDF document info
        $component->assertSee('PDF Document');
        $component->assertSee('friendship-lane-plans.pdf');
        $component->assertSee('8 pages');
    }

    /** @test */
    public function it_displays_customer_info_card()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Should show customer info
        $component->assertSee('Customer');
        $component->assertSee('Jane Doe');
        $component->assertSee('555-0125');
    }

    /** @test */
    public function it_displays_quick_actions_section()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Should show quick actions
        $component->assertSee('Quick Actions');
        $component->assertSee('Auto-Parse PDF');
        $component->assertSee('Save Entities');
        $component->assertSee('Back to Project');
    }

    /** @test */
    public function it_handles_complex_multi_room_scenario()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add complete project structure like Friendship Lane
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Sink Wall', 'linear_feet' => '12.5', 'cabinet_level' => '3'],
                    ['run_name' => 'Fridge Wall', 'linear_feet' => '8.0', 'cabinet_level' => '3'],
                    ['run_name' => 'Pantry', 'linear_feet' => '6.5', 'cabinet_level' => '2'],
                    ['run_name' => 'Island', 'linear_feet' => '10.0', 'cabinet_level' => '4'],
                ],
            ],
        ]);

        // Verify all cabinet runs are displayed
        $component->assertSee('Sink Wall');
        $component->assertSee('Fridge Wall');
        $component->assertSee('Pantry');
        $component->assertSee('Island');

        // Verify total LF (12.5 + 8.0 + 6.5 + 10.0 = 37.0)
        $component->assertSee('37.0 LF');
    }

    /** @test */
    public function it_can_save_entities_and_persist_to_database()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add rooms with cabinet runs
        $component->set('data.rooms', [
            [
                'room_id' => null,
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Base Cabinets',
                        'cabinet_level' => '2',
                        'linear_feet' => '15.5',
                        'notes' => 'Soft close drawers',
                    ],
                ],
            ],
        ]);

        // Save entities
        $component->call('saveRoomsAndCabinets');

        // Verify database persistence
        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        // Get the room to check cabinet runs
        $room = Room::where('project_id', $this->project->id)->where('name', 'Kitchen')->first();
        $this->assertNotNull($room);

        // Check location was created
        $location = RoomLocation::where('room_id', $room->id)->first();
        $this->assertNotNull($location);

        // Check cabinet run was created
        $this->assertDatabaseHas('projects_cabinet_runs', [
            'location_id' => $location->id,
            'name' => 'Base Cabinets',
            'cabinet_level' => '2',
        ]);
    }

    /** @test */
    public function it_handles_decimal_linear_feet_correctly()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with decimal linear feet
        $component->set('data.rooms', [
            [
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    ['run_name' => 'Base', 'linear_feet' => '10.333', 'cabinet_level' => '2'],
                    ['run_name' => 'Wall', 'linear_feet' => '8.667', 'cabinet_level' => '3'],
                ],
            ],
        ]);

        // Should display formatted total (19.0 after rounding in display)
        $component->assertSee('19.0 LF');
    }

    /** @test */
    public function it_displays_room_type_fallback_when_name_missing()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add room with only room_type (no room_name)
        $component->set('data.rooms', [
            [
                'room_name' => null,
                'room_type' => 'kitchen',
                'cabinet_runs' => [],
            ],
        ]);

        // Should fall back to room_type
        $component->assertSee('kitchen');
    }
}
