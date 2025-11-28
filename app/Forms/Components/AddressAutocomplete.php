<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

/**
 * Google Places Address Autocomplete Component
 *
 * A drop-in replacement for TextInput that provides Google Places autocomplete
 * and auto-populates related address fields (city, state, zip, country).
 *
 * Usage:
 *   AddressAutocomplete::make('street1')
 *       ->label('Street Address')
 *       ->maxLength(255)
 *       // Auto-populates city, zip, state_id, country_id by default
 *
 * Custom field mapping:
 *   AddressAutocomplete::make('street1')
 *       ->cityField('custom_city')
 *       ->stateField('custom_state')
 *       ->zipField('custom_zip')
 *       ->countryField('custom_country')
 */
class AddressAutocomplete extends TextInput
{
    protected string $view = 'forms.components.address-autocomplete';

    // Target field names for auto-population (defaults match codebase standard)
    protected ?string $cityField = 'city';
    protected ?string $stateField = 'state_id';
    protected ?string $zipField = 'zip';
    protected ?string $countryField = 'country_id';

    // Country restrictions for Google Places
    protected array $countries = ['us', 'ca'];

    protected function setUp(): void
    {
        parent::setUp();

        // Set a helpful placeholder
        $this->placeholder('Start typing an address...');
    }

    /**
     * Set the target field name for city auto-population
     */
    public function cityField(?string $field): static
    {
        $this->cityField = $field;
        return $this;
    }

    /**
     * Set the target field name for state auto-population
     */
    public function stateField(?string $field): static
    {
        $this->stateField = $field;
        return $this;
    }

    /**
     * Set the target field name for ZIP code auto-population
     */
    public function zipField(?string $field): static
    {
        $this->zipField = $field;
        return $this;
    }

    /**
     * Set the target field name for country auto-population
     */
    public function countryField(?string $field): static
    {
        $this->countryField = $field;
        return $this;
    }

    /**
     * Set country restrictions for Google Places autocomplete
     *
     * @param array $countries Array of ISO 3166-1 alpha-2 country codes (e.g., ['us', 'ca'])
     */
    public function countries(array $countries): static
    {
        $this->countries = array_map('strtolower', $countries);
        return $this;
    }

    /**
     * Disable auto-population of city field
     */
    public function withoutCityField(): static
    {
        $this->cityField = null;
        return $this;
    }

    /**
     * Disable auto-population of state field
     */
    public function withoutStateField(): static
    {
        $this->stateField = null;
        return $this;
    }

    /**
     * Disable auto-population of ZIP field
     */
    public function withoutZipField(): static
    {
        $this->zipField = null;
        return $this;
    }

    /**
     * Disable auto-population of country field
     */
    public function withoutCountryField(): static
    {
        $this->countryField = null;
        return $this;
    }

    // Getters for Blade view
    public function getCityField(): ?string
    {
        return $this->cityField;
    }

    public function getStateField(): ?string
    {
        return $this->stateField;
    }

    public function getZipField(): ?string
    {
        return $this->zipField;
    }

    public function getCountryField(): ?string
    {
        return $this->countryField;
    }

    public function getCountries(): array
    {
        return $this->countries;
    }

    /**
     * Get the Google Places API key from config
     */
    public function getGoogleApiKey(): ?string
    {
        return config('services.google.places_api_key');
    }
}
