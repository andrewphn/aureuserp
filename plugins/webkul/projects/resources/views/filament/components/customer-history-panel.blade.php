<div
    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
    x-init="
        const observer = new MutationObserver(() => {
            isDark = document.documentElement.classList.contains('dark');
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    "
    :class="isDark ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'"
    class="rounded-lg p-4 border"
>
    <div class="flex items-center gap-3 mb-3">
        <div :class="isDark ? 'bg-primary-500/20' : 'bg-primary-100'" class="h-10 w-10 rounded-full flex items-center justify-center">
            <x-heroicon-o-user :class="isDark ? 'text-primary-400' : 'text-primary-600'" class="h-5 w-5" />
        </div>
        <div>
            <p :class="isDark ? 'text-gray-100' : 'text-gray-900'" class="font-medium">{{ $partner->name }}</p>
            <p :class="isDark ? 'text-gray-400' : 'text-gray-500'" class="text-xs">Customer since {{ $partner->created_at?->format('M Y') ?? 'Unknown' }}</p>
        </div>
        @if($totalProjects >= 3)
            <span :class="isDark ? 'bg-warning-500/20 text-warning-300' : 'bg-warning-100 text-warning-800'" class="ml-auto inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                <x-heroicon-s-star class="h-3 w-3 mr-1" />
                VIP
            </span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div :class="isDark ? 'bg-gray-900' : 'bg-white'" class="text-center py-2 rounded">
            <p :class="isDark ? 'text-gray-100' : 'text-gray-900'" class="text-2xl font-bold">{{ $totalProjects }}</p>
            <p :class="isDark ? 'text-gray-400' : 'text-gray-500'" class="text-xs">Total Projects</p>
        </div>
        <div :class="isDark ? 'bg-gray-900' : 'bg-white'" class="text-center py-2 rounded">
            <p :class="isDark ? 'text-gray-100' : 'text-gray-900'" class="text-2xl font-bold">
                @if($totalProjects > 0)
                    Repeat
                @else
                    New
                @endif
            </p>
            <p :class="isDark ? 'text-gray-400' : 'text-gray-500'" class="text-xs">Customer Type</p>
        </div>
    </div>
</div>
