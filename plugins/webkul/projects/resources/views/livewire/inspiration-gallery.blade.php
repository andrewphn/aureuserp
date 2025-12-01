<div
    x-data="inspirationGallery()"
    x-init="initMasonry"
    class="space-y-4"
>
    {{-- Room Filter Tabs --}}
    @if($this->rooms->isNotEmpty())
        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
            <button
                wire:click="filterByRoom(null)"
                type="button"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                    'bg-primary-600 text-white' => $selectedRoomId === null,
                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $selectedRoomId !== null,
                ])
            >
                {{ __('All Rooms') }}
            </button>

            @foreach($this->rooms as $room)
                <button
                    wire:click="filterByRoom({{ $room->id }})"
                    type="button"
                    @class([
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        'bg-primary-600 text-white' => $selectedRoomId === $room->id,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $selectedRoomId !== $room->id,
                    ])
                >
                    {{ $room->name }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Upload Area --}}
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
        <input
            type="file"
            wire:model="newImages"
            multiple
            accept="image/*"
            class="hidden"
            id="inspiration-upload-{{ $this->getId() }}"
        />
        <label
            for="inspiration-upload-{{ $this->getId() }}"
            class="cursor-pointer"
        >
            <x-filament::icon
                icon="heroicon-o-photo"
                class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3"
            />
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Click to upload or drag and drop') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ __('PNG, JPG, GIF up to 10MB') }}
            </p>
        </label>
    </div>

    {{-- Pending Uploads Section --}}
    @if(count($pendingUploads) > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    {{ __(':count image(s) ready to upload', ['count' => count($pendingUploads)]) }}
                </h4>
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="discardPendingUploads"
                        color="gray"
                        size="sm"
                    >
                        {{ __('Discard') }}
                    </x-filament::button>
                    <x-filament::button
                        wire:click="savePendingUploads"
                        size="sm"
                    >
                        {{ __('Save All') }}
                    </x-filament::button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($pendingUploads as $index => $upload)
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm">
                        <img
                            src="{{ $upload['preview'] }}"
                            alt="{{ $upload['name'] }}"
                            class="w-full h-32 object-cover"
                        />
                        <div class="p-2">
                            <input
                                type="text"
                                value="{{ $upload['title'] }}"
                                wire:change="updatePendingUpload({{ $index }}, 'title', $event.target.value)"
                                placeholder="{{ __('Title') }}"
                                class="w-full text-xs border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                            />
                        </div>
                        <button
                            wire:click="removePendingUpload({{ $index }})"
                            type="button"
                            class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Card-based Gallery --}}
    <div
        x-ref="masonryGrid"
        class="masonry-grid"
        wire:ignore.self
    >
        @forelse($this->images as $image)
            <div
                class="masonry-item mb-4"
                data-id="{{ $image->id }}"
                wire:key="image-{{ $image->id }}"
            >
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
                    {{-- Image with click to expand --}}
                    <div class="relative group cursor-pointer" wire:click="openEditor({{ $image->id }})">
                        <img
                            src="{{ Storage::disk('public')->url($image->file_path) }}"
                            alt="{{ $image->title ?? $image->file_name }}"
                            class="w-full object-cover"
                            loading="lazy"
                        />

                        {{-- Hover overlay with expand hint --}}
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <div class="bg-white/90 dark:bg-gray-800/90 rounded-full p-2">
                                <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="w-6 h-6 text-gray-700 dark:text-gray-300" />
                            </div>
                        </div>

                        {{-- Room badge --}}
                        @if($image->room)
                            <div class="absolute top-2 left-2">
                                <span class="px-2 py-1 text-xs font-medium bg-primary-500/90 text-white rounded-full">
                                    {{ $image->room->name }}
                                </span>
                            </div>
                        @endif

                        {{-- Tags display --}}
                        @if(!empty($image->tags))
                            <div class="absolute top-2 right-2 flex flex-wrap gap-1 max-w-[60%] justify-end">
                                @foreach(array_slice($image->tags, 0, 2) as $tagId)
                                    @php
                                        $tag = $this->availableTags->firstWhere('id', $tagId);
                                    @endphp
                                    @if($tag)
                                        <span
                                            class="px-1.5 py-0.5 text-xs rounded"
                                            style="background-color: {{ $tag->color ?? '#6b7280' }}20; color: {{ $tag->color ?? '#6b7280' }};"
                                        >
                                            {{ $tag->name }}
                                        </span>
                                    @endif
                                @endforeach
                                @if(count($image->tags) > 2)
                                    <span class="px-1.5 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        +{{ count($image->tags) - 2 }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Card Content with Quick Edit --}}
                    <div class="p-3">
                        @if($quickEditImageId === $image->id)
                            {{-- Quick Edit Mode --}}
                            <div class="space-y-2" wire:click.stop>
                                <input
                                    type="text"
                                    wire:model="quickEditTitle"
                                    placeholder="{{ __('Title') }}"
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                                    autofocus
                                />
                                <textarea
                                    wire:model="quickEditDescription"
                                    rows="2"
                                    placeholder="{{ __('Add a comment...') }}"
                                    class="w-full text-xs border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200 resize-none"
                                ></textarea>
                                <div class="flex justify-end gap-2">
                                    <button
                                        wire:click="cancelQuickEdit"
                                        type="button"
                                        class="px-2 py-1 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                    >
                                        {{ __('Cancel') }}
                                    </button>
                                    <button
                                        wire:click="saveQuickEdit"
                                        type="button"
                                        class="px-3 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700"
                                    >
                                        {{ __('Save') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Display Mode --}}
                            <div class="min-h-[3rem]">
                                @if($image->title)
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $image->title }}
                                    </h4>
                                @else
                                    <h4 class="text-sm text-gray-400 dark:text-gray-500 italic truncate">
                                        {{ pathinfo($image->file_name, PATHINFO_FILENAME) }}
                                    </h4>
                                @endif

                                @if($image->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ $image->description }}
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400 dark:text-gray-500 italic mt-1">
                                        {{ __('No comment') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Card Actions --}}
                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <button
                                    wire:click.stop="startQuickEdit({{ $image->id }})"
                                    type="button"
                                    class="flex items-center gap-1 text-xs text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                                    title="{{ __('Quick Edit') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-pencil" class="w-3.5 h-3.5" />
                                    {{ __('Edit') }}
                                </button>

                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click.stop="openEditor({{ $image->id }})"
                                        type="button"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                                        title="{{ __('Full Edit') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-cog-6-tooth" class="w-4 h-4" />
                                    </button>
                                    <button
                                        wire:click.stop="deleteImage({{ $image->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this image?') }}"
                                        type="button"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                        title="{{ __('Delete') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-photo"
                    class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4"
                />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('No inspiration images yet. Upload some to get started!') }}
                </p>
            </div>
        @endforelse
    </div>

    {{-- Full Edit Slide-Over Modal --}}
    <x-filament::modal
        id="edit-image-modal"
        :open="$showEditModal"
        slide-over
        width="md"
    >
        <x-slot name="heading">
            {{ __('Edit Image Details') }}
        </x-slot>

        @if($this->editingImage)
            <div class="space-y-4">
                {{-- Image Preview --}}
                <div class="rounded-lg overflow-hidden">
                    <img
                        src="{{ Storage::disk('public')->url($this->editingImage->file_path) }}"
                        alt="{{ $this->editingImage->file_name }}"
                        class="w-full max-h-64 object-contain bg-gray-100 dark:bg-gray-800"
                    />
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Title') }}
                    </label>
                    <input
                        type="text"
                        wire:model="editTitle"
                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                        placeholder="{{ __('Enter a title for this image') }}"
                    />
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Description / Comments') }}
                    </label>
                    <textarea
                        wire:model="editDescription"
                        rows="3"
                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                        placeholder="{{ __('Add notes or description') }}"
                    ></textarea>
                </div>

                {{-- Room Assignment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Room') }}
                    </label>
                    <select
                        wire:model="editRoomId"
                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-200"
                    >
                        <option value="">{{ __('No room assigned') }}</option>
                        @foreach($this->rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tags Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Tags') }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->availableTags as $tag)
                            <button
                                wire:click="toggleTag({{ $tag->id }})"
                                type="button"
                                @class([
                                    'px-3 py-1.5 text-sm rounded-full border transition-colors',
                                    'ring-2 ring-offset-1' => in_array($tag->id, $editTags),
                                ])
                                style="
                                    background-color: {{ in_array($tag->id, $editTags) ? ($tag->color ?? '#6b7280') : 'transparent' }};
                                    color: {{ in_array($tag->id, $editTags) ? '#ffffff' : ($tag->color ?? '#6b7280') }};
                                    border-color: {{ $tag->color ?? '#6b7280' }};
                                    {{ in_array($tag->id, $editTags) ? 'ring-color: ' . ($tag->color ?? '#6b7280') . ';' : '' }}
                                "
                            >
                                {{ $tag->name }}
                            </button>
                        @endforeach

                        @if($this->availableTags->isEmpty())
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">
                                {{ __('No tags available. Create tags in project settings.') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- File Info --}}
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-2 border-t dark:border-gray-700">
                    <p>{{ __('File') }}: {{ $this->editingImage->file_name }}</p>
                    <p>{{ __('Size') }}: {{ $this->editingImage->formatted_file_size }}</p>
                    @if($this->editingImage->dimensions)
                        <p>{{ __('Dimensions') }}: {{ $this->editingImage->dimensions }}</p>
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
                wire:click="saveImageMetadata"
            >
                {{ __('Save Changes') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>

@push('styles')
<style>
    .masonry-grid {
        display: flex;
        flex-wrap: wrap;
        margin: -0.5rem;
    }

    .masonry-item {
        width: calc(50% - 1rem);
        margin: 0.5rem;
    }

    @media (min-width: 768px) {
        .masonry-item {
            width: calc(33.333% - 1rem);
        }
    }

    @media (min-width: 1024px) {
        .masonry-item {
            width: calc(25% - 1rem);
        }
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@push('scripts')
<script>
    function inspirationGallery() {
        return {
            masonry: null,

            initMasonry() {
                this.$nextTick(() => {
                    if (typeof Masonry !== 'undefined' && typeof imagesLoaded !== 'undefined') {
                        const grid = this.$refs.masonryGrid;
                        if (grid) {
                            imagesLoaded(grid, () => {
                                this.masonry = new Masonry(grid, {
                                    itemSelector: '.masonry-item',
                                    columnWidth: '.masonry-item',
                                    percentPosition: true,
                                    gutter: 16
                                });
                            });
                        }
                    }
                });

                // Re-layout on Livewire updates
                Livewire.hook('morph.updated', ({ component }) => {
                    if (this.masonry) {
                        this.$nextTick(() => {
                            imagesLoaded(this.$refs.masonryGrid, () => {
                                this.masonry.reloadItems();
                                this.masonry.layout();
                            });
                        });
                    }
                });
            }
        };
    }
</script>
@endpush
