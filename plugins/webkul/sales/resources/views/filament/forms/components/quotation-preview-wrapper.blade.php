@php
    // Get the document_template_id from the form
    $templateId = null;
    if (method_exists($this, 'getState')) {
        $formData = $this->getState();
        $templateId = $formData['document_template_id'] ?? null;
    }
@endphp

<div
    x-data="{
        templateId: @js($templateId)
    }"
    x-init="
        // Watch for changes in the parent form's document_template_id field
        $watch('$wire.data.document_template_id', value => {
            templateId = value;
            // Dispatch event to update Livewire component
            $dispatch('template-changed', { templateId: value });
        });
    "
    wire:key="preview-wrapper"
>
    <div x-show="templateId">
        @livewire('quotation-preview-panel', ['documentTemplateId' => $templateId ?? 3], key('quotation-preview'))
    </div>
    <div x-show="!templateId" class="flex items-center justify-center h-32 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
        <div class="text-center">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <p class="font-medium">Select a proposal template above to preview</p>
        </div>
    </div>
</div>
