<x-filament-panels::page>
    {{-- Control Bar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div class="flex flex-1 flex-wrap items-end gap-4">
            {{-- Template Select --}}
            <div class="min-w-[200px] flex-1 max-w-xs">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Document Template
                </label>
                <select
                    wire:model.live="selectedTemplateId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select template...</option>
                    @foreach($this->getTemplateOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Project Select --}}
            <div class="min-w-[200px] flex-1 max-w-xs">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Project (Optional)
                </label>
                <select
                    wire:model.live="selectedProjectId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select project to auto-fill...</option>
                    @foreach($this->getProjectOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- AI Toggle --}}
            <x-filament::button
                wire:click="toggleAiPanel"
                :color="$showAiPanel ? 'primary' : 'gray'"
                icon="heroicon-o-sparkles"
                size="sm"
            >
                AI Fill
            </x-filament::button>

            {{-- Actions --}}
            <x-filament::button
                wire:click="clearFields"
                color="gray"
                icon="heroicon-o-arrow-path"
                size="sm"
            >
                Reset
            </x-filament::button>

            <x-filament::button
                wire:click="printDocument"
                color="gray"
                icon="heroicon-o-printer"
                size="sm"
            >
                Print
            </x-filament::button>

            <x-filament::button
                wire:click="exportHtml"
                color="success"
                icon="heroicon-o-arrow-down-tray"
                size="sm"
            >
                Export
            </x-filament::button>
        </div>
    </div>

    {{-- AI Panel (Collapsible) --}}
    @if($showAiPanel)
        <div class="mb-4 rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-700 dark:bg-purple-900/20">
            <div class="flex items-start gap-4">
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium text-purple-700 dark:text-purple-300">
                        AI Instructions
                    </label>
                    <textarea
                        wire:model="aiPrompt"
                        rows="2"
                        class="w-full rounded-lg border-purple-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-purple-600 dark:bg-gray-800"
                        placeholder="e.g., 'Fill courier as ABC Shipping, driver John Smith, pickup tomorrow 9am'"
                    ></textarea>
                </div>
                <x-filament::button
                    wire:click="runAiFill"
                    color="primary"
                    icon="heroicon-o-sparkles"
                    :disabled="$isProcessing"
                >
                    @if($isProcessing)
                        <x-filament::loading-indicator class="h-4 w-4" />
                    @else
                        Fill
                    @endif
                </x-filament::button>
            </div>
            <p class="mt-2 text-xs text-purple-600 dark:text-purple-400">
                Describe what to fill and the AI will extract and populate the relevant fields.
            </p>
        </div>
    @endif

    {{-- Main Content Area --}}
    <div class="grid gap-6 lg:grid-cols-3" x-data="{
        zoom: 0.75,
        zoomIn() { this.zoom = Math.min(this.zoom + 0.1, 1.5); },
        zoomOut() { this.zoom = Math.max(this.zoom - 0.1, 0.3); },
        resetZoom() { this.zoom = 0.75; }
    }">
        {{-- Left: Editable Fields Panel --}}
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800 lg:col-span-1">
            <h3 class="mb-4 border-b pb-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Editable Fields
            </h3>

            @if(empty($editableFields))
                <p class="text-sm text-gray-400 italic">
                    Select a template to see available fields
                </p>
            @else
                <div class="max-h-[600px] space-y-3 overflow-y-auto pr-2">
                    @foreach($editableFields as $fieldName => $value)
                        <div class="group">
                            <label
                                for="field-{{ $fieldName }}"
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ str_replace('_', ' ', $fieldName) }}
                            </label>
                            @if(strlen($value ?? '') > 100 || str_contains(strtolower($fieldName), 'notes') || str_contains(strtolower($fieldName), 'instructions'))
                                <textarea
                                    id="field-{{ $fieldName }}"
                                    wire:model.live.debounce.500ms="editableFields.{{ $fieldName }}"
                                    rows="3"
                                    class="w-full rounded border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                ></textarea>
                            @else
                                <input
                                    type="text"
                                    id="field-{{ $fieldName }}"
                                    wire:model.live.debounce.500ms="editableFields.{{ $fieldName }}"
                                    class="w-full rounded border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right: Document Preview --}}
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 lg:col-span-2">
            <div class="flex items-center justify-between border-b p-3 dark:border-gray-700">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Document Preview
                </h3>
                <div class="flex items-center gap-2">
                    <button
                        @click="zoomOut"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-o-minus class="h-4 w-4" />
                    </button>
                    <span class="text-xs text-gray-500" x-text="Math.round(zoom * 100) + '%'"></span>
                    <button
                        @click="zoomIn"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-o-plus class="h-4 w-4" />
                    </button>
                    <button
                        @click="resetZoom"
                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />
                    </button>
                </div>
            </div>

            {{-- Preview Container --}}
            <div
                class="max-h-[700px] overflow-auto bg-gray-100 p-4 dark:bg-gray-900"
                id="preview-container"
            >
                @if(empty($renderedContent))
                    <div class="flex h-64 items-center justify-center text-gray-400">
                        <div class="text-center">
                            <x-heroicon-o-document-text class="mx-auto h-12 w-12 mb-2" />
                            <p>Select a template to preview</p>
                        </div>
                    </div>
                @else
                    <div
                        class="mx-auto bg-white shadow-lg"
                        :style="'transform: scale(' + zoom + '); transform-origin: top center;'"
                        style="width: 8.5in; min-height: 11in;"
                        id="document-content"
                    >
                        {!! $renderedContent !!}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Print Frame (Hidden) --}}
    <iframe id="print-frame" class="hidden"></iframe>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for print command
            if (typeof Livewire !== 'undefined') {
                Livewire.on('print-document', () => {
                    const content = document.getElementById('document-content');
                    if (content) {
                        const printFrame = document.getElementById('print-frame');
                        const doc = printFrame.contentWindow.document;
                        doc.open();
                        doc.write('<html><head><title>Print</title><style>@media print { @page { size: letter; margin: 0; } body { margin: 0; } }</style></head><body>' + content.innerHTML + '</body></html>');
                        doc.close();
                        printFrame.contentWindow.focus();
                        printFrame.contentWindow.print();
                    }
                });
            }
        });
    </script>
</x-filament-panels::page>
