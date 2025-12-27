{{-- Sales & Orders Section (Indigo) --}}
@php
    $orders = $project->orders()->with('partner')->latest()->limit(3)->get();
    $totalOrderValue = $project->orders()->sum('grand_total');
    $sourceQuote = $project->sourceQuote ?? null;
@endphp

<div class="rounded-lg border border-indigo-200 dark:border-indigo-800 overflow-hidden">
    {{-- Header --}}
    <div class="bg-indigo-500 px-4 py-2.5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-heroicon-s-currency-dollar class="w-5 h-5 text-white" />
            <h4 class="text-white font-semibold">Sales & Orders</h4>
        </div>
        @if($totalOrderValue > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                ${{ number_format($totalOrderValue, 2) }} total
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="bg-white dark:bg-gray-900 p-4 space-y-4">
        {{-- Source Quote (if exists) --}}
        @if($sourceQuote)
            <div class="p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-indigo-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Source Quote</span>
                    </div>
                    <span class="text-sm text-indigo-600 dark:text-indigo-400">{{ $sourceQuote->name ?? 'N/A' }}</span>
                </div>
            </div>
        @endif

        {{-- Recent Orders --}}
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Orders</h5>
            @if($orders->isEmpty())
                <p class="text-sm text-gray-500 italic">No orders yet</p>
            @else
                <div class="space-y-2">
                    @foreach($orders as $order)
                        <div class="flex items-center justify-between text-sm p-2 rounded bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-shopping-bag class="w-4 h-4 text-gray-400" />
                                <span class="text-gray-900 dark:text-gray-100">{{ $order->name ?? 'Order #' . $order->id }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ${{ number_format($order->grand_total ?? 0, 2) }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $order->state === 'sale' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $order->state === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $order->state === 'cancel' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ ucfirst($order->state ?? 'draft') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Summary Stats --}}
        @if($orders->isNotEmpty())
            <div class="grid grid-cols-2 gap-4 py-2">
                <div class="text-center p-2 rounded bg-gray-50 dark:bg-gray-800">
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $orders->count() }}</p>
                    <p class="text-xs text-gray-500">Orders</p>
                </div>
                <div class="text-center p-2 rounded bg-gray-50 dark:bg-gray-800">
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($totalOrderValue, 0) }}</p>
                    <p class="text-xs text-gray-500">Total Value</p>
                </div>
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a
                href="{{ route('filament.admin.resources.orders.create', ['project_id' => $project->id]) }}"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <x-heroicon-s-plus class="w-4 h-4 mr-1" />
                Create Order
            </a>
            @if($orders->isNotEmpty())
                <a
                    href="{{ route('filament.admin.resources.orders.index', ['tableFilters' => ['project_id' => ['value' => $project->id]]]) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 ml-auto"
                >
                    View All Orders &rarr;
                </a>
            @endif
        </div>
    </div>
</div>
