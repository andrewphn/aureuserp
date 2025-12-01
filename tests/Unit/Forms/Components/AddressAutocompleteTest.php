<?php

namespace Tests\Unit\Forms\Components;

use Webkul\Support\Filament\Forms\Components\AddressAutocomplete;
use Tests\TestCase;

/**
 * Unit tests for AddressAutocomplete Filament component
 */
class AddressAutocompleteTest extends TestCase
{
    /**
     * Test that the component can be instantiated
     */
    public function test_can_instantiate_component(): void
    {
        $component = AddressAutocomplete::make('street1');

        $this->assertInstanceOf(AddressAutocomplete::class, $component);
    }

    /**
     * Test default field mappings
     */
    public function test_default_field_mappings(): void
    {
        $component = AddressAutocomplete::make('street1');

        $this->assertEquals('city', $component->getCityField());
        $this->assertEquals('state_id', $component->getStateField());
        $this->assertEquals('zip', $component->getZipField());
        $this->assertEquals('country_id', $component->getCountryField());
    }

    /**
     * Test default country restrictions
     */
    public function test_default_countries(): void
    {
        $component = AddressAutocomplete::make('street1');

        $this->assertEquals(['us', 'ca'], $component->getCountries());
    }

    /**
     * Test custom city field mapping
     */
    public function test_custom_city_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->cityField('custom_city');

        $this->assertEquals('custom_city', $component->getCityField());
    }

    /**
     * Test custom state field mapping
     */
    public function test_custom_state_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->stateField('custom_state');

        $this->assertEquals('custom_state', $component->getStateField());
    }

    /**
     * Test custom zip field mapping
     */
    public function test_custom_zip_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->zipField('custom_zip');

        $this->assertEquals('custom_zip', $component->getZipField());
    }

    /**
     * Test custom country field mapping
     */
    public function test_custom_country_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->countryField('custom_country');

        $this->assertEquals('custom_country', $component->getCountryField());
    }

    /**
     * Test custom country restrictions
     */
    public function test_custom_countries(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->countries(['US', 'MX', 'CA']);

        // Should be normalized to lowercase
        $this->assertEquals(['us', 'mx', 'ca'], $component->getCountries());
    }

    /**
     * Test disabling city field
     */
    public function test_without_city_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->withoutCityField();

        $this->assertNull($component->getCityField());
    }

    /**
     * Test disabling state field
     */
    public function test_without_state_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->withoutStateField();

        $this->assertNull($component->getStateField());
    }

    /**
     * Test disabling zip field
     */
    public function test_without_zip_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->withoutZipField();

        $this->assertNull($component->getZipField());
    }

    /**
     * Test disabling country field
     */
    public function test_without_country_field(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->withoutCountryField();

        $this->assertNull($component->getCountryField());
    }

    /**
     * Test method chaining
     */
    public function test_method_chaining(): void
    {
        $component = AddressAutocomplete::make('street1')
            ->cityField('my_city')
            ->stateField('my_state')
            ->zipField('my_zip')
            ->countryField('my_country')
            ->countries(['us']);

        $this->assertEquals('my_city', $component->getCityField());
        $this->assertEquals('my_state', $component->getStateField());
        $this->assertEquals('my_zip', $component->getZipField());
        $this->assertEquals('my_country', $component->getCountryField());
        $this->assertEquals(['us'], $component->getCountries());
    }

    /**
     * Test API key retrieval from config
     */
    public function test_get_google_api_key_from_config(): void
    {
        // Set a test API key
        config(['services.google.places_api_key' => 'test_api_key_12345']);

        $component = AddressAutocomplete::make('street1');

        $this->assertEquals('test_api_key_12345', $component->getGoogleApiKey());
    }

    /**
     * Test null API key when not configured
     */
    public function test_null_api_key_when_not_configured(): void
    {
        // Clear the config
        config(['services.google.places_api_key' => null]);

        $component = AddressAutocomplete::make('street1');

        $this->assertNull($component->getGoogleApiKey());
    }

    /**
     * Test that component extends TextInput
     */
    public function test_extends_text_input(): void
    {
        $component = AddressAutocomplete::make('street1');

        // TextInput methods should be available
        $component->maxLength(255);
        $component->required();

        $this->assertTrue(true); // If we get here, methods were accessible
    }

    /**
     * Test component name
     */
    public function test_component_name(): void
    {
        $component = AddressAutocomplete::make('street1');

        $this->assertEquals('street1', $component->getName());
    }

    /**
     * Test fluent interface returns correct type
     */
    public function test_fluent_returns_self(): void
    {
        $component = AddressAutocomplete::make('street1');

        $result = $component->cityField('custom');
        $this->assertInstanceOf(AddressAutocomplete::class, $result);

        $result = $component->stateField('custom');
        $this->assertInstanceOf(AddressAutocomplete::class, $result);

        $result = $component->zipField('custom');
        $this->assertInstanceOf(AddressAutocomplete::class, $result);

        $result = $component->countryField('custom');
        $this->assertInstanceOf(AddressAutocomplete::class, $result);

        $result = $component->countries(['us']);
        $this->assertInstanceOf(AddressAutocomplete::class, $result);

        $result = $component->withoutCityField();
        $this->assertInstanceOf(AddressAutocomplete::class, $result);
    }
}
