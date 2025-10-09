@php
    $pdfCount = $pdfs->count();
    $totalSize = $pdfs->sum('file_size');
    $totalSizeMB = round($totalSize / 1048576, 2);
@endphp

<div class="space-y-3">
    {{-- Compact Summary Stats --}}
    @if($pdfCount > 0)
        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 px-1">
            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $pdfCount }}</span>
            <span>{{ $pdfCount === 1 ? 'file' : 'files' }}</span>
            <span>•</span>
            <span>{{ $totalSizeMB }} MB total</span>
        </div>
    @endif

    {{-- Previously Uploaded PDFs - Compact Grid --}}
    @if($pdfCount > 0)
        <div class="grid grid-cols-1 gap-2">
            @foreach($pdfs as $pdf)
                <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">{{ $pdf->file_name }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ round($pdf->file_size / 1048576, 2) }} MB • {{ $pdf->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{ Storage::disk('public')->url($pdf->file_path) }}"
                           target="_blank"
                           class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                           title="View PDF">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        <a href="{{ Storage::disk('public')->url($pdf->file_path) }}"
                           download
                           class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                           title="Download PDF">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </a>
                        @if($context === 'edit' && $record)
                            <button type="button"
                                    wire:click="$parent.deletePdf({{ $pdf->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                    title="Delete PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- New PDF Button --}}
    @if($context === 'edit' && $record)
        <div class="px-1">
            <button type="button"
                    wire:click="mountAction('uploadPdf')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>New PDF</span>
            </button>
        </div>
    @endif

    {{-- Upload Instructions --}}
    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 px-1 mt-2">
        <div class="flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1 a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <span>Save project first, then upload PDFs in Documents tab</span>
        </div>
    </div>
</div>
