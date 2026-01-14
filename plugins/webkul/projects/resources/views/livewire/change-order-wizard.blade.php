<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
        <h2 class="text-xl font-bold text-white">Change Order Wizard</h2>
        <p class="text-primary-100 text-sm mt-1">{{ $stepTitle }}</p>
    </div>

    {{-- Progress Steps --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            @foreach(range(1, $totalSteps) as $step)
                <div class="flex items-center {{ $step < $totalSteps ? 'flex-1' : '' }}">
                    <button 
                        wire:click="goToStep({{ $step }})"
                        class="flex items-center justify-center w-10 h-10 rounded-full transition-colors
                               {{ $currentStep >= $step 
                                   ? 'bg-primary-600 text-white' 
                                   : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}
                               {{ $currentStep > $step ? 'cursor-pointer hover:bg-primary-700' : '' }}
                               {{ $currentStep === $step ? 'ring-4 ring-primary-200 dark:ring-primary-800' : '' }}"
                        @if($step > $currentStep) disabled @endif
                    >
                        @if($currentStep > $step)
                            <x-heroicon-s-check class="w-5 h-5"/>
                        @else
                            {{ $step }}
                        @endif
                    </button>
                    @if($step < $totalSteps)
                        <div class="flex-1 h-1 mx-3 {{ $currentStep > $step ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <div class="p-6">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
                {{ session('message') }}
            </div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Step 1: Basic Information --}}
        @if($currentStep === 1)
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Change Order Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="title"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                           placeholder="Brief description of the change">
                    @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Reason for Change <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="reason"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @foreach($reasonOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea wire:model="description"
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                              placeholder="Detailed explanation of why this change is needed"></textarea>
                </div>
            </div>
        @endif

        {{-- Step 2: Specify Changes --}}
        @if($currentStep === 2)
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Changes to Make</h3>
                    <button wire:click="addChange"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900 rounded-lg transition-colors">
                        <x-heroicon-o-plus class="w-4 h-4"/>
                        Add Change
                    </button>
                </div>

                @if(count($changes) === 0)
                    <div class="text-center py-8 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <x-heroicon-o-document-plus class="w-12 h-12 mx-auto text-gray-400"/>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">No changes added yet.</p>
                        <button wire:click="addChange"
                                class="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            Add First Change
                        </button>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($changes as $index => $change)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-start justify-between mb-4">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Change #{{ $index + 1 }}</span>
                                    <button wire:click="removeChange({{ $index }})"
                                            class="text-red-500 hover:text-red-600">
                                        <x-heroicon-o-trash class="w-5 h-5"/>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Entity Type</label>
                                        <select wire:model="changes.{{ $index }}.entity_type"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white">
                                            <option value="">Select Type</option>
                                            <option value="Cabinet">Cabinet</option>
                                            <option value="Door">Door</option>
                                            <option value="Drawer">Drawer</option>
                                            <option value="Shelf">Shelf</option>
                                            <option value="Pullout">Pullout</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Entity ID</label>
                                        <input type="number" 
                                               wire:model="changes.{{ $index }}.entity_id"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white"
                                               placeholder="ID">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Field Name</label>
                                        <input type="text" 
                                               wire:model="changes.{{ $index }}.field_name"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white"
                                               placeholder="e.g., width_inches">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">New Value</label>
                                        <input type="text" 
                                               wire:model="changes.{{ $index }}.new_value"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white"
                                               placeholder="New value">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @error('changes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- Step 3: Review Impact --}}
        @if($currentStep === 3)
            <div class="space-y-6">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Summary</h3>
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Total Changes</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($changes) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Price Impact</dt>
                            <dd class="text-2xl font-bold {{ $priceDelta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $priceDelta >= 0 ? '+' : '' }}${{ number_format($priceDelta, 2) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Changes to Apply</h4>
                    <div class="space-y-2">
                        @foreach($changes as $change)
                            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <x-heroicon-o-pencil-square class="w-5 h-5 text-primary-500"/>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $change['entity_type'] }} #{{ $change['entity_id'] }}: 
                                    <span class="font-mono">{{ $change['field_name'] }}</span> → 
                                    <span class="font-semibold">{{ $change['new_value'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 4: Submit --}}
        @if($currentStep === 4)
            <div class="text-center py-8">
                <x-heroicon-o-check-badge class="w-16 h-16 mx-auto text-green-500 mb-4"/>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Ready to Submit</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Your change order will be submitted for approval.
                </p>
                <button wire:click="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-200 transition-colors disabled:opacity-50">
                    <span wire:loading.remove>Submit Change Order</span>
                    <span wire:loading>Submitting...</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Footer Navigation --}}
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-between">
        <button wire:click="previousStep"
                @if($currentStep === 1) disabled @endif
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <x-heroicon-o-arrow-left class="w-4 h-4 inline mr-1"/>
            Previous
        </button>

        @if($currentStep < $totalSteps)
            <button wire:click="nextStep"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-200 transition-colors">
                Next
                <x-heroicon-o-arrow-right class="w-4 h-4 inline ml-1"/>
            </button>
        @endif
    </div>
</div>
