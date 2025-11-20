{{-- Skeleton Loading Overlay --}}
<div
    x-show="!systemReady"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center"
    @touchmove.prevent
    @wheel.prevent
>
    <div class="w-full h-full flex flex-col">
        <!-- Skeleton Header Bar -->
        <div class="h-16 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 animate-pulse"></div>

        <!-- Skeleton Content Area -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="max-w-md w-full space-y-6">
                <!-- Loading Icon -->
                <div class="flex justify-center">
                    <svg class="animate-spin h-16 w-16 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Loading Text -->
                <div class="text-center space-y-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Loading PDF Viewer</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Preparing your document and annotations...</p>
                </div>

                <!-- Skeleton PDF Preview -->
                <div class="space-y-3">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-4/6"></div>
                    <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mt-4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/6"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                </div>
            </div>
        </div>
    </div>
</div>
