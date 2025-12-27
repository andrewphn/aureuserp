{{-- Team Assignment Section (Purple) --}}
<div class="rounded-lg border border-purple-200 dark:border-purple-800 overflow-hidden">
    {{-- Header --}}
    <div class="bg-purple-500 px-4 py-2.5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-heroicon-s-user-group class="w-5 h-5 text-white" />
            <h4 class="text-white font-semibold">Team Assignment</h4>
        </div>
    </div>

    {{-- Content --}}
    <div class="bg-white dark:bg-gray-900 p-4">
        {{-- Team Members Grid --}}
        <div class="grid grid-cols-3 gap-4 mb-4">
            {{-- Project Manager --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center mb-2">
                    @if($project->user)
                        <span class="text-lg font-bold text-purple-700 dark:text-purple-300">
                            {{ strtoupper(substr($project->user->name, 0, 1)) }}
                        </span>
                    @else
                        <x-heroicon-o-user class="w-6 h-6 text-purple-400" />
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ $project->user?->name ?? 'Unassigned' }}
                </p>
                <p class="text-xs text-gray-500">Project Manager</p>
            </div>

            {{-- Designer --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center mb-2">
                    @if($project->designer)
                        <span class="text-lg font-bold text-pink-700 dark:text-pink-300">
                            {{ strtoupper(substr($project->designer->name, 0, 1)) }}
                        </span>
                    @else
                        <x-heroicon-o-paint-brush class="w-6 h-6 text-pink-400" />
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ $project->designer?->name ?? 'Unassigned' }}
                </p>
                <p class="text-xs text-gray-500">Designer</p>
            </div>

            {{-- Purchasing Manager --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center mb-2">
                    @if($project->purchasingManager)
                        <span class="text-lg font-bold text-indigo-700 dark:text-indigo-300">
                            {{ strtoupper(substr($project->purchasingManager->name, 0, 1)) }}
                        </span>
                    @else
                        <x-heroicon-o-shopping-cart class="w-6 h-6 text-indigo-400" />
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ $project->purchasingManager?->name ?? 'Unassigned' }}
                </p>
                <p class="text-xs text-gray-500">Purchasing Mgr</p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
            <button
                wire:click="openAssignDesignerModal"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
            >
                <x-heroicon-s-paint-brush class="w-4 h-4 mr-1" />
                {{ $project->designer ? 'Change' : 'Assign' }} Designer
            </button>
            <button
                wire:click="openAssignPurchasingModal"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
            >
                <x-heroicon-s-shopping-cart class="w-4 h-4 mr-1" />
                {{ $project->purchasingManager ? 'Change' : 'Assign' }} Purchasing
            </button>
        </div>
    </div>
</div>
