@php
$bgColor = match($status ?? 'no-estimate') {
    'complete' => 'bg-success-100 dark:bg-success-500/20 text-success-700 dark:text-success-300',
    'missing' => 'bg-warning-100 dark:bg-warning-500/20 text-warning-700 dark:text-warning-300',
    'over' => 'bg-danger-100 dark:bg-danger-500/20 text-danger-700 dark:text-danger-300',
    default => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
};
@endphp

<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $bgColor }}">
    {{ $text }}
</span>

@if(isset($allocated) && isset($estimated) && $status === 'missing')
<div class="text-xs text-gray-500 mt-1">
    {{ number_format($allocated, 1) }} / {{ number_format($estimated, 1) }} LF
</div>
@endif
