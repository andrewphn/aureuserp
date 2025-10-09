<div class="space-y-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
        <p class="text-sm text-blue-900 dark:text-blue-300">
            <strong>Version History:</strong> {{ $versions->count() }} version(s) total
        </p>
    </div>

    <div class="space-y-3">
        @foreach($versions->reverse() as $version)
            <div class="border-2 {{ $version->id === $currentVersion->id ? 'border-orange-400 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-300 bg-white dark:bg-gray-800' }} rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $version->is_latest_version ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                Version {{ $version->version_number }}
                            </span>

                            @if($version->is_latest_version)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    Latest
                                </span>
                            @endif

                            @if($version->id === $currentVersion->id)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                    Current View
                                </span>
                            @endif
                        </div>

                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <p><strong>File:</strong> {{ $version->file_name }}</p>
                            <p><strong>Pages:</strong> {{ $version->page_count }}</p>
                            <p><strong>Size:</strong> {{ $version->formatted_file_size }}</p>
                            <p><strong>Uploaded:</strong> {{ $version->created_at->format('M j, Y g:i A') }} by {{ $version->uploader->name }}</p>

                            @if($version->version_metadata && isset($version->version_metadata['version_notes']))
                                <div class="mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded">
                                    <strong class="text-xs">Notes:</strong>
                                    <p class="text-sm mt-1">{{ $version->version_metadata['version_notes'] }}</p>
                                </div>
                            @endif

                            @if($version->version_metadata && ($version->version_metadata['migrate_annotations'] ?? false))
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    ✅ Annotations migrated from Version {{ $version->version_number - 1 }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 ml-4">
                        @if($version->id !== $currentVersion->id)
                            <a href="{{ route('filament.admin.resources.project.projects.pdf-review', ['record' => $version->module_id, 'pdf' => $version->id]) }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                View This Version
                            </a>
                        @endif

                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($version->file_path) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg">
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($versions->count() > 1)
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Version Chain</h4>
            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                @foreach($versions as $v)
                    <span class="px-2 py-1 rounded {{ $v->is_latest_version ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 font-semibold' : 'bg-gray-200 dark:bg-gray-700' }}">
                        v{{ $v->version_number }}
                    </span>
                    @if(!$loop->last)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
