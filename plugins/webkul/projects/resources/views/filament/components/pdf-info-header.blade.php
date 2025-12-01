@props(['pdfDocument', 'totalPages'])

<div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="flex-shrink-0">
        <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
            <x-heroicon-o-document-text class="w-6 h-6 text-primary-600 dark:text-primary-400" />
        </div>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ $pdfDocument->file_name }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ $totalPages }} {{ Str::plural('page', $totalPages) }} to classify
        </p>
    </div>
    @if($pdfDocument->file_path)
        <a href="{{ Storage::disk('public')->url($pdfDocument->file_path) }}"
           target="_blank"
           class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50 transition-colors">
            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
            Open PDF
        </a>
    @endif
</div>
