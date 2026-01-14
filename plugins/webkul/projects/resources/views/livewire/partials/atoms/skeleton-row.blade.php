{{-- Skeleton Loading Row for Cabinet Table --}}
{{-- Displays animated placeholder content while loading --}}

@props([
    'columns' => 7,
    'animate' => true
])

<tr 
    class="border-b border-gray-200 dark:border-gray-700"
    role="row"
    aria-busy="true"
    aria-label="Loading cabinet data..."
>
    {{-- Name skeleton --}}
    <td class="px-3 py-3">
        <div 
            class="h-4 rounded w-24 {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- Width skeleton --}}
    <td class="px-3 py-3 text-center">
        <div 
            class="h-4 rounded w-12 mx-auto {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- Height skeleton --}}
    <td class="px-3 py-3 text-center">
        <div 
            class="h-4 rounded w-12 mx-auto {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- Depth skeleton --}}
    <td class="px-3 py-3 text-center">
        <div 
            class="h-4 rounded w-12 mx-auto {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- Qty skeleton --}}
    <td class="px-3 py-3 text-center">
        <div 
            class="h-4 rounded w-8 mx-auto {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- LF skeleton --}}
    <td class="px-3 py-3 text-right">
        <div 
            class="h-4 rounded w-12 ml-auto {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
            aria-hidden="true"
        ></div>
    </td>
    
    {{-- Actions skeleton --}}
    <td class="px-3 py-3">
        <div class="flex items-center justify-end gap-1">
            <div 
                class="h-8 w-8 rounded {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
                aria-hidden="true"
            ></div>
            <div 
                class="h-8 w-8 rounded {{ $animate ? 'animate-pulse' : '' }} bg-gray-200 dark:bg-gray-700"
                aria-hidden="true"
            ></div>
        </div>
    </td>
</tr>
