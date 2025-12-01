<?php

namespace Webkul\Project\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\CreateProject;
use Webkul\Security\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Integration tests for Customer Creation Modal in Project Wizard
 *
 * Tests the full workflow of creating customers inline during project creation.
 * These tests focus on:
 * - Data layer and business logic validation
 * - Field validation rules (email format, required fields, etc.)
 * - Data persistence for all modal sections
 * - Search term pre-fill functionality
 * - Customer-vendor separation
 */
class CustomerCreationModalIntegrationTest extends TestCase
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
    public function create_project_page_class_exists()
    {
        $this->assertTrue(
            class_exists(CreateProject::class),
            'CreateProject page class should exist'
        );
    }

    /** @test */
    public function create_project_page_has_customer_creation_method()
    {
        $page = new CreateProject();

        $this->assertTrue(
            method_exists($page, 'getCustomerCreationFormComponents'),
            'CreateProject should have getCustomerCreationFormComponents method'
        );
    }

    /** @test */
    public function customer_creation_method_returns_array()
    {
        $page = new CreateProject();

        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('getCustomerCreationFormComponents');
        $method->setAccessible(true);

        $components = $method->invoke($page);

        $this->assertIsArray($components);
        $this->assertNotEmpty($components);
    }

    /** @test */
    public function customer_with_valid_data_can_be_created_via_form_data()
    {
        // Simulate what createOptionUsing does
        $formData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Test Integration Customer',
            'phone' => '555-123-4567',
            'email' => 'integration@test.com',
        ];

        // Apply the same transformation as createOptionUsing
        $formData['sub_type'] = 'customer';
        $formData['creator_id'] = $this->adminUser->id;

        $partner = Partner::create($formData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Test Integration Customer',
            'sub_type' => 'customer',
            'email' => 'integration@test.com',
        ]);

        $this->assertEquals($partner->id, Partner::where('email', 'integration@test.com')->first()->id);
    }

    /** @test */
    public function company_customer_can_be_created_with_business_details()
    {
        $formData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Integration Test Company LLC',
            'phone' => '(508) 555-9999',
            'email' => 'company@integration.test',
            'city' => 'Boston',
            'zip' => '02101',
            'tax_id' => '12-3456789',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($formData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Integration Test Company LLC',
            'account_type' => AccountType::COMPANY->value,
            'sub_type' => 'customer',
            'tax_id' => '12-3456789',
        ]);
    }

    /** @test */
    public function newly_created_customer_appears_in_partner_query()
    {
        // Create a customer using the same approach as the form
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Queryable Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Query the same way the Select field does
        $customers = Partner::where('sub_type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        $this->assertArrayHasKey($partner->id, $customers->toArray());
        $this->assertEquals('Queryable Customer', $customers[$partner->id]);
    }

    /** @test */
    public function customer_creation_respects_account_type_field()
    {
        $individualData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'John Individual',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $companyData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Corporate Company',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $individual = Partner::create($individualData);
        $company = Partner::create($companyData);

        // Compare enum values (model casts to enum)
        $this->assertEquals(AccountType::INDIVIDUAL, $individual->account_type);
        $this->assertEquals(AccountType::COMPANY, $company->account_type);
    }

    /** @test */
    public function created_customer_has_correct_creator_id()
    {
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Created By Test User',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $this->assertEquals($this->adminUser->id, $partner->creator_id);
    }

    /** @test */
    public function vendors_are_excluded_from_customer_query()
    {
        // Create a customer
        Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Real Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // Create a vendor
        Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Supply Vendor',
            'sub_type' => 'vendor',
            'creator_id' => $this->adminUser->id,
        ]);

        // Query as the Select field does
        $customers = Partner::where('sub_type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        $this->assertContains('Real Customer', $customers->toArray());
        $this->assertNotContains('Supply Vendor', $customers->toArray());
    }

    /** @test */
    public function customer_can_be_created_with_full_address()
    {
        $formData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Full Address Customer',
            'phone' => '555-111-2222',
            'email' => 'address@test.com',
            'street1' => '123 Main Street',
            'street2' => 'Suite 100',
            'city' => 'Nantucket',
            'zip' => '02554',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($formData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Full Address Customer',
            'street1' => '123 Main Street',
            'street2' => 'Suite 100',
            'city' => 'Nantucket',
            'zip' => '02554',
        ]);
    }

    /** @test */
    public function customer_can_be_created_without_optional_fields()
    {
        // Minimum required fields only
        $formData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Minimal Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($formData);

        $this->assertDatabaseHas('partners_partners', [
            'name' => 'Minimal Customer',
            'sub_type' => 'customer',
        ]);

        // Optional fields should be null
        $this->assertNull($partner->phone);
        $this->assertNull($partner->email);
        $this->assertNull($partner->street1);
    }

    /** @test */
    public function multiple_customers_can_be_created_sequentially()
    {
        $customers = [
            ['name' => 'Customer Alpha'],
            ['name' => 'Customer Beta'],
            ['name' => 'Customer Gamma'],
        ];

        foreach ($customers as $data) {
            Partner::create([
                'account_type' => AccountType::INDIVIDUAL->value,
                'name' => $data['name'],
                'sub_type' => 'customer',
                'creator_id' => $this->adminUser->id,
            ]);
        }

        $allCustomers = Partner::where('sub_type', 'customer')
            ->whereIn('name', ['Customer Alpha', 'Customer Beta', 'Customer Gamma'])
            ->get();

        $this->assertCount(3, $allCustomers);
    }

    /** @test */
    public function customer_creation_returns_valid_id()
    {
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Return ID Test',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // ID should be a positive integer
        $this->assertIsInt($partner->id);
        $this->assertGreaterThan(0, $partner->id);

        // Should be able to fetch by this ID
        $fetched = Partner::find($partner->id);
        $this->assertNotNull($fetched);
        $this->assertEquals('Return ID Test', $fetched->name);
    }

    // =====================================================
    // FIELD VALIDATION TESTS
    // =====================================================

    /** @test */
    public function email_validation_accepts_valid_email()
    {
        $validEmails = [
            'simple@example.com',
            'very.common@example.com',
            'disposable.style.email.with+symbol@example.com',
            'other.email-with-hyphen@example.com',
            'fully-qualified-domain@example.com',
            'user.name+tag+sorting@example.com',
            'x@example.com',
            'example-indeed@strange-example.com',
        ];

        foreach ($validEmails as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'email']
            );

            $this->assertFalse($validator->fails(), "Email {$email} should be valid");
        }
    }

    /** @test */
    public function email_validation_rejects_invalid_email()
    {
        $invalidEmails = [
            'plainaddress',
            '@missinglocal.com',
            'missing.at.sign.example.com',
            'two@@at.com',
        ];

        foreach ($invalidEmails as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'email']
            );

            $this->assertTrue($validator->fails(), "Email {$email} should be invalid");
        }
    }

    /** @test */
    public function name_field_is_required()
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => 'required']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function name_field_accepts_valid_names()
    {
        $validNames = [
            'John Smith',
            'Acme Construction LLC',
            "O'Brien & Associates",
            'Smith-Jones Corp',
            'ABC 123 Company',
        ];

        foreach ($validNames as $name) {
            $validator = Validator::make(
                ['name' => $name],
                ['name' => 'required|string|max:255']
            );

            $this->assertFalse($validator->fails(), "Name '{$name}' should be valid");
        }
    }

    /** @test */
    public function phone_accepts_various_formats()
    {
        $phoneFormats = [
            '5551234567',
            '555-123-4567',
            '(555) 123-4567',
            '+1 555 123 4567',
            '555.123.4567',
        ];

        foreach ($phoneFormats as $phone) {
            $partner = Partner::create([
                'account_type' => AccountType::INDIVIDUAL->value,
                'name' => 'Phone Format Test',
                'phone' => $phone,
                'sub_type' => 'customer',
                'creator_id' => $this->adminUser->id,
            ]);

            $this->assertEquals($phone, $partner->phone);
            $partner->delete();
        }
    }

    /** @test */
    public function url_validation_for_website()
    {
        $validUrls = [
            'https://example.com',
            'http://example.com',
            'https://www.example.com',
            'https://sub.domain.example.com',
            'https://example.com/path',
        ];

        foreach ($validUrls as $url) {
            $validator = Validator::make(
                ['website' => $url],
                ['website' => 'url']
            );

            $this->assertFalse($validator->fails(), "URL {$url} should be valid");
        }
    }

    // =====================================================
    // DATA PERSISTENCE INTEGRATION TESTS
    // =====================================================

    /** @test */
    public function customer_with_all_address_fields_persists_correctly()
    {
        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();

        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Full Address Test',
            'street1' => '789 Integration Lane',
            'street2' => 'Unit B',
            'city' => 'Test City',
            'zip' => '12345',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);
        $partnerId = $partner->id;

        // Fetch fresh from database
        $fetched = Partner::find($partnerId);

        $this->assertEquals('789 Integration Lane', $fetched->street1);
        $this->assertEquals('Unit B', $fetched->street2);
        $this->assertEquals('Test City', $fetched->city);
        $this->assertEquals('12345', $fetched->zip);
        if ($state) {
            $this->assertEquals($state->id, $fetched->state_id);
        }
        if ($country) {
            $this->assertEquals($country->id, $fetched->country_id);
        }
    }

    /** @test */
    public function company_business_details_persist_correctly()
    {
        $customerData = [
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Business Details Corp',
            'tax_id' => '99-8877665',
            'company_registry' => 'BUS-REG-001',
            'website' => 'https://businessdetails.test',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);
        $fetched = Partner::find($partner->id);

        $this->assertEquals('99-8877665', $fetched->tax_id);
        $this->assertEquals('BUS-REG-001', $fetched->company_registry);
        $this->assertEquals('https://businessdetails.test', $fetched->website);
    }

    /** @test */
    public function individual_additional_details_persist_correctly()
    {
        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Additional Details Person',
            'job_title' => 'Integration Test Manager',
            'mobile' => '555-999-8888',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);
        $fetched = Partner::find($partner->id);

        $this->assertEquals('Integration Test Manager', $fetched->job_title);
        $this->assertEquals('555-999-8888', $fetched->mobile);
    }

    /** @test */
    public function sales_person_assignment_persists()
    {
        $salesperson = User::firstOrCreate(
            ['email' => 'salesperson-integration@test.com'],
            [
                'name' => 'Integration Salesperson',
                'password' => bcrypt('password'),
            ]
        );

        $customerData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Salesperson Assigned Customer',
            'user_id' => $salesperson->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($customerData);
        $fetched = Partner::find($partner->id);

        $this->assertEquals($salesperson->id, $fetched->user_id);
    }

    // =====================================================
    // SEARCH TERM PRE-FILL INTEGRATION TESTS
    // =====================================================

    /** @test */
    public function search_term_tracking_property_exists_on_page()
    {
        $page = new CreateProject();

        $this->assertTrue(
            property_exists($page, 'lastCustomerSearchTerm'),
            'CreateProject should have lastCustomerSearchTerm property for search pre-fill'
        );
    }

    /** @test */
    public function search_term_is_nullable()
    {
        $page = new CreateProject();

        $this->assertNull($page->lastCustomerSearchTerm);

        $page->lastCustomerSearchTerm = 'Test Company Name';
        $this->assertEquals('Test Company Name', $page->lastCustomerSearchTerm);

        $page->lastCustomerSearchTerm = null;
        $this->assertNull($page->lastCustomerSearchTerm);
    }

    /** @test */
    public function search_term_preserves_special_characters()
    {
        $page = new CreateProject();

        $testStrings = [
            "O'Brien Construction",
            "Smith & Sons LLC",
            "Test-Company Inc.",
            "Müller Enterprises",
        ];

        foreach ($testStrings as $testString) {
            $page->lastCustomerSearchTerm = $testString;
            $this->assertEquals($testString, $page->lastCustomerSearchTerm);
        }
    }

    // =====================================================
    // COMPLETE WORKFLOW INTEGRATION TESTS
    // =====================================================

    /** @test */
    public function full_customer_creation_workflow_for_company()
    {
        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();
        $salesperson = User::first();

        // Simulate complete form submission
        $formData = [
            // Essential fields
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Complete Workflow Company',
            'phone' => '(555) 111-2222',
            'email' => 'workflow@company.test',
            // Address section
            'street1' => '100 Workflow Drive',
            'street2' => 'Suite 500',
            'city' => 'Workflow City',
            'zip' => '99999',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            // Additional details
            'mobile' => '(555) 333-4444',
            'website' => 'https://workflow.test',
            // Business details
            'tax_id' => '55-1234567',
            'company_registry' => 'WORK-FLOW-001',
            // Sales settings
            'user_id' => $salesperson?->id,
        ];

        // Apply same transformation as createOptionUsing
        $formData['sub_type'] = 'customer';
        $formData['creator_id'] = $this->adminUser->id;

        $partner = Partner::create($formData);

        // Verify complete persistence
        $this->assertDatabaseHas('partners_partners', [
            'id' => $partner->id,
            'name' => 'Complete Workflow Company',
            'account_type' => AccountType::COMPANY->value,
            'sub_type' => 'customer',
            'phone' => '(555) 111-2222',
            'email' => 'workflow@company.test',
            'street1' => '100 Workflow Drive',
            'street2' => 'Suite 500',
            'city' => 'Workflow City',
            'zip' => '99999',
            'tax_id' => '55-1234567',
            'company_registry' => 'WORK-FLOW-001',
            'website' => 'https://workflow.test',
        ]);

        // Verify it appears in customer dropdown query
        $customers = Partner::where('sub_type', 'customer')
            ->where('name', 'like', '%Complete Workflow%')
            ->pluck('name', 'id');

        $this->assertArrayHasKey($partner->id, $customers->toArray());
    }

    /** @test */
    public function full_customer_creation_workflow_for_individual()
    {
        // Create a parent company first
        $parentCompany = Partner::create([
            'account_type' => AccountType::COMPANY->value,
            'name' => 'Parent Workflow Corp',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        $country = \Webkul\Support\Models\Country::first();
        $state = \Webkul\Support\Models\State::first();

        $formData = [
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Individual Workflow Person',
            'phone' => '(555) 555-6666',
            'email' => 'individual@workflow.test',
            'street1' => '200 Person Lane',
            'city' => 'Person City',
            'zip' => '88888',
            'state_id' => $state?->id,
            'country_id' => $country?->id,
            'mobile' => '(555) 777-8888',
            'job_title' => 'Workflow Manager',
            'parent_id' => $parentCompany->id,
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ];

        $partner = Partner::create($formData);

        // Verify individual-specific fields
        $fetched = Partner::find($partner->id);
        $this->assertEquals(AccountType::INDIVIDUAL, $fetched->account_type);
        $this->assertEquals('Workflow Manager', $fetched->job_title);
        $this->assertEquals($parentCompany->id, $fetched->parent_id);

        // Verify parent relationship
        $this->assertEquals('Parent Workflow Corp', $fetched->parent?->name);
    }

    /** @test */
    public function customer_id_returned_can_be_used_in_project()
    {
        $partner = Partner::create([
            'account_type' => AccountType::INDIVIDUAL->value,
            'name' => 'Project Customer',
            'sub_type' => 'customer',
            'creator_id' => $this->adminUser->id,
        ]);

        // The ID returned should be usable as partner_id in project
        $this->assertIsInt($partner->id);
        $this->assertGreaterThan(0, $partner->id);

        // Should be retrievable via the same query used in the Select field
        $found = Partner::find($partner->id);
        $this->assertNotNull($found);
        $this->assertEquals('customer', $found->sub_type);
    }
}
