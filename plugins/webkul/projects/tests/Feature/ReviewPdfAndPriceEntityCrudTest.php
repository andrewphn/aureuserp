<?php

namespace Webkul\Project\Tests\Feature;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ReviewPdfAndPrice;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectDraft;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Support\Models\Company;

/**
 * End-to-End Feature Test for ReviewPdfAndPrice Entity CRUD
 *
 * Tests the complete workflow:
 * 1. Page loads with PDF document
 * 2. User extracts and creates rooms from PDF
 * 3. User adds cabinet runs to rooms
 * 4. Entity sidebar displays hierarchy
 * 5. Entities are saved to database
 * 6. Draft auto-save works
 * 7. Resuming draft restores state
 */
class ReviewPdfAndPriceEntityCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected PdfDocument $pdfDocument;
    protected Partner $customer;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@tcswoodwork.com',
        ]);

        // Create company
        $this->company = Company::factory()->create([
            'name' => 'TCS Woodwork',
            'acronym' => 'TCS',
            'is_default' => true,
        ]);

        // Create customer
        $this->customer = Partner::factory()->create([
            'name' => 'John Smith',
            'sub_type' => 'customer',
            'street1' => '123 Main St',
            'city' => 'Nantucket',
            'phone' => '555-1234',
        ]);

        // Create project
        $this->project = Project::factory()->create([
            'name' => '123 Main St - Kitchen Renovation',
            'project_number' => 'TCS-001-MAIN',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
        ]);

        // Create PDF document attached to project
        $this->pdfDocument = PdfDocument::create([
            'module_type' => Project::class,
            'module_id' => $this->project->id,
            'file_name' => 'kitchen-plan.pdf',
            'file_path' => 'pdf-documents/' . $this->project->id . '/kitchen-plan.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'is_primary_reference' => true,
            'is_latest_version' => true,
            'uploaded_by' => $this->user->id,
        ]);

        // Create PDF pages
        for ($i = 1; $i <= 3; $i++) {
            PdfPage::create([
                'document_id' => $this->pdfDocument->id,
                'page_number' => $i,
                'width' => 612,
                'height' => 792,
            ]);
        }
    }

    /** @test */
    public function it_loads_pdf_review_page_with_empty_rooms()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('filament.admin.resources.projects.pdf-review', [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]));

        $response->assertSuccessful();
    }

    /** @test */
    public function it_shows_page_view_with_pdf_page_count()
    {
        $this->actingAs($this->user);

        Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ])
            ->assertSee('PDF Pages (3)')
            ->assertSee('Page View')
            ->assertSee('Entity View');
    }

    /** @test */
    public function it_shows_customer_info_in_sidebar()
    {
        $this->actingAs($this->user);

        Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ])
            ->assertSee('Customer')
            ->assertSee('John Smith');
    }

    /** @test */
    public function it_can_add_room_to_form()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Add a room via the rooms repeater
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
                        'linear_feet' => '12.5',
                        'notes' => '',
                    ],
                ],
            ],
        ]);

        $component->assertSet('data.rooms.0.room_name', 'Kitchen');
        $component->assertSet('data.rooms.0.cabinet_runs.0.run_name', 'Base Cabinets');
        $component->assertSet('data.rooms.0.cabinet_runs.0.linear_feet', '12.5');
    }

    /** @test */
    public function it_can_save_rooms_and_cabinets_to_database()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Set room data
        $component->set('data.rooms', [
            [
                'room_id' => null,
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Wall Cabinets',
                        'cabinet_level' => '3',
                        'linear_feet' => '8.0',
                        'notes' => 'Upper cabinets along sink wall',
                    ],
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Base Cabinets',
                        'cabinet_level' => '2',
                        'linear_feet' => '15.5',
                        'notes' => 'Base cabinets with drawers',
                    ],
                ],
            ],
            [
                'room_id' => null,
                'room_name' => 'Pantry',
                'room_type' => 'pantry',
                'cabinet_runs' => [
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Pantry Shelving',
                        'cabinet_level' => '2',
                        'linear_feet' => '6.0',
                        'notes' => 'Adjustable shelving',
                    ],
                ],
            ],
        ]);

        // Call save method
        $component->call('saveRoomsAndCabinets');

        // Verify rooms were created in database
        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $this->assertDatabaseHas('projects_rooms', [
            'project_id' => $this->project->id,
            'name' => 'Pantry',
            'room_type' => 'pantry',
        ]);

        // Verify cabinet runs were created
        $kitchen = Room::where('project_id', $this->project->id)
            ->where('name', 'Kitchen')
            ->first();

        $this->assertNotNull($kitchen);

        // Get the default location for the kitchen
        $location = RoomLocation::where('room_id', $kitchen->id)->first();
        $this->assertNotNull($location);

        // Verify cabinet runs
        $this->assertDatabaseHas('projects_cabinet_runs', [
            'location_id' => $location->id,
            'name' => 'Wall Cabinets',
            'cabinet_level' => '3',
        ]);

        $this->assertDatabaseHas('projects_cabinet_runs', [
            'location_id' => $location->id,
            'name' => 'Base Cabinets',
            'cabinet_level' => '2',
        ]);

        // Verify total counts
        $this->assertEquals(2, Room::where('project_id', $this->project->id)->count());
        $this->assertEquals(3, CabinetRun::whereHas('location', function ($q) {
            $q->whereHas('room', function ($q2) {
                $q2->where('project_id', $this->project->id);
            });
        })->count());
    }

    /** @test */
    public function it_can_save_draft_and_resume()
    {
        $this->actingAs($this->user);

        // Initial session - add rooms and save draft
        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        $component->set('data.rooms', [
            [
                'room_id' => null,
                'room_name' => 'Master Bedroom',
                'room_type' => 'bedroom',
                'cabinet_runs' => [
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Closet System',
                        'cabinet_level' => '2',
                        'linear_feet' => '10.0',
                        'notes' => '',
                    ],
                ],
            ],
        ]);

        // Save draft
        $component->call('saveDraft');

        // Verify draft was created
        $draft = ProjectDraft::where('user_id', $this->user->id)
            ->where('session_id', 'pdf-review-' . $this->project->id . '-' . $this->pdfDocument->id)
            ->first();

        $this->assertNotNull($draft);
        $this->assertEquals('Master Bedroom', $draft->form_data['rooms'][0]['room_name'] ?? null);

        // New session - resume from draft
        $component2 = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Should show draft resume banner and restore data
        $component2->assertSee('Resuming Draft');
        $component2->assertSet('data.rooms.0.room_name', 'Master Bedroom');
    }

    /** @test */
    public function it_can_discard_draft_and_start_fresh()
    {
        $this->actingAs($this->user);

        // Create a draft first
        ProjectDraft::create([
            'user_id' => $this->user->id,
            'session_id' => 'pdf-review-' . $this->project->id . '-' . $this->pdfDocument->id,
            'wizard_type' => 'pdf-review',
            'form_data' => [
                'rooms' => [
                    [
                        'room_name' => 'Old Room',
                        'room_type' => 'other',
                        'cabinet_runs' => [],
                    ],
                ],
            ],
            'current_step' => 1,
            'expires_at' => now()->addDays(7),
        ]);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Should see draft resume option
        $component->assertSee('Resuming Draft');

        // Discard the draft
        $component->call('discardDraft');

        // Draft should be deleted
        $this->assertDatabaseMissing('projects_project_drafts', [
            'user_id' => $this->user->id,
            'session_id' => 'pdf-review-' . $this->project->id . '-' . $this->pdfDocument->id,
        ]);

        // Rooms should be empty
        $component->assertSet('data.rooms', []);
    }

    /** @test */
    public function it_shows_entity_summary_stats()
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
                        'run_name' => 'Base',
                        'cabinet_level' => '2',
                        'linear_feet' => '10.5',
                    ],
                    [
                        'run_name' => 'Wall',
                        'cabinet_level' => '3',
                        'linear_feet' => '8.0',
                    ],
                ],
            ],
            [
                'room_id' => null,
                'room_name' => 'Bathroom',
                'room_type' => 'bathroom',
                'cabinet_runs' => [
                    [
                        'run_name' => 'Vanity',
                        'cabinet_level' => '2',
                        'linear_feet' => '4.5',
                    ],
                ],
            ],
        ]);

        // Summary should show:
        // - 2 Rooms
        // - 3 Cabinet Runs
        // - 23.0 LF Total (10.5 + 8.0 + 4.5)
        $component->assertSee('Rooms');
        $component->assertSee('Cabinet Runs');
        $component->assertSee('Total Linear Feet');
    }

    /** @test */
    public function it_can_classify_pdf_pages()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Set page classifications
        $component->set('data.page_metadata.0.page_type', 'cover_page');
        $component->set('data.page_metadata.1.page_type', 'floor_plan');
        $component->set('data.page_metadata.2.page_type', 'elevation');

        $component->assertSet('data.page_metadata.0.page_type', 'cover_page');
        $component->assertSet('data.page_metadata.1.page_type', 'floor_plan');
        $component->assertSet('data.page_metadata.2.page_type', 'elevation');
    }

    /** @test */
    public function it_creates_sales_order_from_entities()
    {
        $this->actingAs($this->user);

        // First, create rooms in the database
        $room = Room::create([
            'project_id' => $this->project->id,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'Main Kitchen Area',
        ]);

        CabinetRun::create([
            'location_id' => $location->id,
            'name' => 'Base Cabinets',
            'run_type' => 'base',
            'cabinet_level' => '2',
            'total_linear_feet' => 15.5,
        ]);

        $component = Livewire::test(ReviewPdfAndPrice::class, [
            'record' => $this->project->id,
            'pdf' => $this->pdfDocument->id,
        ]);

        // Set rooms data in form
        $component->set('data.rooms', [
            [
                'room_id' => $room->id,
                'room_name' => 'Kitchen',
                'room_type' => 'kitchen',
                'cabinet_runs' => [
                    [
                        'cabinet_run_id' => null,
                        'run_name' => 'Base Cabinets',
                        'cabinet_level' => '2',
                        'linear_feet' => '15.5',
                    ],
                ],
            ],
        ]);

        // Create sales order
        $component->call('createSalesOrder');

        // Verify sales order was created
        $this->assertDatabaseHas('sales_orders', [
            'project_id' => $this->project->id,
        ]);
    }
}
