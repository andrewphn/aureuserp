{{-- Production Progress Widget --}}
<div class="space-y-2">
    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Production Progress</h4>

    <div class="space-y-3">
        {{-- CNC Progress --}}
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-600 dark:text-gray-400">CNC</span>
                <span class="font-medium">{{ $data['cnc_progress'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div
                    class="bg-blue-500 h-1.5 rounded-full"
                    style="width: {{ $data['cnc_progress'] ?? 0 }}%"
                ></div>
            </div>
        </div>

        {{-- Assembly Progress --}}
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-600 dark:text-gray-400">Assembly</span>
                <span class="font-medium">{{ $data['assembly_progress'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div
                    class="bg-green-500 h-1.5 rounded-full"
                    style="width: {{ $data['assembly_progress'] ?? 0 }}%"
                ></div>
            </div>
        </div>

        {{-- Finishing Progress --}}
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-600 dark:text-gray-400">Finishing</span>
                <span class="font-medium">{{ $data['finishing_progress'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div
                    class="bg-purple-500 h-1.5 rounded-full"
                    style="width: {{ $data['finishing_progress'] ?? 0 }}%"
                ></div>
            </div>
        </div>
    </div>
</div>
