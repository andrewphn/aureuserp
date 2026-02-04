<div class="space-y-4" wire:poll.5s>
    @if($sheet)
        {{-- Sheet Info Header --}}
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $sheet->file_name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sheet {{ $sheet->sheet_number ?? '?' }} &bull;
                    {{ $sheet->cncProgram?->material_code ?? 'Unknown material' }}
                </p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $cutParts->count() }}</div>
                <div class="text-sm text-gray-500">Cut Parts</div>
            </div>
        </div>

        {{-- QA Summary --}}
        @if($cutParts->count() > 0)
            <div class="grid grid-cols-6 gap-3">
                <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="text-xl font-bold text-gray-600 dark:text-gray-300">{{ $cutParts->where('status', 'pending')->count() }}</div>
                    <div class="text-xs text-gray-500">Pending</div>
                </div>
                <div class="text-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $cutParts->where('status', 'cut')->count() }}</div>
                    <div class="text-xs text-gray-500">Cut</div>
                </div>
                <div class="text-center p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $cutParts->where('status', 'passed')->count() }}</div>
                    <div class="text-xs text-gray-500">Passed</div>
                </div>
                <div class="text-center p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ $cutParts->where('status', 'failed')->count() }}</div>
                    <div class="text-xs text-gray-500">Failed</div>
                </div>
                <div class="text-center p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $cutParts->where('status', 'recut_needed')->count() }}</div>
                    <div class="text-xs text-gray-500">Recut</div>
                </div>
                <div class="text-center p-3 bg-red-200 dark:bg-red-900/50 rounded-lg">
                    <div class="text-xl font-bold text-red-700 dark:text-red-300">{{ $cutParts->where('status', 'scrapped')->count() }}</div>
                    <div class="text-xs text-gray-500">Scrapped</div>
                </div>
            </div>
        @endif

        {{-- Parts Table --}}
        @if($cutParts->count() > 0)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Label</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Dimensions</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Notes</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($cutParts as $part)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $part->part_label }}</span>
                                    @if($part->is_recut)
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 rounded">RECUT #{{ $part->recut_count }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $part->part_type_name }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $part->dimensions ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $part->status === 'pending',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' => $part->status === 'cut',
                                        'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' => $part->status === 'passed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' => $part->status === 'failed',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' => $part->status === 'recut_needed',
                                        'bg-red-200 text-red-800 dark:bg-red-900/70 dark:text-red-200' => $part->status === 'scrapped',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $part->status)) }}
                                    </span>
                                    @if($part->failure_reason)
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                            {{ \Webkul\Project\Models\CncCutPart::getFailureReasons()[$part->failure_reason] ?? $part->failure_reason }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ $part->notes ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1 flex-wrap">
                                        @if($part->status === 'pending')
                                            {{-- Mark as Cut --}}
                                            <button
                                                type="button"
                                                wire:click="markCut({{ $part->id }})"
                                                class="p-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                                                title="Mark as Cut"
                                            >
                                                <x-heroicon-o-scissors class="w-4 h-4" />
                                            </button>
                                        @endif

                                        @if(in_array($part->status, ['pending', 'cut']))
                                            {{-- Pass Button --}}
                                            <button
                                                type="button"
                                                wire:click="passPart({{ $part->id }})"
                                                wire:loading.attr="disabled"
                                                class="p-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50"
                                                title="Pass QA"
                                            >
                                                <x-heroicon-o-check class="w-4 h-4" />
                                            </button>

                                            {{-- Fail Button --}}
                                            <button
                                                type="button"
                                                wire:click="openFailModal({{ $part->id }})"
                                                class="p-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                                                title="Fail QA"
                                            >
                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                            </button>
                                        @endif

                                        @if(in_array($part->status, ['failed', 'scrapped']))
                                            {{-- Recut Button --}}
                                            <button
                                                type="button"
                                                wire:click="recutPart({{ $part->id }})"
                                                wire:loading.attr="disabled"
                                                class="p-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors disabled:opacity-50"
                                                title="Create Recut"
                                            >
                                                <x-heroicon-o-arrow-path class="w-4 h-4" />
                                            </button>
                                        @endif

                                        @if($part->status === 'failed')
                                            {{-- Scrap Button --}}
                                            <button
                                                type="button"
                                                wire:click="markScrapped({{ $part->id }})"
                                                wire:confirm="Are you sure you want to scrap this part?"
                                                class="p-2 bg-red-700 hover:bg-red-800 text-white rounded-lg transition-colors"
                                                title="Mark as Scrapped"
                                            >
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        @endif

                                        {{-- Comment Button --}}
                                        <button
                                            type="button"
                                            wire:click="openCommentModal({{ $part->id }})"
                                            class="p-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                                            title="Add Comment"
                                        >
                                            <x-heroicon-o-chat-bubble-left class="w-4 h-4" />
                                        </button>

                                        {{-- Delete Button --}}
                                        <button
                                            type="button"
                                            wire:click="deletePart({{ $part->id }})"
                                            wire:confirm="Are you sure you want to delete this part?"
                                            class="p-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-600 dark:text-gray-300 rounded-lg transition-colors"
                                            title="Delete Part"
                                        >
                                            <x-heroicon-o-x-circle class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-squares-2x2 class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p class="text-lg">No cut parts recorded</p>
                <p class="text-sm mt-1">Add individual cabinet parts to track QA status</p>
            </div>
        @endif

        {{-- Quick Add Form --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Quick Add Part</h4>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Part Label *</label>
                    <input
                        type="text"
                        wire:model="newPartLabel"
                        placeholder="e.g., BS-1"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Part Type</label>
                    <select
                        wire:model="newPartType"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    >
                        <option value="">Select type...</option>
                        @foreach($this->getPartTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button
                    type="button"
                    wire:click="addQuickPart({{ $sheet->id }})"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="addQuickPart">Add Part</span>
                    <span wire:loading wire:target="addQuickPart">Adding...</span>
                </button>
            </div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>No sheet selected</p>
        </div>
    @endif

    {{-- Fail Modal --}}
    @if($showFailModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Fail QA - Select Reason</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Failure Reason *</label>
                        <select
                            wire:model="failureReason"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="">Select reason...</option>
                            @foreach($this->getFailureReasonOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (optional)</label>
                        <textarea
                            wire:model="failureNotes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            placeholder="Describe the issue..."
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        wire:click="closeFailModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="submitFailure"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitFailure">Mark as Failed</span>
                        <span wire:loading wire:target="submitFailure">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Comment Modal --}}
    @if($showCommentModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Add Comment</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Comment *</label>
                    <textarea
                        wire:model="commentText"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        placeholder="Enter your comment..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        wire:click="closeCommentModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="submitComment"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitComment">Add Comment</span>
                        <span wire:loading wire:target="submitComment">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
