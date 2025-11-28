<div
    x-data="{
        previewOpen: false,
        templateId: @entangle('data.document_template_id')
    }"
    @toggle-preview.window="previewOpen = !previewOpen"
    class="flex gap-6"
>
    {{-- Main Form Area --}}
    <div
        class="flex-1 transition-all duration-300"
        :class="{ 'max-w-full': !previewOpen, 'max-w-[60%]': previewOpen }"
    >
        <form wire:submit="create">
            {{ $this->form }}
        </form>
    </div>

    {{-- Side Preview Panel --}}
    <div
        x-show="previewOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-8"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-8"
        class="w-[40%] sticky top-6 h-fit"
    >
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
            {{-- Preview Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Template Preview
                </h3>
                <button
                    type="button"
                    @click="previewOpen = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Preview Content --}}
            <div class="p-4 overflow-auto" style="max-height: calc(100vh - 200px);">
                <div x-show="!templateId || templateId === ''" class="text-center py-12 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <p>Select a proposal template to preview</p>
                </div>

                <div x-show="templateId && templateId !== ''" x-cloak>
                    <livewire:quotation-preview-panel :documentTemplateId="null" key="preview-panel" />
                </div>
            </div>
        </div>
    </div>
</div>
