<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

        <!-- Modal panel -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">

                <!-- Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Complete Hierarchy for {{ $annotation['label'] ?? 'Annotation' }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Complete the missing hierarchy levels to save this annotation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="bg-gray-50 px-4 py-5 sm:p-6">
                    <div class="space-y-6">
                        @foreach($missingLevels as $level)
                            @php
                                $entityType = $level['type'];
                                $form = $levelForms[$entityType] ?? [];
                            @endphp

                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <h4 class="text-base font-medium text-gray-900 mb-4">
                                    {{ $this->getEntityDisplayName($entityType) }}
                                </h4>

                                <!-- Mode Selection: Create New or Link Existing -->
                                <div class="space-y-4">
                                    <!-- Create New Option -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input
                                                wire:model.live="levelForms.{{ $entityType }}.mode"
                                                id="create-{{ $entityType }}"
                                                name="mode-{{ $entityType }}"
                                                type="radio"
                                                value="create"
                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                                            >
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label for="create-{{ $entityType }}" class="font-medium text-gray-700">
                                                Create New
                                            </label>

                                            @if($form['mode'] === 'create')
                                            <div class="mt-3 space-y-3">
                                                <!-- Name Field -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">
                                                        Name <span class="text-red-500">*</span>
                                                    </label>
                                                    <input
                                                        wire:model="levelForms.{{ $entityType }}.create_data.name"
                                                        type="text"
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    >
                                                </div>

                                                <!-- Type-specific fields -->
                                                @if($entityType === 'room')
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Room Type</label>
                                                        <select wire:model="levelForms.{{ $entityType }}.create_data.room_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                            <option value="kitchen">Kitchen</option>
                                                            <option value="bathroom">Bathroom</option>
                                                            <option value="bedroom">Bedroom</option>
                                                            <option value="living_room">Living Room</option>
                                                            <option value="office">Office</option>
                                                            <option value="closet">Closet</option>
                                                            <option value="general">General</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Floor Number</label>
                                                        <input wire:model="levelForms.{{ $entityType }}.create_data.floor_number" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                    </div>
                                                @endif

                                                @if($entityType === 'room_location')
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Location Type</label>
                                                        <select wire:model="levelForms.{{ $entityType }}.create_data.location_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                            <option value="wall">Wall</option>
                                                            <option value="island">Island</option>
                                                            <option value="peninsula">Peninsula</option>
                                                            <option value="corner">Corner</option>
                                                        </select>
                                                    </div>
                                                @endif

                                                @if($entityType === 'cabinet_run')
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Run Type</label>
                                                        <select wire:model="levelForms.{{ $entityType }}.create_data.run_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                            <option value="base">Base Cabinets</option>
                                                            <option value="wall">Wall Cabinets</option>
                                                            <option value="tall">Tall Cabinets</option>
                                                        </select>
                                                    </div>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Link Existing Option -->
                                    @if(count($form['existing_options'] ?? []) > 0)
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input
                                                wire:model.live="levelForms.{{ $entityType }}.mode"
                                                id="link-{{ $entityType }}"
                                                name="mode-{{ $entityType }}"
                                                type="radio"
                                                value="link"
                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                                            >
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label for="link-{{ $entityType }}" class="font-medium text-gray-700">
                                                Link Existing
                                            </label>

                                            @if($form['mode'] === 'link')
                                            <div class="mt-3">
                                                <select wire:model="levelForms.{{ $entityType }}.existing_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                    <option value="">Select {{ $this->getEntityDisplayName($entityType) }}</option>
                                                    @foreach($form['existing_options'] as $option)
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button
                        wire:click="saveHierarchy"
                        type="button"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Create & Save
                    </button>
                    <button
                        wire:click="closeModal"
                        type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
