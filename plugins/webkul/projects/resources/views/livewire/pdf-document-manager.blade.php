<div class="space-y-4">
    {{-- Header with Upload Button --}}
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('PDF Documents') }}
        </h3>
        <x-filament::button
            wire:click="openUploadModal"
            icon="heroicon-m-arrow-up-tray"
            size="sm"
        >
            {{ __('Upload Document') }}
        </x-filament::button>
    </div>

    {{-- Document Type Filter Tabs --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
        <button
            wire:click="filterByType(null)"
            type="button"
            @class([
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                'bg-primary-600 text-white' => $filterDocumentType === null,
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filterDocumentType !== null,
            ])
        >
            {{ __('All') }}
            <span class="ml-1 text-xs opacity-75">({{ $this->documents->count() }})</span>
        </button>

        @foreach($documentTypes as $typeKey => $typeLabel)
            @if(isset($this->documentTypeCounts[$typeKey]))
                <button
                    wire:click="filterByType('{{ $typeKey }}')"
                    type="button"
                    @class([
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        'bg-primary-600 text-white' => $filterDocumentType === $typeKey,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filterDocumentType !== $typeKey,
                    ])
                >
                    {{ $typeLabel }}
                    <span class="ml-1 text-xs opacity-75">({{ $this->documentTypeCounts[$typeKey] }})</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- Documents List --}}
    <div class="space-y-3">
        @forelse($this->documents as $document)
            <div
                wire:key="document-{{ $document->id }}"
                @class([
                    'relative flex items-start gap-4 p-4 rounded-lg border transition-colors',
                    'border-primary-300 bg-primary-50 dark:border-primary-700 dark:bg-primary-900/20' => $document->is_primary_reference,
                    'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-750' => !$document->is_primary_reference,
                ])
            >
                {{-- PDF Icon --}}
                <div class="flex-shrink-0">
                    <div class="w-12 h-14 bg-red-100 dark:bg-red-900/30 rounded flex items-center justify-center">
                        <x-filament::icon
                            icon="heroicon-o-document"
                            class="w-8 h-8 text-red-600 dark:text-red-400"
                        />
                    </div>
                </div>

                {{-- Document Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $document->file_name }}
                            </h4>
                            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $document->formatted_file_size }}</span>
                                <span>&bull;</span>
                                <span>{{ $document->page_count }} {{ Str::plural('page', $document->page_count) }}</span>
                                @if($document->document_type)
                                    <span>&bull;</span>
                                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                        {{ $documentTypes[$document->document_type] ?? $document->document_type }}
                                    </span>
                                @endif
                                @if($document->is_primary_reference)
                                    <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded text-xs font-medium">
                                        {{ __('Primary Reference') }}
                                    </span>
                                @endif
                            </div>
                            @if($document->notes)
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ $document->notes }}
                                </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1">
                            <x-filament::icon-button
                                icon="heroicon-m-eye"
                                color="gray"
                                size="sm"
                                :href="Storage::disk('public')->url($document->file_path)"
                                target="_blank"
                                :tooltip="__('View')"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-arrow-down-tray"
                                color="gray"
                                size="sm"
                                wire:click="downloadDocument({{ $document->id }})"
                                :tooltip="__('Download')"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-pencil-square"
                                color="gray"
                                size="sm"
                                wire:click="openEditor({{ $document->id }})"
                                :tooltip="__('Edit')"
                            />
                            @unless($document->is_primary_reference)
                                <x-filament::icon-button
                                    icon="heroicon-m-star"
                                    color="gray"
                                    size="sm"
                                    wire:click="setPrimaryReference({{ $document->id }})"
                                    :tooltip="__('Set as Primary')"
                                />
                            @endunless
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="sm"
                                wire:click="confirmDelete({{ $document->id }})"
                                :tooltip="__('Delete')"
                            />
                        </div>
                    </div>

                    {{-- Version Info --}}
                    @if($document->version_number > 1)
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Version :version', ['version' => $document->version_number]) }}
                            &bull;
                            {{ __('Uploaded :date', ['date' => $document->created_at->diffForHumans()]) }}
                        </div>
                    @else
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Uploaded :date', ['date' => $document->created_at->diffForHumans()]) }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                <x-filament::icon
                    icon="heroicon-o-document"
                    class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4"
                />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('No PDF documents uploaded yet.') }}
                </p>
                <x-filament::button
                    wire:click="openUploadModal"
                    class="mt-4"
                    size="sm"
                >
                    {{ __('Upload First Document') }}
                </x-filament::button>
            </div>
        @endforelse
    </div>

    {{-- Upload Modal --}}
    <x-filament::modal
        id="upload-document-modal"
        :open="$showUploadModal"
        width="md"
    >
        <x-slot name="heading">
            {{ __('Upload PDF Document') }}
        </x-slot>

        <div class="space-y-4">
            {{-- File Upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('PDF File') }} <span class="text-red-500">*</span>
                </label>
                <div
                    x-data="{ dragover: false }"
                    x-on:dragover.prevent="dragover = true"
                    x-on:dragleave.prevent="dragover = false"
                    x-on:drop.prevent="dragover = false"
                    :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/20': dragover }"
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center transition-colors"
                >
                    <input
                        type="file"
                        wire:model="uploadFile"
                        accept=".pdf"
                        class="hidden"
                        id="pdf-upload-{{ $this->getId() }}"
                    />
                    <label for="pdf-upload-{{ $this->getId() }}" class="cursor-pointer">
                        @if($uploadFile)
                            <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-8 h-8" />
                                <span class="text-sm">{{ $uploadFile->getClientOriginalName() }}</span>
                            </div>
                        @else
                            <x-filament::icon
                                icon="heroicon-o-document-arrow-up"
                                class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3"
                            />
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Click to select or drag and drop') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                {{ __('PDF files up to 50MB') }}
                            </p>
                        @endif
                    </label>
                </div>
                @error('uploadFile')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Document Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Document Type') }}
                </label>
                <select
                    wire:model="uploadDocumentType"
                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                >
                    <option value="">{{ __('Select type...') }}</option>
                    @foreach($documentTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Notes') }}
                </label>
                <textarea
                    wire:model="uploadNotes"
                    rows="3"
                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                    placeholder="{{ __('Add any notes about this document...') }}"
                ></textarea>
            </div>
        </div>

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="closeUploadModal"
                color="gray"
            >
                {{ __('Cancel') }}
            </x-filament::button>
            <x-filament::button
                wire:click="uploadDocument"
                :disabled="!$uploadFile"
            >
                {{ __('Upload') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Edit Slide-Over Modal --}}
    <x-filament::modal
        id="edit-document-modal"
        :open="$showEditModal"
        slide-over
        width="md"
    >
        <x-slot name="heading">
            {{ __('Edit Document Details') }}
        </x-slot>

        @if($this->editingDocument)
            <div class="space-y-4">
                {{-- Document Preview Info --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="w-10 h-12 bg-red-100 dark:bg-red-900/30 rounded flex items-center justify-center flex-shrink-0">
                        <x-filament::icon
                            icon="heroicon-o-document"
                            class="w-6 h-6 text-red-600 dark:text-red-400"
                        />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ $this->editingDocument->file_name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->editingDocument->formatted_file_size }}
                            &bull;
                            {{ $this->editingDocument->page_count }} {{ Str::plural('page', $this->editingDocument->page_count) }}
                        </p>
                    </div>
                </div>

                {{-- Document Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Document Type') }}
                    </label>
                    <select
                        wire:model="editDocumentType"
                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                    >
                        <option value="">{{ __('Select type...') }}</option>
                        @foreach($documentTypes as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Notes') }}
                    </label>
                    <textarea
                        wire:model="editNotes"
                        rows="4"
                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                        placeholder="{{ __('Add any notes about this document...') }}"
                    ></textarea>
                </div>

                {{-- Primary Reference Toggle --}}
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="editIsPrimaryReference"
                        id="edit-is-primary-{{ $this->getId() }}"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                    />
                    <label for="edit-is-primary-{{ $this->getId() }}" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Set as Primary Reference Document') }}
                    </label>
                </div>

                {{-- Metadata --}}
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-2 border-t dark:border-gray-700">
                    <p>{{ __('Uploaded') }}: {{ $this->editingDocument->created_at->format('M d, Y g:i A') }}</p>
                    @if($this->editingDocument->uploader)
                        <p>{{ __('By') }}: {{ $this->editingDocument->uploader->name }}</p>
                    @endif
                    @if($this->editingDocument->version_number > 1)
                        <p>{{ __('Version') }}: {{ $this->editingDocument->version_number }}</p>
                    @endif
                </div>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="closeEditor"
                color="gray"
            >
                {{ __('Cancel') }}
            </x-filament::button>
            <x-filament::button
                wire:click="saveDocumentMetadata"
            >
                {{ __('Save Changes') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Delete Confirmation Modal --}}
    <x-filament::modal
        id="delete-document-modal"
        :open="$showDeleteModal"
        width="md"
    >
        <x-slot name="heading">
            {{ __('Delete Document') }}
        </x-slot>

        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to delete this document?') }}
            </p>

            @php
                $counts = $this->deletingDocumentEntityCounts;
            @endphp

            @if($counts['annotations'] > 0)
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5"
                        />
                        <div class="text-sm">
                            <p class="font-medium text-amber-800 dark:text-amber-200">
                                {{ __('This document has linked entities:') }}
                            </p>
                            <ul class="mt-1 text-amber-700 dark:text-amber-300 space-y-0.5">
                                <li>{{ $counts['annotations'] }} {{ Str::plural('annotation', $counts['annotations']) }}</li>
                                @if($counts['roomRefs'] > 0)
                                    <li>{{ $counts['roomRefs'] }} room {{ Str::plural('reference', $counts['roomRefs']) }}</li>
                                @endif
                                @if($counts['cabinetRefs'] > 0)
                                    <li>{{ $counts['cabinetRefs'] }} cabinet {{ Str::plural('reference', $counts['cabinetRefs']) }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <input
                            type="radio"
                            wire:model="clearEntitiesOnDelete"
                            value="0"
                            name="delete-option"
                            class="mt-0.5 border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Keep entity references') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Annotations will be soft deleted but room/cabinet references remain intact. Document can be restored later.') }}
                            </p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 border border-red-200 dark:border-red-800 rounded-lg cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <input
                            type="radio"
                            wire:model="clearEntitiesOnDelete"
                            value="1"
                            name="delete-option"
                            class="mt-0.5 border-gray-300 dark:border-gray-600 text-danger-600 focus:ring-danger-500"
                        />
                        <div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                                {{ __('Clear all entity references') }}
                            </p>
                            <p class="text-xs text-red-600 dark:text-red-500">
                                {{ __('Permanently removes all annotations and their room/cabinet links. This cannot be undone.') }}
                            </p>
                        </div>
                    </label>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('This document has no linked annotations or entities.') }}
                </p>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="closeDeleteModal"
                color="gray"
            >
                {{ __('Cancel') }}
            </x-filament::button>
            <x-filament::button
                wire:click="deleteDocument"
                color="danger"
            >
                {{ __('Delete Document') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
