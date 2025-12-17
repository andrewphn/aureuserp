<x-filament-panels::page>
    {{-- Summary Cards --}}
    @php
        $summary = $this->getSummaryTotals();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $summary['total_hours'] }}</div>
                <div class="text-sm text-gray-500">Total Hours</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $summary['total_projects'] }}</div>
                <div class="text-sm text-gray-500">Projects</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $summary['total_employees'] }}</div>
                <div class="text-sm text-gray-500">Employees</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-success-600">${{ $summary['total_cost'] }}</div>
                <div class="text-sm text-gray-500">Total Labor Cost</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Filters --}}
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            Filters
        </x-slot>

        <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="startDate"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="endDate"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Project</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="selectedProjectId">
                        <option value="">All Projects</option>
                        @foreach(\Webkul\Project\Models\Project::pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </form>
    </x-filament::section>

    {{-- Main Table --}}
    <x-filament::section>
        <x-slot name="heading">
            Project Hours Summary
        </x-slot>
        <x-slot name="description">
            Click on a project row to see employee and task breakdown
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    {{-- Drill-down Section --}}
    @if($this->expandedProjectId)
        @php
            $employeeBreakdown = $this->getEmployeeBreakdown();
            $taskBreakdown = $this->getTaskBreakdown();
            $project = \Webkul\Project\Models\Project::find($this->expandedProjectId);
        @endphp

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Employee Breakdown --}}
            <x-filament::section>
                <x-slot name="heading">
                    Employee Hours - {{ $project?->name }}
                </x-slot>

                @if(count($employeeBreakdown) > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 font-medium">Employee</th>
                                <th class="text-right py-2 font-medium">Hours</th>
                                <th class="text-right py-2 font-medium">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employeeBreakdown as $item)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2">{{ $item['employee'] }}</td>
                                    <td class="text-right py-2">{{ $item['hours'] }}</td>
                                    <td class="text-right py-2 text-success-600">${{ $item['cost'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500 text-center py-4">No employee data available</p>
                @endif
            </x-filament::section>

            {{-- Task Breakdown --}}
            <x-filament::section>
                <x-slot name="heading">
                    Task Hours - {{ $project?->name }}
                </x-slot>

                @if(count($taskBreakdown) > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 font-medium">Task</th>
                                <th class="text-right py-2 font-medium">Hours</th>
                                <th class="text-right py-2 font-medium">Allocated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($taskBreakdown as $item)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2">{{ $item['task'] }}</td>
                                    <td class="text-right py-2">{{ $item['hours'] }}</td>
                                    <td class="text-right py-2 text-gray-500">{{ $item['allocated'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500 text-center py-4">No task-specific entries</p>
                @endif
            </x-filament::section>
        </div>

        {{-- Close drill-down button --}}
        <div class="mt-4 flex justify-center">
            <x-filament::button
                wire:click="toggleExpand({{ $this->expandedProjectId }})"
                color="gray"
                size="sm"
            >
                Close Details
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
