<div class="max-w-3xl mx-auto">
    @if($submitted)
        {{-- Success State --}}
        <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-semibold text-green-800 mb-2">Thank You!</h2>
            <p class="text-green-700">
                Your inquiry has been received. We'll be in touch within 1-2 business days.
            </p>
        </div>
    @else
        {{-- Progress Indicator --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <button
                        wire:click="goToStep({{ $i }})"
                        class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}"
                        @if($i > $currentStep + 1) disabled @endif
                    >
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200
                            {{ $currentStep >= $i ? 'bg-primary-600 border-primary-600 text-white' : 'border-gray-300 text-gray-500' }}
                            {{ $i <= $currentStep ? 'cursor-pointer hover:bg-primary-700' : '' }}"
                        >
                            @if($currentStep > $i)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        @if($i < $totalSteps)
                            <div class="flex-1 h-1 mx-2 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-200' }}"></div>
                        @endif
                    </button>
                @endfor
            </div>
            <div class="flex justify-between mt-2 text-sm text-gray-600">
                <span>Contact Info</span>
                <span>Project Details</span>
                <span>Review</span>
            </div>
        </div>

        {{-- Error Message --}}
        @if($errorMessage)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-700">{{ $errorMessage }}</p>
            </div>
        @endif

        <form wire:submit="submit">
            {{-- Honeypot fields (hidden from users, visible to bots) --}}
            <div class="hidden" aria-hidden="true">
                <input type="text" name="website" wire:model="website" tabindex="-1" autocomplete="off">
                <input type="text" name="url" wire:model="url" tabindex="-1" autocomplete="off">
                <input type="text" name="_gotcha" wire:model="_gotcha" tabindex="-1" autocomplete="off">
            </div>

            {{-- Step 1: Contact Information --}}
            <div x-show="$wire.currentStep === 1" x-cloak>
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Contact Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- First Name --}}
                    <div>
                        <label for="firstname" class="block text-sm font-medium text-gray-700 mb-1">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="firstname"
                            wire:model="firstname"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required
                        >
                        @error('firstname') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Last Name --}}
                    <div>
                        <label for="lastname" class="block text-sm font-medium text-gray-700 mb-1">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="lastname"
                            wire:model="lastname"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required
                        >
                        @error('lastname') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required
                        >
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Phone <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            wire:model="phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required
                        >
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Company --}}
                    <div class="md:col-span-2">
                        <label for="company" class="block text-sm font-medium text-gray-700 mb-1">
                            Company (optional)
                        </label>
                        <input
                            type="text"
                            id="company"
                            wire:model="company"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>

                    {{-- Preferred Contact Method --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Preferred Contact Method
                        </label>
                        <div class="flex gap-4">
                            @foreach(['email' => 'Email', 'phone' => 'Phone', 'text' => 'Text'] as $value => $label)
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        wire:model="contactpreferred"
                                        value="{{ $value }}"
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- How did you hear about us --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            How did you hear about us?
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach(['Google' => 'Google', 'Social Media' => 'Social Media', 'Referral' => 'Referral', 'Houzz' => 'Houzz', 'Home Show' => 'Home Show', 'Previous Client' => 'Previous Client'] as $value => $label)
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        wire:model="source"
                                        value="{{ $value }}"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Consent --}}
                    <div class="md:col-span-2 space-y-3">
                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                wire:model="processing_consent"
                                class="w-4 h-4 mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                required
                            >
                            <span class="ml-2 text-sm text-gray-700">
                                I consent to TCS Woodwork processing my data to respond to my inquiry. <span class="text-red-500">*</span>
                            </span>
                        </label>
                        @error('processing_consent') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                wire:model="communication_consent"
                                class="w-4 h-4 mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">
                                I'd like to receive occasional updates about projects, woodworking tips, and TCS news.
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Step 2: Project Information --}}
            <div x-show="$wire.currentStep === 2" x-cloak>
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Project Information</h2>

                <div class="space-y-6">
                    {{-- Project Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            What type of project are you considering?
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach(['Kitchen Cabinetry', 'Bathroom Vanity', 'Built-ins', 'Closet System', 'Home Office', 'Furniture', 'Commercial', 'Other'] as $type)
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        wire:model="project_type"
                                        value="{{ $type }}"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $type }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Project Description --}}
                    <div>
                        <label for="project_description" class="block text-sm font-medium text-gray-700 mb-1">
                            Tell us about your project
                        </label>
                        <textarea
                            id="project_description"
                            wire:model="project_description"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Describe your vision, dimensions, special requirements..."
                        ></textarea>
                    </div>

                    {{-- Budget Range --}}
                    <div>
                        <label for="budget_range" class="block text-sm font-medium text-gray-700 mb-1">
                            Budget Range
                        </label>
                        <select
                            id="budget_range"
                            wire:model="budget_range"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Select a range...</option>
                            <option value="under_10k">Under $10,000</option>
                            <option value="10k_25k">$10,000 - $25,000</option>
                            <option value="25k_50k">$25,000 - $50,000</option>
                            <option value="50k_100k">$50,000 - $100,000</option>
                            <option value="over_100k">Over $100,000</option>
                            <option value="unsure">Not sure yet</option>
                        </select>
                    </div>

                    {{-- Design Style --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Design Style Preference
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach(['Traditional', 'Modern', 'Transitional', 'Rustic', 'Contemporary', 'Craftsman', 'Mid-Century', 'Other'] as $style)
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        wire:model="design_style"
                                        value="{{ $style }}"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $style }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Wood Species --}}
                    <div>
                        <label for="wood_species" class="block text-sm font-medium text-gray-700 mb-1">
                            Wood Species Preference
                        </label>
                        <select
                            id="wood_species"
                            wire:model="wood_species"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Select a species...</option>
                            <option value="walnut">Walnut</option>
                            <option value="oak">Oak</option>
                            <option value="cherry">Cherry</option>
                            <option value="maple">Maple</option>
                            <option value="white_oak">White Oak</option>
                            <option value="ash">Ash</option>
                            <option value="hickory">Hickory</option>
                            <option value="painted">Painted/MDF</option>
                            <option value="other">Other</option>
                            <option value="unsure">Not sure yet</option>
                        </select>
                    </div>

                    {{-- Project Address (collapsible) --}}
                    <div x-data="{ showAddress: false }">
                        <button
                            type="button"
                            @click="showAddress = !showAddress"
                            class="flex items-center text-primary-600 hover:text-primary-700"
                        >
                            <svg class="w-5 h-5 mr-1 transition-transform" :class="{ 'rotate-90': showAddress }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Add Project Address
                        </button>

                        <div x-show="showAddress" x-collapse class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <input
                                        type="text"
                                        wire:model="project_address_street1"
                                        placeholder="Street Address"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                                <div>
                                    <input
                                        type="text"
                                        wire:model="project_address_city"
                                        placeholder="City"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                                <div class="flex gap-4">
                                    <input
                                        type="text"
                                        wire:model="project_address_state"
                                        placeholder="State"
                                        class="w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                    <input
                                        type="text"
                                        wire:model="project_address_zip"
                                        placeholder="ZIP"
                                        class="w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3: Review & Submit --}}
            <div x-show="$wire.currentStep === 3" x-cloak>
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Review Your Information</h2>

                <div class="space-y-6">
                    {{-- Contact Summary --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 mb-3">Contact Information</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-500">Name:</span> {{ $firstname }} {{ $lastname }}</div>
                            <div><span class="text-gray-500">Email:</span> {{ $email }}</div>
                            <div><span class="text-gray-500">Phone:</span> {{ $phone }}</div>
                            @if($company)
                                <div><span class="text-gray-500">Company:</span> {{ $company }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Project Summary --}}
                    @if(!empty($project_type) || $project_description || $budget_range)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-medium text-gray-900 mb-3">Project Details</h3>
                            <div class="space-y-2 text-sm">
                                @if(!empty($project_type))
                                    <div><span class="text-gray-500">Type:</span> {{ implode(', ', $project_type) }}</div>
                                @endif
                                @if($budget_range)
                                    <div><span class="text-gray-500">Budget:</span>
                                        @switch($budget_range)
                                            @case('under_10k') Under $10,000 @break
                                            @case('10k_25k') $10,000 - $25,000 @break
                                            @case('25k_50k') $25,000 - $50,000 @break
                                            @case('50k_100k') $50,000 - $100,000 @break
                                            @case('over_100k') Over $100,000 @break
                                            @default {{ $budget_range }}
                                        @endswitch
                                    </div>
                                @endif
                                @if(!empty($design_style))
                                    <div><span class="text-gray-500">Style:</span> {{ implode(', ', $design_style) }}</div>
                                @endif
                                @if($wood_species)
                                    <div><span class="text-gray-500">Wood:</span> {{ ucfirst(str_replace('_', ' ', $wood_species)) }}</div>
                                @endif
                                @if($project_description)
                                    <div class="mt-2">
                                        <span class="text-gray-500">Description:</span>
                                        <p class="mt-1 text-gray-700">{{ $project_description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Additional Information --}}
                    <div>
                        <label for="additional_information" class="block text-sm font-medium text-gray-700 mb-1">
                            Anything else you'd like us to know?
                        </label>
                        <textarea
                            id="additional_information"
                            wire:model="additional_information"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Questions, timeline constraints, special requirements..."
                        ></textarea>
                    </div>

                    {{-- Turnstile CAPTCHA --}}
                    @if(config('services.turnstile.site_key'))
                        <div class="flex justify-center" x-data x-init="
                            if (typeof turnstile !== 'undefined') {
                                turnstile.render('#turnstile-widget', {
                                    sitekey: '{{ config('services.turnstile.site_key') }}',
                                    callback: function(token) {
                                        $wire.set('turnstileToken', token);
                                    }
                                });
                            }
                        ">
                            <div id="turnstile-widget"></div>
                        </div>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    @endif
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                <button
                    type="button"
                    wire:click="prevStep"
                    class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors {{ $currentStep === 1 ? 'invisible' : '' }}"
                >
                    Back
                </button>

                @if($currentStep < $totalSteps)
                    <button
                        type="button"
                        wire:click="nextStep"
                        class="px-6 py-2 text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        Continue
                    </button>
                @else
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="px-8 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                    >
                        <span wire:loading.remove>Submit Inquiry</span>
                        <span wire:loading>Sending...</span>
                    </button>
                @endif
            </div>
        </form>
    @endif
</div>
