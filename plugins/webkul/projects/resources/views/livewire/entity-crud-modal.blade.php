<div>
    @if($showModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/50 dark:bg-gray-950/75"
        x-data
        x-on:keydown.escape.window="$wire.closeModal()"
    >
        {{-- Modal Container --}}
        <div class="relative w-full max-w-xl mx-4 bg-white dark:bg-gray-800 rounded-xl shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $this->getModalHeading() }}
                </h2>
                <button
                    wire:click="closeModal"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                {{-- Flash Messages --}}
                @if(session('error'))
                    <div class="p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-300 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Room Form --}}
                @if($entityType === 'room')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Room Name <span class="text-danger-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="formData.name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Kitchen, Master Bathroom"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Room Type
                                </label>
                                <select
                                    wire:model="formData.room_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getRoomTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Floor Number
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.floor_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="0"
                                    max="99"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Square Footage (optional)
                            </label>
                            <input
                                type="number"
                                wire:model="formData.square_footage"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 150"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="Any additional notes..."
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Room Location Form --}}
                @if($entityType === 'room_location')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Location Name <span class="text-danger-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="formData.name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Sink Wall, Island, Pantry Alcove"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Location Type
                                </label>
                                <select
                                    wire:model="formData.location_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getLocationTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (inches)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.overall_width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Cabinet Level
                                </label>
                                <select
                                    wire:model="formData.cabinet_level"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">Select...</option>
                                    @foreach($this->getPricingTiers() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Material Category
                                </label>
                                <select
                                    wire:model="formData.material_category"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">Select...</option>
                                    @foreach($this->getMaterialCategories() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish Option
                                </label>
                                <select
                                    wire:model="formData.finish_option"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">Select...</option>
                                    @foreach($this->getFinishOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Cabinet Run Form --}}
                @if($entityType === 'cabinet_run')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Run Name <span class="text-danger-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="formData.name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Base Run 1, Upper Cabinets"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Run Type
                                </label>
                                <select
                                    wire:model="formData.run_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getRunTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Linear Feet
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.linear_feet"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.1"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sort Order
                            </label>
                            <input
                                type="number"
                                wire:model="formData.sort_order"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                min="0"
                            />
                        </div>
                    </div>
                @endif

                {{-- Cabinet Form --}}
                @if($entityType === 'cabinet')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Cabinet Name
                            </label>
                            <input
                                type="text"
                                wire:model="formData.name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., B24, W3012, SB36"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Cabinet Type
                                </label>
                                <select
                                    wire:model="formData.cabinet_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getCabinetTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.quantity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="1"
                                />
                            </div>
                        </div>

                        {{-- Dimensions --}}
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.length_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Depth (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.depth_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Height (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.height_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                        </div>

                        {{-- Construction & Style --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Construction Style
                                </label>
                                <select
                                    wire:model="formData.construction_style"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="frameless">Frameless</option>
                                    <option value="framed">Framed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Style
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.door_style"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Shaker, Slab"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Material
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.material"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Maple, Plywood"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.finish"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Natural, Painted"
                                />
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Section Form --}}
                @if($entityType === 'section')
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Section Type
                                </label>
                                <select
                                    wire:model="formData.section_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="door">Door</option>
                                    <option value="drawer">Drawer</option>
                                    <option value="open_shelf">Open Shelf</option>
                                    <option value="pull_out">Pull Out</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hinge Side
                                </label>
                                <select
                                    wire:model="formData.hinge_side"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                    <option value="top">Top</option>
                                    <option value="bottom">Bottom</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Height (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.height_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.5"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Overlay Style
                            </label>
                            <input
                                type="text"
                                wire:model="formData.overlay_style"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Full Overlay, Inset"
                            />
                        </div>
                    </div>
                @endif

                {{-- Door Form --}}
                @if($entityType === 'door')
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Number
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.door_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., D1, D2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Name
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.door_name"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Left Door, Upper Door"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.125"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Height (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.height_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.125"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hinge Side
                                </label>
                                <select
                                    wire:model="formData.hinge_side"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                    <option value="top">Top</option>
                                    <option value="bottom">Bottom</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish Type
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.finish_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Paint, Stain"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    wire:model="formData.has_glass"
                                    class="rounded border-gray-300 dark:border-gray-600"
                                />
                                Has Glass Insert
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Drawer Form --}}
                @if($entityType === 'drawer')
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Number
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.drawer_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., DR1, DR2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Name
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.drawer_name"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Top Drawer, Utensil Drawer"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Front Width (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.front_width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.125"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Front Height (in)
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.front_height_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    step="0.125"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Position
                                </label>
                                <input
                                    type="number"
                                    wire:model="formData.drawer_position"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="1"
                                    placeholder="1 = top"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Slide Type
                                </label>
                                <select
                                    wire:model="formData.slide_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">Select...</option>
                                    <option value="undermount">Undermount</option>
                                    <option value="side_mount">Side Mount</option>
                                    <option value="center_mount">Center Mount</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish Type
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.finish_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Paint, Stain"
                                />
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        wire:model="formData.soft_close"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                    />
                                    Soft Close
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Shelf Form --}}
                @if($entityType === 'shelf')
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Shelf Number
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.shelf_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., S1, S2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Shelf Name
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.shelf_name"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Top Shelf, Middle Shelf"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Depth (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.depth_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Thickness (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.thickness_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Shelf Type
                                </label>
                                <select
                                    wire:model="formData.shelf_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="fixed">Fixed Shelf</option>
                                    <option value="adjustable">Adjustable Shelf</option>
                                    <option value="roll_out">Roll-Out Shelf</option>
                                    <option value="pull_down">Pull-Down Shelf</option>
                                    <option value="corner">Corner Shelf</option>
                                    <option value="floating">Floating Shelf</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Material
                                </label>
                                <select
                                    wire:model="formData.material"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="plywood">Plywood</option>
                                    <option value="mdf">MDF</option>
                                    <option value="melamine">Melamine</option>
                                    <option value="solid_wood">Solid Wood</option>
                                    <option value="glass">Glass</option>
                                    <option value="wire">Wire</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Edge Treatment
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.edge_treatment"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Banded, Bullnose"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish Type
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.finish_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Paint, Stain"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Pullout Form --}}
                @if($entityType === 'pullout')
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pullout Number
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.pullout_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., PO1, PO2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pullout Name
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.pullout_name"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Trash Pullout"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Pullout Type
                            </label>
                            <select
                                wire:model="formData.pullout_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option value="trash">Trash Pullout</option>
                                <option value="recycling">Recycling Pullout</option>
                                <option value="spice_rack">Spice Rack</option>
                                <option value="tray_divider">Tray Divider</option>
                                <option value="cutting_board">Cutting Board</option>
                                <option value="mixer_lift">Mixer Lift</option>
                                <option value="blind_corner">Blind Corner Pullout</option>
                                <option value="lazy_susan">Lazy Susan</option>
                                <option value="roll_out_tray">Roll-Out Tray</option>
                                <option value="pantry_pullout">Pantry Pullout</option>
                                <option value="utensil_divider">Utensil Divider</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Manufacturer
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.manufacturer"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., Rev-A-Shelf"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Model Number
                                </label>
                                <input
                                    type="text"
                                    wire:model="formData.model_number"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., 4WCSC2135DM2"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.width_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Height (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.height_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Depth (in)
                                </label>
                                <input
                                    type="number"
                                    step="0.125"
                                    wire:model="formData.depth_inches"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    wire:model="formData.quantity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        wire:model="formData.soft_close"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                    />
                                    Soft Close
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                            </label>
                            <textarea
                                wire:model="formData.notes"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            ></textarea>
                        </div>
                    </div>
                @endif

                {{-- Default fallback for other entity types --}}
                @if(!in_array($entityType, ['room', 'room_location', 'cabinet_run', 'cabinet', 'section', 'door', 'drawer', 'shelf', 'pullout']))
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name
                            </label>
                            <input
                                type="text"
                                wire:model="formData.name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                    </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                @if($mode === 'edit')
                    <button
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this {{ $entityLabels[$entityType] ?? 'entity' }}? This cannot be undone."
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-danger-600 hover:text-danger-700 hover:bg-danger-50 dark:text-danger-400 dark:hover:text-danger-300 dark:hover:bg-danger-900/20 rounded-lg transition-colors"
                    >
                        <x-heroicon-o-trash class="w-4 h-4" />
                        Delete
                    </button>
                @else
                    <div></div>
                @endif

                <div class="flex items-center gap-3">
                    <button
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors"
                    >
                        {{ $mode === 'create' ? 'Create' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
