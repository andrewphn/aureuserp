<x-filament::section
    collapsible
    :collapsed="$collapsed"
    x-data="{
        summaryData: @js(collect($fields)->mapWithKeys(fn($f) => [$f['key'] => $f['default']])->all()),
        fields: @js($fields),
        parentWire: null,

        init() {
            this.$nextTick(() => {
                // Find the Livewire component - look for the form's wire:id
                const formEl = this.\$el.closest('form')?.querySelector('[wire\\:id]');
                this.parentWire = formEl?.__livewire || window.Livewire?.find(formEl?.getAttribute('wire:id'));

                if (!this.parentWire) {
                    // Try alternative: find the first Livewire component in the page
                    const anyWireEl = document.querySelector('main [wire\\:id]');
                    this.parentWire = anyWireEl?.__livewire || window.Livewire?.find(anyWireEl?.getAttribute('wire:id'));
                }

                if (this.parentWire) {
                    // Watch for Livewire updates
                    Livewire.hook('commit', ({ component }) => {
                        if (component === this.parentWire) {
                            this.updateSummary();
                        }
                    });

                    // Initial update
                    setTimeout(() => this.updateSummary(), 100);
                } else {
                    console.warn('LiveSummaryPanel: Could not find parent Livewire component');
                }
            });
        },

        updateSummary() {
            if (!this.parentWire) return;

            const formData = this.parentWire.data || {};

            this.fields.forEach(field => {
                if (field.formatter) {
                    try {
                        const formatterFn = eval(`(${field.formatter})`);
                        this.summaryData[field.key] = formatterFn.call(this, formData, field.default);
                    } catch (e) {
                        console.error(`Formatter error for ${field.key}:`, e);
                        this.summaryData[field.key] = field.default;
                    }
                } else {
                    this.summaryData[field.key] = formData[field.key] || field.default;
                }
            });
        }
    }"
>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="font-semibold">{{ $heading }}</span>
        </div>
    </x-slot>

    @if($description)
    <x-slot name="description">
        {{ $description }}
    </x-slot>
    @endif

    <div class="grid {{ $gridCols }} gap-6">
        @foreach($fields as $field)
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                @if(!empty($field['icon']))
                {!! $field['icon'] !!}
                @endif
                {{ $field['label'] }}
            </div>
            <div class="text-sm text-gray-900 dark:text-gray-100"
                 @class(['font-mono' => $field['key'] === 'project_number'])>
                @if(!empty($field['isHtml']))
                    <span x-html="summaryData['{{ $field['key'] }}']"></span>
                @else
                    <span x-text="summaryData['{{ $field['key'] }}']"></span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</x-filament::section>
