<div class="p-6">
    {{-- Image Display --}}
    <div class="flex justify-center mb-6">
        <img src="{{ $imageUrl }}"
             alt="{{ $record->file_name }}"
             class="max-w-full h-auto rounded-lg shadow-lg"
             style="max-height: 70vh;">
    </div>

    {{-- Image Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Left Column --}}
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">File Information</h3>
                <dl class="mt-2 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">File Name:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->file_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Dimensions:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->dimensions ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">File Size:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->formatted_file_size }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Type:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->mime_type }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Upload Details</h3>
                <dl class="mt-2 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Uploaded By:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->uploader->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Uploaded:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->created_at->format('M d, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>

            @if($record->description)
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</h3>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $record->description }}</p>
            </div>
            @endif

            @if($record->tags)
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($record->tags as $tag)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Future: Nutrient Annotation Integration --}}
    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Nutrient Annotations (Coming Soon)</h4>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Image annotation capabilities with Nutrient will be integrated here, allowing you to add notes, highlights, and markup directly on images.
                </p>
            </div>
        </div>
    </div>
</div>
