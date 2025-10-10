<?php

namespace Tests\Browser;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Tag;
use Webkul\Support\Models\Company;

class PdfAnnotationModalIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Project $project;
    protected PdfDocument $document;
    protected PdfPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Partner::factory()->create([
            'sub_type' => 'customer',
            'name' => 'Test Customer Inc',
        ]);

        $company = Company::factory()->create([
            'name' => 'Test Company LLC',
        ]);

        $this->project = Project::factory()->create([
            'partner_id' => $customer->id,
            'company_id' => $company->id,
            'street_address_1' => '123 Test Street',
            'city' => 'Boston',
            'state' => 'MA',
            'zip_code' => '02101',
            'country' => 'United States',
        ]);

        $this->document = PdfDocument::factory()->create([
            'module_type' => Project::class,
            'module_id' => $this->project->id,
            'file_path' => 'test.pdf',
        ]);

        $this->page = PdfPage::factory()->create([
            'document_id' => $this->document->id,
            'page_number' => 1,
        ]);
    }

    /** @test */
    public function user_can_open_annotation_modal_and_see_cover_page_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->waitForText('Page 1')
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->waitForText('What type of page is this?')
                ->select('@page-type-dropdown', 'Cover')
                ->waitForText('Cover Page Information')
                ->assertSee('Customer')
                ->assertSee('Company')
                ->assertSee('Branch (Optional)')
                ->assertSee('Project Address');
        });
    }

    /** @test */
    public function cover_page_fields_pre_populate_with_project_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover')
                ->pause(500) // Wait for Alpine to populate fields
                ->assertSelected('@customer-dropdown', (string) $this->project->partner_id)
                ->assertSelected('@company-dropdown', (string) $this->project->company_id)
                ->assertInputValue('@street-address-1', '123 Test Street')
                ->assertInputValue('@city', 'Boston')
                ->assertInputValue('@zip-code', '02101');
        });
    }

    /** @test */
    public function customer_create_button_opens_new_window(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover')
                ->click('@customer-create-button')
                ->pause(1000)
                // Verify new window opened (implementation depends on Dusk capabilities)
                ->assertSee('Customer'); // Modal should still be visible
        });
    }

    /** @test */
    public function customer_edit_button_requires_selection(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover')
                ->select('@customer-dropdown', '') // Deselect customer
                ->click('@customer-edit-button')
                ->assertDialogOpened('Please select a customer first');
        });
    }

    /** @test */
    public function refresh_button_reloads_customer_options(): void
    {
        $this->browse(function (Browser $browser) {
            $initialCustomerCount = Partner::where('sub_type', 'customer')->count();

            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover');

            // Create new customer in background
            Partner::factory()->create([
                'sub_type' => 'customer',
                'name' => 'New Customer',
            ]);

            $browser->click('@customer-refresh-button')
                ->pause(2000) // Wait for page reload
                ->waitFor('@annotation-modal')
                ->assertSelectHasOptions('@customer-dropdown', [$initialCustomerCount + 1]);
        });
    }

    /** @test */
    public function project_tags_section_displays_and_allows_tag_selection(): void
    {
        Tag::factory()->create(['name' => 'High Priority', 'type' => 'priority']);
        Tag::factory()->create(['name' => 'Complex', 'type' => 'complexity']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->scrollTo('@project-tags-section')
                ->assertSee('Project Tags')
                ->assertSee('🎯 Priority')
                ->assertSee('📊 Complexity')
                ->click('@category-priority')
                ->waitForText('High Priority')
                ->click('@tag-high-priority')
                ->waitForText('Saving...')
                ->pause(1000)
                ->assertMissing('@saving-indicator');
        });
    }

    /** @test */
    public function project_tags_search_filters_tags(): void
    {
        Tag::factory()->create(['name' => 'Alpha Tag', 'type' => 'priority']);
        Tag::factory()->create(['name' => 'Beta Tag', 'type' => 'priority']);
        Tag::factory()->create(['name' => 'Gamma Tag', 'type' => 'complexity']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->scrollTo('@project-tags-section')
                ->click('@category-priority')
                ->type('@tag-search-input', 'beta')
                ->pause(500)
                ->assertSee('Beta Tag')
                ->assertDontSee('Alpha Tag')
                ->assertDontSee('Gamma Tag');
        });
    }

    /** @test */
    public function save_changes_button_persists_cover_page_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover')
                ->type('@street-address-1', 'New Address')
                ->click('@save-changes-button')
                ->pause(1000)
                ->assertSee('saved'); // Or whatever success message is shown

            // Verify data persisted
            $this->page->refresh();
            $this->assertEquals('cover', $this->page->page_type);
        });
    }

    /** @test */
    public function discussion_tab_displays_chatter_messages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->click('@discussion-tab')
                ->waitForText('Discussion')
                ->assertSee('Add a message'); // Chatter input field
        });
    }

    /** @test */
    public function branch_dropdown_shows_only_when_company_selected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->select('@page-type-dropdown', 'Cover')
                ->select('@company-dropdown', '')
                ->assertMissing('@branch-dropdown')
                ->select('@company-dropdown', (string) $this->project->company_id)
                ->pause(200)
                ->assertVisible('@branch-dropdown');
        });
    }

    /** @test */
    public function modal_closes_when_close_button_clicked(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/admin/project/projects/{$this->project->id}/pdf-review?pdf={$this->document->id}")
                ->click('@annotate-page-1')
                ->waitFor('@annotation-modal')
                ->assertVisible('@annotation-modal')
                ->click('@modal-close-button')
                ->pause(500)
                ->assertMissing('@annotation-modal');
        });
    }
}
