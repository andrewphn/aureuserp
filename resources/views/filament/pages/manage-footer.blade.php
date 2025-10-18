<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Customize Your Footer</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Choose which fields appear in your sticky footer for each context. The footer adapts automatically when you're working with projects, sales orders, inventory, or production jobs.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-sm text-blue-700 dark:text-blue-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><strong>Minimized:</strong> 2-3 fields for quick glance</span>
                            </div>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-sm text-purple-700 dark:text-purple-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                                </svg>
                                <span><strong>Expanded:</strong> 5-10 fields for detailed view</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Persona Templates Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Apply: Persona Templates</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Apply pre-configured templates based on your role. Each template is optimized for specific workflows and user needs.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse($templates as $template)
                        <button
                            wire:click="applyTemplate('{{ $template['slug'] }}')"
                            class="flex flex-col items-start gap-3 p-4 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-{{ $template['color'] }}-100 dark:bg-{{ $template['color'] }}-900/20 flex items-center justify-center">
                                    <x-filament::icon
                                        :icon="$template['icon']"
                                        class="w-5 h-5 text-{{ $template['color'] }}-600 dark:text-{{ $template['color'] }}-400"
                                    />
                                </div>
                                <div class="text-left">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $template['name'] }}</div>
                                </div>
                            </div>
                            @if($template['description'])
                                <p class="text-xs text-left text-gray-600 dark:text-gray-400">{{ $template['description'] }}</p>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No templates available.</p>
                            <p class="text-sm mt-2">
                                <a href="{{ route('filament.admin.resources.footer-templates.create') }}" class="text-primary-600 hover:underline">
                                    Create your first template
                                </a>
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Form Section --}}
        <form wire:submit="savePreferences">
            {{ $this->form }}

            <div class="flex items-center justify-between mt-6">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Changes are saved per user and persist across sessions
                </div>
                <div class="flex items-center gap-3">
                    <x-filament::button
                        type="button"
                        color="danger"
                        outlined
                        wire:click="resetToDefaults"
                        wire:confirm="Are you sure you want to reset all preferences to defaults?"
                    >
                        Reset to Defaults
                    </x-filament::button>

                    <x-filament::button type="submit" color="primary">
                        Save Preferences
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
