{{--
    Cabinet Spec Tree Content View

    This view is used by CabinetSpecTreeRelationManager to display the
    hierarchical cabinet spec builder within the Edit Project page.

    Variables:
    - $project: The Project model instance
    - $specData: Array of spec data loaded from database relations
--}}

<div class="p-4">
    {{-- Embed the CabinetSpecBuilder Livewire component --}}
    @livewire('cabinet-spec-builder', ['specData' => $specData ?? []], key('cabinet-spec-tree-' . ($project->id ?? 'new')))

    {{-- Embed the AI Assistant floating widget --}}
    @livewire('cabinet-ai-assistant', [
        'projectId' => $project->id ?? 0,
        'specData' => $specData ?? [],
    ], key('cabinet-ai-assistant-' . ($project->id ?? 'new')))

    {{-- Info banner about data sync --}}
    @if(!empty($specData))
        <div class="mt-5 p-3.5 bg-blue-50/80 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700/50">
            <div class="flex items-start gap-2.5">
                <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
                <div class="text-sm">
                    <p class="font-medium text-blue-800 dark:text-blue-200">Changes sync automatically</p>
                    <p class="text-blue-600 dark:text-blue-300/80 mt-0.5">
                        Cabinet specs update the database in real-time. Use the tree view to add, edit, or remove items.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
