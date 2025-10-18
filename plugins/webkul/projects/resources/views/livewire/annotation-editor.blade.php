<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-end">
        <div class="bg-white h-full w-full max-w-md shadow-xl overflow-y-auto">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-6">Annotation Details</h2>

                <form wire:submit="save" class="space-y-4">
                    {{-- Type Display --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <div class="text-sm text-gray-900">
                            @if($annotationType === 'location')
                                📍 Location
                            @elseif($annotationType === 'cabinet_run')
                                📦 Cabinet Run
                            @elseif($annotationType === 'cabinet')
                                🗄️ Cabinet
                            @else
                                {{ ucfirst(str_replace('_', ' ', $annotationType ?? 'unknown')) }}
                            @endif
                        </div>
                    </div>

                    {{-- Context Display --}}
                    @if($roomName || $locationName)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location Context</label>
                        <div class="text-sm text-gray-900">
                            @if($roomName)
                                🏠 {{ $roomName }}
                            @endif
                            @if($roomName && $locationName)
                                →
                            @endif
                            @if($locationName)
                                📍 {{ $locationName }}
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Label Input --}}
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700 mb-1">
                            Label <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="label"
                            wire:model="label"
                            placeholder="e.g., Run 1, Base Run A"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    {{-- Notes Textarea --}}
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes / Comments</label>
                        <textarea
                            id="notes"
                            wire:model="notes"
                            rows="3"
                            placeholder="Add any notes or comments about this annotation..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    {{-- Measurements (only for cabinet_run and cabinet) --}}
                    @if(in_array($annotationType, ['cabinet_run', 'cabinet']))
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">📏 Measurements</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="measurementWidth" class="block text-sm font-medium text-gray-700 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    id="measurementWidth"
                                    wire:model="measurementWidth"
                                    step="0.125"
                                    placeholder="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div>
                                <label for="measurementHeight" class="block text-sm font-medium text-gray-700 mb-1">
                                    Height (in)
                                </label>
                                <input
                                    type="number"
                                    id="measurementHeight"
                                    wire:model="measurementHeight"
                                    step="0.125"
                                    placeholder="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            wire:click="cancel"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            💾 Save Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
