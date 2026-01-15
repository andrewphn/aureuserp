<div class="cabinet-configurator">
    @if(!$cabinet)
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <x-heroicon-o-cube class="mx-auto mb-4 w-12 h-12 opacity-50" />
            <p>Select a cabinet to configure</p>
        </div>
    @else
        {{-- Header with cabinet info --}}
        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $cabinet->cabinet_number ?? 'Cabinet' }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $cabinetWidth }}"W x {{ $cabinetHeight }}"H x {{ $cabinetDepth }}"D
                    &bull; {{ ucfirst(str_replace('_', ' ', $constructionType)) }}
                </p>
            </div>
            <div class="flex gap-2 items-center">
                {{-- Validation status --}}
                @if($isValid && empty($validationWarnings))
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-800 dark:text-green-100">
                        <x-heroicon-s-check-circle class="mr-1 w-4 h-4" />
                        Valid
                    </span>
                @elseif($isValid && !empty($validationWarnings))
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full dark:bg-yellow-800 dark:text-yellow-100">
                        <x-heroicon-s-exclamation-triangle class="mr-1 w-4 h-4" />
                        {{ count($validationWarnings) }} Warning(s)
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-red-800 bg-red-100 rounded-full dark:bg-red-800 dark:text-red-100">
                        <x-heroicon-s-x-circle class="mr-1 w-4 h-4" />
                        {{ count($validationErrors) }} Error(s)
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-3">
            {{-- Visual Cabinet Representation --}}
            <div class="lg:col-span-2">
                <div class="p-4 bg-gray-100 rounded-lg dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Cabinet Front View</h4>
                        <button
                            wire:click="openTemplateModal"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg dark:bg-blue-900 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-800"
                        >
                            <x-heroicon-m-squares-2x2 class="mr-1 w-4 h-4" />
                            Apply Template
                        </button>
                    </div>

                    {{-- Cabinet Visual Box --}}
                    <div class="relative mx-auto bg-white rounded border-4 border-amber-700 dark:bg-gray-900" style="height: 400px; max-width: 500px;">
                        {{-- Cabinet dimension labels --}}
                        <div class="absolute right-0 left-0 -top-6 text-xs text-center text-gray-500">
                            {{ $cabinetWidth }}"
                        </div>
                        <div class="flex absolute top-0 bottom-0 -right-8 items-center">
                            <span class="text-xs text-gray-500 whitespace-nowrap transform -rotate-90">
                                {{ $cabinetHeight }}"
                            </span>
                        </div>

                        {{-- Face Frame Rails (if face frame construction) --}}
                        @if($constructionType === 'face_frame')
                            {{-- Top Rail --}}
                            <div class="absolute top-0 right-0 left-0 bg-amber-600/30 border-b border-amber-700" style="height: {{ ($railWidth / $cabinetHeight) * 100 }}%;">
                                <span class="absolute inset-0 flex justify-center items-center text-xs text-amber-800 opacity-75">Top Rail</span>
                            </div>
                            {{-- Bottom Rail --}}
                            <div class="absolute right-0 bottom-0 left-0 bg-amber-600/30 border-t border-amber-700" style="height: {{ ($railWidth / $cabinetHeight) * 100 }}%;">
                                <span class="absolute inset-0 flex justify-center items-center text-xs text-amber-800 opacity-75">Bottom Rail</span>
                            </div>
                            {{-- Left Stile --}}
                            <div class="absolute top-0 bottom-0 left-0 bg-amber-600/30 border-r border-amber-700" style="width: {{ ($stileWidth / $cabinetWidth) * 100 }}%;"></div>
                            {{-- Right Stile --}}
                            <div class="absolute top-0 right-0 bottom-0 bg-amber-600/30 border-l border-amber-700" style="width: {{ ($stileWidth / $cabinetWidth) * 100 }}%;"></div>
                        @endif

                        {{-- Sections --}}
                        @php
                            $frameLeftPercent = $constructionType === 'face_frame' ? ($stileWidth / $cabinetWidth) * 100 : 2;
                            $frameRightPercent = $constructionType === 'face_frame' ? ($stileWidth / $cabinetWidth) * 100 : 2;
                            $frameTopPercent = $constructionType === 'face_frame' ? ($railWidth / $cabinetHeight) * 100 : 2;
                            $frameBottomPercent = $constructionType === 'face_frame' ? ($railWidth / $cabinetHeight) * 100 : 2;
                            $openingWidthPercent = 100 - $frameLeftPercent - $frameRightPercent;
                            $openingHeightPercent = 100 - $frameTopPercent - $frameBottomPercent;

                            // Calculate section positions
                            $sectionLeftPercent = $frameLeftPercent;
                        @endphp

                        @forelse($sections as $index => $section)
                            @php
                                $sectionWidthPercent = $openingWidthPercent * ($section['ratio'] ?? (1 / count($sections)));
                                $midStilePercent = $constructionType === 'face_frame' && $index > 0 ? ($stileWidth / $cabinetWidth) * 100 : 0;
                            @endphp

                            {{-- Mid-stile before section (except first) --}}
                            @if($constructionType === 'face_frame' && $index > 0)
                                <div
                                    class="absolute bg-amber-600/30 border-l border-r border-amber-700"
                                    style="
                                        left: {{ $sectionLeftPercent - $midStilePercent }}%;
                                        top: {{ $frameTopPercent }}%;
                                        width: {{ $midStilePercent }}%;
                                        height: {{ $openingHeightPercent }}%;
                                    "
                                ></div>
                            @endif

                            <div
                                wire:click="openSectionConfigurator({{ $section['id'] }})"
                                class="flex absolute flex-col justify-center items-center rounded border-2 transition-all cursor-pointer hover:ring-2 hover:ring-blue-500 hover:shadow-lg"
                                style="
                                    left: {{ $sectionLeftPercent }}%;
                                    top: {{ $frameTopPercent }}%;
                                    width: {{ $sectionWidthPercent }}%;
                                    height: {{ $openingHeightPercent }}%;
                                    background-color: {{ $this->getSectionColor($section['type']) }}15;
                                    border-color: {{ $this->getSectionColor($section['type']) }};
                                "
                                title="{{ $section['name'] }}: {{ number_format($section['width'], 2) }}&quot;W x {{ number_format($section['height'], 2) }}&quot;H"
                            >
                                <x-dynamic-component :component="$this->getSectionIcon($section['type'])" class="w-8 h-8" style="color: {{ $this->getSectionColor($section['type']) }}" />
                                <span class="mt-1 text-xs font-medium" style="color: {{ $this->getSectionColor($section['type']) }}">
                                    {{ $section['name'] }}
                                </span>
                                <span class="text-xs opacity-75" style="color: {{ $this->getSectionColor($section['type']) }}">
                                    {{ ucfirst(str_replace('_', ' ', $section['type'])) }}
                                </span>
                                @if($section['component_count'] > 0)
                                    <span class="mt-1 px-2 py-0.5 text-xs bg-white/50 rounded-full">
                                        {{ $section['component_count'] }} component(s)
                                    </span>
                                @endif

                                {{-- Section controls --}}
                                <div class="flex absolute top-1 right-1 gap-1">
                                    <button
                                        wire:click.stop="moveSectionLeft({{ $section['id'] }})"
                                        class="p-1 bg-white rounded shadow hover:bg-gray-100"
                                        title="Move Left"
                                        @if($index === 0) disabled class="opacity-50 cursor-not-allowed" @endif
                                    >
                                        <x-heroicon-m-chevron-left class="w-3 h-3" />
                                    </button>
                                    <button
                                        wire:click.stop="moveSectionRight({{ $section['id'] }})"
                                        class="p-1 bg-white rounded shadow hover:bg-gray-100"
                                        title="Move Right"
                                        @if($index === count($sections) - 1) disabled class="opacity-50 cursor-not-allowed" @endif
                                    >
                                        <x-heroicon-m-chevron-right class="w-3 h-3" />
                                    </button>
                                    <button
                                        wire:click.stop="openEditSectionModal({{ $section['id'] }})"
                                        class="p-1 bg-white rounded shadow hover:bg-gray-100"
                                        title="Edit Section"
                                    >
                                        <x-heroicon-m-pencil class="w-3 h-3" />
                                    </button>
                                    <button
                                        wire:click.stop="deleteSection({{ $section['id'] }})"
                                        wire:confirm="Are you sure you want to delete this section and all its components?"
                                        class="p-1 text-red-600 bg-white rounded shadow hover:bg-red-100"
                                        title="Delete Section"
                                    >
                                        <x-heroicon-m-trash class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            @php
                                $sectionLeftPercent += $sectionWidthPercent + $midStilePercent;
                            @endphp
                        @empty
                            <div class="flex absolute inset-0 justify-center items-center">
                                <div class="text-center text-gray-400">
                                    <x-heroicon-o-plus-circle class="mx-auto mb-2 w-12 h-12" />
                                    <p class="text-sm">No sections defined</p>
                                    <button
                                        wire:click="openAddSectionModal"
                                        class="mt-2 text-blue-500 hover:text-blue-700"
                                    >
                                        Add your first section
                                    </button>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Add Section Button --}}
                    <div class="mt-4 text-center">
                        <button
                            wire:click="openAddSectionModal"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <x-heroicon-m-plus class="mr-2 w-4 h-4" />
                            Add Section
                        </button>
                    </div>
                </div>

                {{-- Frame Dimensions Summary --}}
                @if($constructionType === 'face_frame' && !empty($frameDimensions))
                    <div class="grid grid-cols-4 gap-3 p-4 mt-4 bg-amber-50 rounded-lg dark:bg-amber-900/20">
                        <div class="text-center">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Stile Width</span>
                            <span class="text-sm font-medium">{{ $frameDimensions['stile_width'] ?? $stileWidth }}"</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Rail Width</span>
                            <span class="text-sm font-medium">{{ $frameDimensions['rail_width'] ?? $railWidth }}"</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Total Opening W</span>
                            <span class="text-sm font-medium">{{ number_format($frameDimensions['total_opening_width'] ?? 0, 2) }}"</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Total Opening H</span>
                            <span class="text-sm font-medium">{{ number_format($frameDimensions['total_opening_height'] ?? 0, 2) }}"</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Controls Panel --}}
            <div class="space-y-4">
                {{-- Construction Type --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Construction Type</h4>
                    <select
                        wire:model="constructionType"
                        wire:change="updateConstructionType"
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                    >
                        <option value="face_frame">Face Frame</option>
                        <option value="frameless">Frameless (Euro)</option>
                    </select>
                </div>

                {{-- Face Frame Settings (only for face frame construction) --}}
                @if($constructionType === 'face_frame')
                    <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Face Frame Settings</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Stile Width (inches)</label>
                                <input
                                    type="number"
                                    wire:model.defer="stileWidth"
                                    step="0.125"
                                    min="1"
                                    max="3"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Rail Width (inches)</label>
                                <input
                                    type="number"
                                    wire:model.defer="railWidth"
                                    step="0.125"
                                    min="1"
                                    max="3"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <button
                                wire:click="updateFaceFrame"
                                class="px-4 py-2 w-full text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                            >
                                Update Face Frame
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Stretcher Generation --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Stretchers</h4>
                    @if($needsStretchers)
                        <div class="mb-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Required:</span>
                                <span class="font-medium">{{ $requiredStretcherCount }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Existing:</span>
                                <span class="font-medium {{ $existingStretcherCount >= $requiredStretcherCount ? 'text-green-600' : 'text-yellow-600' }}">
                                    {{ $existingStretcherCount }}
                                </span>
                            </div>
                        </div>
                        <button
                            wire:click="generateStretchers"
                            wire:confirm="This will regenerate all stretchers. Continue?"
                            class="inline-flex justify-center items-center px-4 py-2 w-full text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        >
                            <x-heroicon-m-bars-3 class="mr-2 w-4 h-4" />
                            Generate Stretchers
                        </button>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            This cabinet type does not require stretchers.
                        </p>
                    @endif
                </div>

                {{-- Sections List --}}
                <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Sections ({{ count($sections) }})
                    </h4>
                    <div class="space-y-2">
                        @forelse($sections as $section)
                            <div
                                wire:click="openSectionConfigurator({{ $section['id'] }})"
                                class="flex justify-between items-center p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                style="border-left: 3px solid {{ $this->getSectionColor($section['type']) }}"
                            >
                                <div class="flex gap-2 items-center">
                                    <x-dynamic-component :component="$this->getSectionIcon($section['type'])" class="w-4 h-4" style="color: {{ $this->getSectionColor($section['type']) }}" />
                                    <span class="text-sm">{{ $section['name'] }}</span>
                                </div>
                                <span class="text-xs text-gray-500">
                                    {{ number_format($section['width'], 1) }}"
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-center text-gray-500">No sections</p>
                        @endforelse
                    </div>
                </div>

                {{-- Validation Messages --}}
                @if(!empty($validationErrors) || !empty($validationWarnings))
                    <div class="p-4 bg-white rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Validation</h4>
                        @foreach($validationErrors as $error)
                            <div class="flex gap-2 items-start mb-2 text-sm text-red-600 dark:text-red-400">
                                <x-heroicon-s-x-circle class="mt-0.5 w-4 h-4 shrink-0" />
                                <span>{{ $error }}</span>
                            </div>
                        @endforeach
                        @foreach($validationWarnings as $warning)
                            <div class="flex gap-2 items-start mb-2 text-sm text-yellow-600 dark:text-yellow-400">
                                <x-heroicon-s-exclamation-triangle class="mt-0.5 w-4 h-4 shrink-0" />
                                <span>{{ $warning }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Template Modal --}}
        @if($showTemplateModal)
            <div class="flex fixed inset-0 z-50 justify-center items-center bg-black/50">
                <div class="p-6 mx-4 w-full max-w-lg bg-white rounded-xl shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Apply Template
                    </h3>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        Select a template to apply. This will replace all existing sections.
                    </p>

                    <div class="grid grid-cols-2 gap-3 mb-6">
                        @foreach($this->getTemplates() as $key => $template)
                            <button
                                wire:click="$set('selectedTemplate', '{{ $key }}')"
                                class="p-3 text-left rounded-lg border-2 transition-all {{ $selectedTemplate === $key ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}"
                            >
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $template['name'] }}
                                </span>
                                <span class="block mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $template['description'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button
                            wire:click="$set('showTemplateModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="applyTemplate"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            {{ empty($selectedTemplate) ? 'disabled' : '' }}
                        >
                            Apply Template
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Add Section Modal --}}
        @if($showAddSectionModal)
            <div class="flex fixed inset-0 z-50 justify-center items-center bg-black/50">
                <div class="p-6 mx-4 w-full max-w-md bg-white rounded-xl shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Add Section
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Section Type
                            </label>
                            <select
                                wire:model="addSectionType"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                @foreach($this->getSectionTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Width Ratio (0-1)
                            </label>
                            <input
                                type="number"
                                wire:model="addSectionRatio"
                                step="0.05"
                                min="0.1"
                                max="1"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Relative width compared to other sections (will be normalized)
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-6">
                        <button
                            wire:click="$set('showAddSectionModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="addSection"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                        >
                            Add Section
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Edit Section Modal --}}
        @if($showEditSectionModal)
            <div class="flex fixed inset-0 z-50 justify-center items-center bg-black/50">
                <div class="p-6 mx-4 w-full max-w-md bg-white rounded-xl shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Edit Section
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Section Name
                            </label>
                            <input
                                type="text"
                                wire:model="editSectionName"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Section Type
                            </label>
                            <select
                                wire:model="editSectionType"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                @foreach($this->getSectionTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Width (inches)
                                </label>
                                <input
                                    type="number"
                                    wire:model="editSectionWidth"
                                    step="0.125"
                                    min="0"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Height (inches)
                                </label>
                                <input
                                    type="number"
                                    wire:model="editSectionHeight"
                                    step="0.125"
                                    min="0"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Width Ratio
                            </label>
                            <input
                                type="number"
                                wire:model="editSectionRatio"
                                step="0.05"
                                min="0.1"
                                max="1"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            />
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-6">
                        <button
                            wire:click="$set('showEditSectionModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:text-gray-300 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="updateSection"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                        >
                            Update Section
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
