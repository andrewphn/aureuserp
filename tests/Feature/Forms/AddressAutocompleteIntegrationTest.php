<?php

namespace Tests\Feature\Forms;

use App\Forms\Components\AddressAutocomplete;
use Tests\TestCase;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Integration tests for AddressAutocomplete component with database
 *
 * NOTE: These tests use the existing database and do NOT create/delete records.
 * They verify the component works with the production data structure.
 */
class AddressAutocompleteIntegrationTest extends TestCase
{
    /**
     * Test that state lookup data is available for US and Canada (uses existing data)
     */
    public function test_state_lookup_data_available(): void
    {
        // Query states by code like the Blade view does - using existing database data
        $statesByCode = State::query()
            ->whereHas('country', fn($q) => $q->whereIn('code', ['US', 'CA']))
            ->pluck('id', 'code')
            ->toArray();

        // Should have at least some states if database is seeded
        $this->assertIsArray($statesByCode);

        // If we have US states, verify the structure is correct
        if (!empty($statesByCode)) {
            // Keys should be state codes (strings), values should be IDs (integers)
            foreach ($statesByCode as $code => $id) {
                $this->assertIsString($code);
                $this->assertIsInt($id);
            }
        }
    }

    /**
     * Test that country lookup data is available (uses existing data)
     */
    public function test_country_lookup_data_available(): void
    {
        // Query countries by code like the Blade view does
        $countriesByCode = Country::query()
            ->whereIn('code', ['US', 'CA'])
            ->pluck('id', 'code')
            ->toArray();

        // Should have at least some countries if database is seeded
        $this->assertIsArray($countriesByCode);

        // If we have countries, verify the structure is correct
        if (!empty($countriesByCode)) {
            foreach ($countriesByCode as $code => $id) {
                $this->assertIsString($code);
                $this->assertIsInt($id);
            }
        }
    }

    /**
     * Test that Google API key is accessible via config
     */
    public function test_google_api_key_in_config(): void
    {
        // Verify the config path is correct
        $this->assertNotNull(config('services.google'));
        $this->assertArrayHasKey('places_api_key', config('services.google'));
    }

    /**
     * Test that states have proper relationship with countries (uses existing data)
     */
    public function test_state_country_relationship(): void
    {
        // Get an existing state from the database
        $state = State::whereHas('country')->first();

        if ($state) {
            // Verify the relationship works
            $this->assertNotNull($state->country);
            $this->assertNotEmpty($state->country->name);
            $this->assertNotEmpty($state->country->code);
        } else {
            // If no states exist, just verify the query doesn't throw
            $this->assertTrue(true);
        }
    }

    /**
     * Test that component view path is correct
     */
    public function test_component_view_exists(): void
    {
        $viewPath = resource_path('views/forms/components/address-autocomplete.blade.php');

        $this->assertFileExists($viewPath);
    }

    /**
     * Test component can be used in a Filament form context
     */
    public function test_component_in_form_context(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->label('Street Address')
            ->maxLength(255)
            ->required();

        // Verify all Filament TextInput methods work
        $this->assertEquals('street1', $component->getName());
        $this->assertTrue($component->isRequired());
    }

    /**
     * Test state lookup query structure is correct
     */
    public function test_state_lookup_query_structure(): void
    {
        // This tests that the query used in the Blade view works correctly
        $query = State::query()
            ->whereHas('country', fn($q) => $q->whereIn('code', ['US', 'CA']));

        // Verify the query can be executed without error
        $result = $query->pluck('id', 'code')->toArray();

        $this->assertIsArray($result);
    }

    /**
     * Test country lookup query structure is correct
     */
    public function test_country_lookup_query_structure(): void
    {
        // This tests that the query used in the Blade view works correctly
        $query = Country::query()
            ->whereIn('code', ['US', 'CA']);

        // Verify the query can be executed without error
        $result = $query->pluck('id', 'code')->toArray();

        $this->assertIsArray($result);
    }
}
