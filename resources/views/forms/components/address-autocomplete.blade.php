@php
    $apiKey = $getGoogleApiKey();
    $cityField = $getCityField();
    $stateField = $getStateField();
    $zipField = $getZipField();
    $countryField = $getCountryField();
    $countries = $getCountries();

    // Pre-load state lookup data for US and Canada
    $statesByCode = \Webkul\Support\Models\State::query()
        ->whereHas('country', fn($q) => $q->whereIn('code', ['US', 'CA']))
        ->pluck('id', 'code')
        ->toArray();

    // Pre-load country lookup data
    $countriesByCode = \Webkul\Support\Models\Country::query()
        ->whereIn('code', ['US', 'CA'])
        ->pluck('id', 'code')
        ->toArray();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <x-filament::input.wrapper
        :disabled="$isDisabled()"
        :prefix="$getPrefixLabel()"
        :suffix="$getSuffixLabel()"
        :valid="! $errors->has($getStatePath())"
        class="fi-fo-text-input"
    >
        <div
            wire:ignore.self
            x-data="addressAutocomplete({
                statePath: '{{ $getStatePath() }}',
                apiKey: '{{ $apiKey }}',
                countries: @js($countries),
                cityField: @js($cityField),
                stateField: @js($stateField),
                zipField: @js($zipField),
                countryField: @js($countryField),
                statesByCode: @js($statesByCode),
                countriesByCode: @js($countriesByCode),
                initialValue: @js($getState()),
                isDisabled: @js($isDisabled()),
                inputId: '{{ $getId() }}',
                placeholder: @js($getPlaceholder() ?? 'Start typing an address...'),
            })"
            x-init="init()"
            class="w-full relative"
            x-ref="container"
        >
            {{-- Hidden input for Livewire binding --}}
            <input
                type="hidden"
                x-ref="hiddenInput"
                {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}"
            />
            {{-- Address input with Google Places Autocomplete --}}
            <input
                x-ref="addressInput"
                type="text"
                x-model="displayValue"
                @if($isDisabled()) disabled @endif
                {!! $isRequired() ? 'required' : '' !!}
                {!! $getPlaceholder() ? 'placeholder="' . e($getPlaceholder()) . '"' : 'placeholder="Start typing an address..."' !!}
                {!! $getMaxLength() ? 'maxlength="' . $getMaxLength() . '"' : '' !!}
                id="{{ $getId() }}"
                @class([
                    'fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75',
                    'placeholder:text-gray-400 focus:ring-0',
                    'disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)]',
                    'disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)]',
                    'sm:text-sm sm:leading-6',
                    'dark:text-white dark:placeholder:text-gray-500',
                    'dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)]',
                    'dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)]',
                ])
                autocomplete="off"
                @input.debounce.300ms="onManualInput($event)"
                @blur="onBlur()"
            />
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>

@once
@push('scripts')
<style>
    /* Style the Google Places autocomplete dropdown */
    .pac-container {
        z-index: 10000 !important;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        border: 1px solid rgb(229 231 235);
        font-family: inherit;
        margin-top: 4px;
    }
    .pac-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        font-size: 0.875rem;
        line-height: 1.25rem;
        border-top: 1px solid rgb(243 244 246);
    }
    .pac-item:first-child {
        border-top: none;
    }
    .pac-item:hover, .pac-item-selected {
        background-color: rgb(243 244 246);
    }
    .pac-item-query {
        font-size: 0.875rem;
        color: rgb(17 24 39);
    }
    .pac-matched {
        font-weight: 600;
    }
    .pac-icon {
        margin-right: 0.5rem;
    }
    /* Dark mode support */
    .dark .pac-container {
        background-color: rgb(31 41 55);
        border-color: rgb(55 65 81);
    }
    .dark .pac-item {
        border-color: rgb(55 65 81);
        color: rgb(229 231 235);
    }
    .dark .pac-item:hover, .dark .pac-item-selected {
        background-color: rgb(55 65 81);
    }
    .dark .pac-item-query {
        color: rgb(243 244 246);
    }
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addressAutocomplete', (config) => ({
        statePath: config.statePath,
        apiKey: config.apiKey,
        countries: config.countries,
        cityField: config.cityField,
        stateField: config.stateField,
        zipField: config.zipField,
        countryField: config.countryField,
        statesByCode: config.statesByCode,
        countriesByCode: config.countriesByCode,
        initialValue: config.initialValue,
        isDisabled: config.isDisabled,
        inputId: config.inputId,
        placeholder: config.placeholder,
        placeAutocomplete: null,
        initialized: false,
        observer: null,
        displayValue: config.initialValue || '',

        async init() {
            const input = this.$refs.addressInput;

            // Check if API key is missing
            if (!this.apiKey) {
                console.log('[AddressAutocomplete] Skipping init - no API key configured');
                return;
            }

            // If currently disabled, set up observer to init when enabled
            if (input && input.disabled) {
                console.log('[AddressAutocomplete] Input is disabled, watching for enable...');
                this.watchForEnable();
                return;
            }

            await this.doInit();
        },

        destroy() {
            // Clean up observer when component is destroyed
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
            // Clean up interval
            if (this.enableCheckInterval) {
                clearInterval(this.enableCheckInterval);
                this.enableCheckInterval = null;
            }
            // Unbind autocomplete (legacy API doesn't need DOM cleanup)
            this.placeAutocomplete = null;
            this.initialized = false;
        },

        watchForEnable() {
            const input = this.$refs.addressInput;
            if (!input) return;

            // Clean up existing observer if any
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }

            // Use MutationObserver to watch for disabled attribute removal
            this.observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                        if (!input.disabled && !this.initialized) {
                            console.log('[AddressAutocomplete] Input became enabled, initializing...');
                            this.doInit();
                        }
                    }
                });
            });

            this.observer.observe(input, { attributes: true });

            // Also check periodically in case mutation observer misses changes
            // (can happen with Livewire morphing)
            this.enableCheckInterval = setInterval(() => {
                const currentInput = this.$refs.addressInput;
                if (currentInput && !currentInput.disabled && !this.initialized) {
                    console.log('[AddressAutocomplete] Enable check: input is now enabled, initializing...');
                    clearInterval(this.enableCheckInterval);
                    this.doInit();
                }
            }, 500);

            // Clear interval after 30 seconds to avoid memory leaks
            setTimeout(() => {
                if (this.enableCheckInterval) {
                    clearInterval(this.enableCheckInterval);
                }
            }, 30000);
        },

        async doInit() {
            if (this.initialized) return;

            // Double-check not disabled
            const input = this.$refs.addressInput;
            if (input && input.disabled) {
                return;
            }

            try {
                await this.loadGooglePlacesApi();
                await this.initializeAutocomplete();
                this.initialized = true;

                console.log('[AddressAutocomplete] Initialized successfully');

                // Clean up observer
                if (this.observer) {
                    this.observer.disconnect();
                    this.observer = null;
                }
            } catch (error) {
                console.error('[AddressAutocomplete] Failed to initialize:', error);
            }
        },

        async loadGooglePlacesApi() {
            // Check if Places library is already loaded
            if (window.google?.maps?.places?.Autocomplete) {
                console.log('[AddressAutocomplete] Google Places API already loaded');
                return;
            }

            // Check if script is already being loaded
            if (window.googlePlacesLoading) {
                return window.googlePlacesLoading;
            }

            // Load Google Maps API using the inline bootstrap loader for importLibrary support
            window.googlePlacesLoading = new Promise(async (resolve, reject) => {
                try {
                    // Check if the bootstrap loader is already set up
                    if (!window.google?.maps?.importLibrary) {
                        // Set up the inline bootstrap loader (required for importLibrary)
                        ((g) => {
                            var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__", m = document, b = window;
                            b = b[c] || (b[c] = {});
                            var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams,
                                u = () => h || (h = new Promise(async (f, n) => {
                                    await (a = m.createElement("script"));
                                    e.set("libraries", [...r] + "");
                                    for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                                    e.set("callback", c + ".maps." + q);
                                    a.src = `https://maps.googleapis.com/maps/api/js?` + e;
                                    d[q] = f;
                                    a.onerror = () => h = n(Error(p + " could not load."));
                                    a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                                    m.head.append(a)
                                }));
                            d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n))
                        })({
                            key: this.apiKey,
                            v: "weekly"
                        });
                    }

                    // Now import the Places library using the bootstrap loader
                    await google.maps.importLibrary('places');
                    console.log('[AddressAutocomplete] Google Places API loaded via importLibrary');
                    resolve();
                } catch (error) {
                    console.error('[AddressAutocomplete] Failed to load Google Places API:', error);
                    reject(error);
                }
            });

            return window.googlePlacesLoading;
        },

        async initializeAutocomplete() {
            const input = this.$refs.addressInput;
            if (!input) {
                console.warn('[AddressAutocomplete] Input ref not found');
                return;
            }

            // Use the legacy Autocomplete API which attaches to existing input
            console.log('[AddressAutocomplete] Using legacy Autocomplete API');
            await this.initLegacyAutocomplete(input);
        },

        async initLegacyAutocomplete(input) {
            // Build options for legacy Autocomplete
            // Use 'street_address' instead of 'address' to limit results to actual street addresses
            // This prevents showing cities, states, and other place types when typing
            const options = {
                types: ['street_address'],
                fields: ['address_components', 'formatted_address', 'geometry']
            };

            // Add country restrictions if specified
            if (this.countries && this.countries.length > 0) {
                options.componentRestrictions = { country: this.countries };
            }

            // Create the legacy Autocomplete attached to the input
            this.placeAutocomplete = new google.maps.places.Autocomplete(input, options);

            // Listen for place selection
            this.placeAutocomplete.addListener('place_changed', () => {
                const place = this.placeAutocomplete.getPlace();

                if (!place.address_components) {
                    console.warn('[AddressAutocomplete] No address components in place');
                    return;
                }

                console.log('[AddressAutocomplete] Place selected:', place.formatted_address);
                this.populateAddressFieldsLegacy(place);
            });

            // Prevent form submission on Enter key
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            console.log('[AddressAutocomplete] Legacy Autocomplete initialized');
        },

        async populateAddressFieldsLegacy(place) {
            const components = this.extractAddressComponentsLegacy(place);
            console.log('[AddressAutocomplete] Extracted components:', components);

            // Build the street address
            const streetAddress = components.street;
            if (streetAddress) {
                this.displayValue = streetAddress;
                this.$wire.set(this.statePath, streetAddress);
            }

            // Auto-populate city
            if (this.cityField && components.city) {
                this.setFieldValue(this.cityField, components.city);
            }

            // Auto-populate ZIP
            if (this.zipField && components.zip) {
                this.setFieldValue(this.zipField, components.zip);
            }

            // IMPORTANT: Set country FIRST, then wait for Livewire to process
            // The State field depends on Country being set first (filtered by country_id)
            // The Country field's afterStateUpdated also clears state_id, so we must wait
            if (this.countryField && components.countryCode) {
                const countryId = this.countriesByCode[components.countryCode];
                if (countryId) {
                    this.setFieldValue(this.countryField, countryId);
                    console.log('[AddressAutocomplete] Set country_id:', countryId, 'for code:', components.countryCode);

                    // Commit country change and wait for Livewire to process
                    // This ensures state options are loaded before we try to set state_id
                    await this.$wire.$commit();
                    console.log('[AddressAutocomplete] Country committed, waiting for state options to load...');

                    // Small delay to ensure Livewire has fully processed the country change
                    // and loaded the state options
                    await new Promise(resolve => setTimeout(resolve, 200));
                }
            }

            // NOW set state after country has been processed
            if (this.stateField && components.stateCode) {
                const stateId = this.statesByCode[components.stateCode];
                if (stateId) {
                    this.setFieldValue(this.stateField, stateId);
                    console.log('[AddressAutocomplete] Set state_id:', stateId, 'for code:', components.stateCode);

                    // Final commit to update the state field display
                    await this.$wire.$commit();
                    console.log('[AddressAutocomplete] State committed');
                } else {
                    console.warn('[AddressAutocomplete] State not found for code:', components.stateCode);
                }
            }
        },

        setFieldValue(fieldName, value) {
            let fullPath;

            if (fieldName.includes('.')) {
                const dataPrefix = this.statePath.split('.')[0];
                fullPath = dataPrefix + '.' + fieldName;
            } else {
                const pathParts = this.statePath.split('.');
                pathParts.pop();
                const basePath = pathParts.length > 0 ? pathParts.join('.') + '.' : '';
                fullPath = basePath + fieldName;
            }

            console.log('[AddressAutocomplete] Setting', fullPath, 'to', value);
            this.$wire.set(fullPath, value);
        },

        extractAddressComponentsLegacy(place) {
            const components = {
                streetNumber: '',
                route: '',
                city: '',
                stateCode: '',
                stateName: '',
                zip: '',
                countryCode: '',
                countryName: ''
            };

            // Legacy API uses address_components with long_name/short_name
            const addressComponents = place.address_components || [];

            addressComponents.forEach(component => {
                const types = component.types || [];
                const longName = component.long_name || '';
                const shortName = component.short_name || '';

                if (types.includes('street_number')) {
                    components.streetNumber = shortName;
                } else if (types.includes('route')) {
                    components.route = longName;
                } else if (types.includes('locality')) {
                    components.city = longName;
                } else if (types.includes('sublocality_level_1') && !components.city) {
                    components.city = longName;
                } else if (types.includes('administrative_area_level_1')) {
                    components.stateCode = shortName;
                    components.stateName = longName;
                } else if (types.includes('postal_code')) {
                    components.zip = shortName;
                } else if (types.includes('country')) {
                    components.countryCode = shortName;
                    components.countryName = longName;
                }
            });

            // Build the full street address
            components.street = [components.streetNumber, components.route]
                .filter(Boolean)
                .join(' ');

            return components;
        },

        onManualInput(event) {
            // Update Livewire state when user types manually
            this.$wire.set(this.statePath, event.target.value);
        },

        onBlur() {
            // Sync any manual changes to Livewire
            this.$wire.set(this.statePath, this.displayValue);
        }
    }));
});
</script>
@endpush
@endonce
