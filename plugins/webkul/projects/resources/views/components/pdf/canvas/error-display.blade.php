{{-- Error Display --}}
<div
    x-show="systemReady && error"
    class="absolute inset-0 bg-white dark:bg-gray-900 z-40 flex items-center justify-center p-8"
>
    <div class="max-w-md w-full space-y-6 text-center">
        <!-- Error Icon -->
        <div class="flex justify-center">
            <svg class="h-16 w-16 text-danger-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>

        <!-- Error Message -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Failed to Load PDF Viewer</h3>
            <p class="text-sm text-danger-600 dark:text-danger-400" x-text="error"></p>
        </div>

        <!-- Help Text -->
        <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
            <p>This error may occur if:</p>
            <ul class="list-disc list-inside text-left space-y-1">
                <li>The PDF file is missing or corrupted</li>
                <li>Your browser blocked the PDF due to security settings</li>
                <li>There's a network connectivity issue</li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-center">
            <button
                @click="window.location.reload()"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
            >
                Reload Page
            </button>
            <a
                href="{{ url()->previous() }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors"
            >
                Go Back
            </a>
        </div>
    </div>
</div>
