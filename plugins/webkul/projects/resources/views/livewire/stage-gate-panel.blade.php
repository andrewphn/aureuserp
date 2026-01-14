<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Stage Progress
                </h3>
                @if($currentStage)
                    <span class="px-2.5 py-1 text-sm font-medium rounded-full"
                          style="background-color: {{ $currentStage->color ?? '#3b82f6' }}20; color: {{ $currentStage->color ?? '#3b82f6' }}">
                        {{ $currentStage->name }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                {{-- Lock Indicators --}}
                @if($lockStatus['design_locked'])
                    <span class="flex items-center gap-1 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded-full"
                          title="Design locked on {{ $lockStatus['design_locked_at'] }}">
                        <x-heroicon-s-lock-closed class="w-3 h-3"/>
                        Design
                    </span>
                @endif
                @if($lockStatus['procurement_locked'])
                    <span class="flex items-center gap-1 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full"
                          title="Procurement locked on {{ $lockStatus['procurement_locked_at'] }}">
                        <x-heroicon-s-lock-closed class="w-3 h-3"/>
                        Procurement
                    </span>
                @endif
                @if($lockStatus['production_locked'])
                    <span class="flex items-center gap-1 px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded-full"
                          title="Production locked on {{ $lockStatus['production_locked_at'] }}">
                        <x-heroicon-s-lock-closed class="w-3 h-3"/>
                        Production
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 transition-all duration-500"
                     style="width: {{ $progress }}%"></div>
            </div>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $progress }}%</span>
        </div>
    </div>

    {{-- Gate Requirements --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($gates as $gate)
            <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            @if($gate['passed'])
                                <span class="flex items-center justify-center w-6 h-6 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-full">
                                    <x-heroicon-s-check class="w-4 h-4"/>
                                </span>
                            @else
                                <span class="flex items-center justify-center w-6 h-6 bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400 rounded-full">
                                    <x-heroicon-s-clock class="w-4 h-4"/>
                                </span>
                            @endif
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                {{ $gate['name'] }}
                            </h4>
                            @if($gate['is_blocking'])
                                <span class="px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded">
                                    Blocking
                                </span>
                            @endif
                        </div>

                        {{-- Requirements Progress --}}
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $gate['requirements_passed'] }}/{{ $gate['requirements_total'] }} requirements met</span>
                            @if(!$gate['passed'] && count($gate['blockers']) > 0)
                                <button wire:click="showGateBlockers('{{ $gate['gate_key'] }}')"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline">
                                    View blockers
                                </button>
                            @endif
                        </div>

                        {{-- Quick Blocker Preview --}}
                        @if(!$gate['passed'] && count($gate['blockers']) > 0)
                            <ul class="mt-2 space-y-1">
                                @foreach(array_slice($gate['blockers'], 0, 3) as $blocker)
                                    <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <x-heroicon-o-x-circle class="w-4 h-4 text-red-500"/>
                                        {{ $blocker['error_message'] }}
                                    </li>
                                @endforeach
                                @if(count($gate['blockers']) > 3)
                                    <li class="text-sm text-gray-500 dark:text-gray-500 pl-6">
                                        +{{ count($gate['blockers']) - 3 }} more...
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                No gates defined for this stage.
            </div>
        @endforelse
    </div>

    {{-- Footer Actions --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <button wire:click="refreshGateStatus"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin"/>
                <span wire:loading.remove>Check Gates</span>
                <span wire:loading>Checking...</span>
            </button>

            <button wire:click="attemptAdvance"
                    @if(!$canAdvance) wire:click="showBlockers" @endif
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors
                           {{ $canAdvance 
                               ? 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500' 
                               : 'bg-gray-400 cursor-not-allowed' }}">
                @if($canAdvance)
                    <x-heroicon-o-arrow-right class="w-4 h-4"/>
                    Advance Stage
                @else
                    <x-heroicon-o-lock-closed class="w-4 h-4"/>
                    {{ count($gates) > 0 ? 'Blocked' : 'Configure Gates' }}
                @endif
            </button>
        </div>
    </div>

    {{-- Blockers Modal --}}
    @if($showBlockersModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                     wire:click="closeBlockersModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900 sm:mx-0 sm:h-10 sm:w-10">
                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400"/>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Cannot Advance Stage
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        The following requirements must be met:
                                    </p>
                                </div>
                                <div class="mt-4 space-y-3 max-h-64 overflow-y-auto">
                                    @foreach($selectedGateBlockers as $blocker)
                                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <x-heroicon-o-x-circle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"/>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $blocker['error_message'] }}
                                                </p>
                                                @if(!empty($blocker['help_text']))
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $blocker['help_text'] }}
                                                    </p>
                                                @endif
                                                @if(!empty($blocker['action_label']))
                                                    <button class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                                        {{ $blocker['action_label'] }} →
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeBlockersModal"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
