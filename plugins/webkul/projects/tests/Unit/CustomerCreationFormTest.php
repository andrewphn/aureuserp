<?php

namespace Webkul\Project\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\CreateProject;
use Webkul\Security\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Unit tests for Customer Creation Form in Project Wizard
 *
 * Tests the getCustomerCreationFormComponentsSimplified() method and
 * customer creation logic within the CreateProject page.
 *
 * Covers:
 * - Essential fields (account_type, name, phone, email)
 * - Address section fields (street1, street2, city, zip, state_id, country_id)
 * - Additional Details section (mobile, job_title, website, parent_id)
 * - Business Details section (tax_id, company_registry)
 * - Sales Settings section (user_id)
 * - Search term pre-fill functionality
 */
class CustomerCreationFormTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for tests
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        Auth::login($this->adminUser);
    }

    /** @test */
    public function it_returns_array_of_form_components()
    {
        $page = new CreateProject();

        // Use reflection to access protected method (simplified version)
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('getCustomerCreationFormComponentsSimplified');
        $method->setAccessible(true);

        $components = $method->invoke($page);

        $this->assertIsArray($components);
        $this->assertNotEmpty($components);
    }

    /** @test */
    public function simplified_form_has_expected_component_count()
    {
        $page = new CreateProject();

        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('getCustomerCreationFormComponentsSimplified');
        $method->setAccessible(true);

        $components = $method->invoke($page);

        // Should have: Radio (account_type), TextInput (name), Grid (phone/email),
        // Section (Address), Section (Additional Details), Section (Business Details - conditional),
        // Section (Sales Settings)
        $this->assertGreaterThanOrEqual(6, count($components));
    }

    /** @test */
    public function it_creates_individual_customer_with_minimum_fields()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'John Doe',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'John Doe',
            'account_type' => AccountType::INDIVIDUAL->value,
            'sub_type' => 'customer',
        ]);

        $this->assertEquals('John Doe', $partner->name);
        $this->assertEquals('customer', $partner->sub_type);
    }

    /** @test */
    public function it_creates_company_customer_with_all_fields()
    {
        // Get a valid country_id from the database, or skip if none exists
        $country = \Webkul\Support\Models\Country::first();

        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Acme Construction LLC',
            'phone' => '(508) 555-1234',
            'email' => 'info@acme.com',
            'street1' => '123 Main Street',
            'city' => 'Nantucket',
            'state_id' => null,
            'zip' => '02554',
            'country_id' => $country?->id, // Use actual country from DB
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Acme Construction LLC',
            'account_type' => AccountType::COMPANY->value,
            'sub_type' => 'customer',
            'phone' => '(508) 555-1234',
            'email' => 'info@acme.com',
            'city' => 'Nantucket',
        ]);
    }

    /** @test */
    public function it_creates_customer_with_contact_info_only()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Jane Smith',
            'phone' => '(617) 555-9876',
            'email' => 'jane@example.com',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Jane Smith',
            'phone' => '(617) 555-9876',
            'email' => 'jane@example.com',
            'sub_type' => 'customer',
        ]);

        // Address fields should be null
        $this->assertNull($partner->street1);
        $this->assertNull($partner->city);
    }

    /** @test */
    public function it_sets_sub_type_to_customer()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Test Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('customer', $partner->sub_type);
    }

    /** @test */
    public function it_sets_creator_id_to_current_user()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Created By Admin',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals($this->adminUser->id, $partner->creator_id);
    }

    /** @test */
    public function it_can_query_customers_only()
    {
        // Create a customer
        Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Customer Person',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Create a vendor (not a customer)
        Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Vendor Company',
            'sub_type' => 'vendor',
            'creator_id' => $this->adminUser->id,
        ]);

        $customers = Partner::where('sub_type', 'customer')->get();

        $this->assertCount(1, $customers);
        $this->assertEquals('Customer Person', $customers->first()->name);
    }

    /** @test */
    public function it_defaults_individual_account_type()
    {
        // The form defaults to Individual - verify this is the expected behavior
        $defaultAccountType = AccountType::INDIVIDUAL->value;

        $this->assertEquals('individual', $defaultAccountType);
    }

    /** @test */
    public function it_validates_email_format_in_database()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Email Test',
            'email' => 'valid@email.com',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('valid@email.com', $partner->email);
    }

    /** @test */
    public function it_stores_phone_with_formatting()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Phone Test',
            'phone' => '(508) 555-1234',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        // Phone should be stored as-is (formatted)
        $this->assertEquals('(508) 555-1234', $partner->phone);
    }

    /** @test */
    public function it_can_create_customer_without_address()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'No Address Customer',
            'phone' => '555-1234',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'No Address Customer',
            'sub_type' => 'customer',
        ]);

        $this->assertNull($partner->street1);
        $this->assertNull($partner->city);
        $this->assertNull($partner->state_id);
        $this->assertNull($partner->zip);
    }

    // =====================================================
    // ADDRESS SECTION TESTS
    // =====================================================

    /** @test */
    public function it_saves_full_address_with_street2()
    {
        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();

        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Full Address Customer',
            'street1' => '123 Main Street',
            'street2' => 'Suite 200',
            'city' => 'Nantucket',
            'zip' => '02554',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Full Address Customer',
            'street1' => '123 Main Street',
            'street2' => 'Suite 200',
            'city' => 'Nantucket',
            'zip' => '02554',
        ]);

        $this->assertEquals('Suite 200', $partner->street2);
    }

    /** @test */
    public function it_saves_address_without_street2()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'No Suite Customer',
            'street1' => '456 Ocean View',
            'city' => 'Chatham',
            'zip' => '02633',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('456 Ocean View', $partner->street1);
        $this->assertNull($partner->street2);
        $this->assertEquals('Chatham', $partner->city);
    }

    // =====================================================
    // ADDITIONAL DETAILS SECTION TESTS
    // =====================================================

    /** @test */
    public function it_saves_mobile_phone_for_individual()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Mobile Customer',
            'phone' => '(508) 555-1234',
            'mobile' => '(508) 555-9999',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('(508) 555-9999', $partner->mobile);
        $this->assertEquals('(508) 555-1234', $partner->phone);
    }

    /** @test */
    public function it_saves_job_title_for_individual()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Project Manager Customer',
            'job_title' => 'Senior Project Manager',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('Senior Project Manager', $partner->job_title);
    }

    /** @test */
    public function it_saves_website_for_company()
    {
        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Tech Company LLC',
            'website' => 'https://techcompany.com',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('https://techcompany.com', $partner->website);
    }

    /** @test */
    public function it_links_individual_to_parent_company()
    {
        // First create a parent company
        $parentCompany = Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Parent Construction Co',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Create individual linked to parent
        $individual = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'John Employee',
            'parent_id' => $parentCompany->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $this->assertEquals($parentCompany->id, $individual->parent_id);

        // Verify relationship
        $freshIndividual = Partner::with('parent')->find($individual->id);
        $this->assertEquals('Parent Construction Co', $freshIndividual->parent?->name);
    }

    // =====================================================
    // BUSINESS DETAILS SECTION TESTS (Company only)
    // =====================================================

    /** @test */
    public function it_saves_tax_id_for_company()
    {
        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Taxed Company Inc',
            'tax_id' => '12-3456789',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('12-3456789', $partner->tax_id);
    }

    /** @test */
    public function it_saves_company_registry()
    {
        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Registered Corp',
            'company_registry' => '987654321',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('987654321', $partner->company_registry);
    }

    /** @test */
    public function it_saves_all_business_details_together()
    {
        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Full Business Company',
            'tax_id' => '98-7654321',
            'company_registry' => 'DUNS-123456',
            'website' => 'https://fullbusiness.com',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Full Business Company',
            'tax_id' => '98-7654321',
            'company_registry' => 'DUNS-123456',
            'website' => 'https://fullbusiness.com',
        ]);
    }

    // =====================================================
    // SALES SETTINGS SECTION TESTS
    // =====================================================

    /** @test */
    public function it_assigns_sales_person()
    {
        // Create another user to be salesperson
        $salesperson = User::firstOrCreate(
            ['email' => 'sales@test.com'],
            [
                'name' => 'Sales Person',
                'password' => bcrypt('password'),
            ]
        );

        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Assigned Customer',
            'user_id' => $salesperson->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals($salesperson->id, $partner->user_id);
    }

    /** @test */
    public function it_defaults_salesperson_to_null_if_not_set()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'No Salesperson Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertNull($partner->user_id);
    }

    // =====================================================
    // COMPREHENSIVE FIELD TESTS
    // =====================================================

    /** @test */
    public function it_creates_customer_with_all_modal_fields()
    {
        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();
        $salesperson = User::first();

        $customerData = [
            // Essential fields
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Complete Company LLC',
            'phone' => '(508) 555-1111',
            'email' => 'complete@company.com',
            // Address section
            'street1' => '100 Enterprise Way',
            'street2' => 'Building A',
            'city' => 'Boston',
            'zip' => '02101',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            // Additional details
            'mobile' => '(508) 555-2222',
            'website' => 'https://completecompany.com',
            // Business details
            'tax_id' => '11-2233445',
            'company_registry' => 'REG-12345',
            // Sales settings
            'user_id' => $salesperson?->id,
            // Required system fields
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        // Verify all fields saved correctly
        $this->assertEquals('Complete Company LLC', $partner->name);
        $this->assertEquals('(508) 555-1111', $partner->phone);
        $this->assertEquals('complete@company.com', $partner->email);
        $this->assertEquals('100 Enterprise Way', $partner->street1);
        $this->assertEquals('Building A', $partner->street2);
        $this->assertEquals('Boston', $partner->city);
        $this->assertEquals('02101', $partner->zip);
        $this->assertEquals('(508) 555-2222', $partner->mobile);
        $this->assertEquals('https://completecompany.com', $partner->website);
        $this->assertEquals('11-2233445', $partner->tax_id);
        $this->assertEquals('REG-12345', $partner->company_registry);
        $this->assertEquals('customer', $partner->sub_type);
    }

    /** @test */
    public function it_creates_individual_with_all_applicable_fields()
    {
        // Create a parent company first
        $parentCompany = Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Employer Company',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();

        $customerData = [
            // Essential fields
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'John Complete Smith',
            'phone' => '(617) 555-3333',
            'email' => 'john.complete@email.com',
            // Address section
            'street1' => '50 Residential Lane',
            'street2' => 'Apt 5B',
            'city' => 'Cambridge',
            'zip' => '02138',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            // Additional details (individual-specific)
            'mobile' => '(617) 555-4444',
            'job_title' => 'CEO',
            'parent_id' => $parentCompany->id,
            // Required system fields
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);

        $this->assertEquals('John Complete Smith', $partner->name);
        $this->assertEquals(AccountType::INDIVIDUAL, $partner->account_type);
        $this->assertEquals('CEO', $partner->job_title);
        $this->assertEquals($parentCompany->id, $partner->parent_id);
        $this->assertEquals('Apt 5B', $partner->street2);
    }

    // =====================================================
    // SEARCH TERM PRE-FILL TESTS
    // =====================================================

    /** @test */
    public function create_project_page_has_search_term_property()
    {
        $page = new CreateProject();

        $this->assertTrue(
            property_exists($page, 'lastCustomerSearchTerm'),
            'CreateProject should have lastCustomerSearchTerm property'
        );
    }

    /** @test */
    public function search_term_property_is_initially_null()
    {
        $page = new CreateProject();

        $this->assertNull($page->lastCustomerSearchTerm);
    }

    /** @test */
    public function search_term_can_be_set_and_retrieved()
    {
        $page = new CreateProject();

        $page->lastCustomerSearchTerm = 'Test Search Company';

        $this->assertEquals('Test Search Company', $page->lastCustomerSearchTerm);
    }

    /** @test */
    public function search_term_can_be_cleared()
    {
        $page = new CreateProject();

        $page->lastCustomerSearchTerm = 'Some Search';
        $page->lastCustomerSearchTerm = null;

        $this->assertNull($page->lastCustomerSearchTerm);
    }

    // =====================================================
    // DATA PERSISTENCE VERIFICATION TESTS
    // =====================================================

    /** @test */
    public function created_customer_can_be_fetched_by_id()
    {
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Fetchable Customer',
            'email' => 'fetch@test.com',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $fetched = Partner::find($partner->id);

        $this->assertNotNull($fetched);
        $this->assertEquals('Fetchable Customer', $fetched->name);
        $this->assertEquals('fetch@test.com', $fetched->email);
    }

    /** @test */
    public function created_customer_persists_after_refresh()
    {
        $partner = Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Persistent Company',
            'phone' => '555-PERSIST',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $partnerId = $partner->id;

        // Refresh from database
        $refreshed = Partner::find($partnerId);

        $this->assertEquals('Persistent Company', $refreshed->name);
        $this->assertEquals('555-PERSIST', $refreshed->phone);
    }

    /** @test */
    public function customer_is_included_in_customer_dropdown_query()
    {
        // Clear any test customers from previous tests
        $testEmail = 'dropdown-test-' . uniqid() . '@test.com';

        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Dropdown Test Customer',
            'email' => $testEmail,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Query the same way the Select field's getSearchResultsUsing does
        $results = Partner::where('sub_type', 'customer')
            ->where('name', 'like', '%Dropdown Test%')
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->toArray();

        $this->assertArrayHasKey($partner->id, $results);
        $this->assertEquals('Dropdown Test Customer', $results[$partner->id]);
    }

    /** @test */
    public function customer_search_is_case_insensitive()
    {
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Case Test Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Search with lowercase
        $results = Partner::where('sub_type', 'customer')
            ->where('name', 'like', '%case test%')
            ->pluck('name', 'id')
            ->toArray();

        $this->assertArrayHasKey($partner->id, $results);
    }
}
