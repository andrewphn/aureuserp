@php
    $isUser = $message['role'] === 'user';
    $isSystem = $message['role'] === 'system';
    $isError = $message['isError'] ?? false;
    $hasCommands = !empty($message['commands'] ?? []);
    $hasImage = !empty($message['image'] ?? null);
@endphp

<div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }} items-start gap-2">
    {{-- Avatar (assistant/system only) --}}
    @if(!$isUser)
        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $isError ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gradient-to-br from-purple-500 to-indigo-600' }}">
            @if($isSystem)
                <x-heroicon-s-information-circle class="w-4 h-4 {{ $isError ? 'text-red-500' : 'text-white' }}" />
            @else
                <x-heroicon-s-sparkles class="w-4 h-4 text-white" />
            @endif
        </div>
    @endif

    {{-- Message Content --}}
    <div class="max-w-[85%] {{ $isUser ? 'order-first' : '' }}">
        {{-- Image preview (for user messages with images) --}}
        @if($hasImage)
            <div class="mb-2">
                <img src="{{ $message['image'] }}" alt="Uploaded image" class="max-h-32 rounded-lg border border-gray-200 dark:border-gray-700" />
            </div>
        @endif

        {{-- Message bubble --}}
        <div class="
            px-4 py-2.5 rounded-2xl text-sm
            {{ $isUser
                ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-tr-sm'
                : ($isError
                    ? 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 rounded-tl-sm'
                    : ($isSystem
                        ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800 rounded-tl-sm'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-tl-sm'))
            }}
        ">
            {{-- Render markdown-like content --}}
            <div class="prose prose-sm dark:prose-invert max-w-none
                {{ $isUser ? '[&_*]:text-white' : '' }}
                [&_p]:my-1 [&_ul]:my-1 [&_li]:my-0.5
                [&_code]:px-1 [&_code]:py-0.5 [&_code]:bg-black/10 [&_code]:dark:bg-white/10 [&_code]:rounded [&_code]:text-xs
                [&_pre]:my-2 [&_pre]:p-2 [&_pre]:bg-black/10 [&_pre]:dark:bg-white/10 [&_pre]:rounded-lg [&_pre]:overflow-x-auto
                [&_strong]:font-semibold
            ">
                {!! \Illuminate\Support\Str::markdown($message['content'] ?? '', [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]) !!}
            </div>
        </div>

        {{-- Command execution indicator --}}
        @if($hasCommands)
            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                <span>{{ count($message['commands']) }} action{{ count($message['commands']) !== 1 ? 's' : '' }} executed</span>
            </div>
        @endif

        {{-- Timestamp --}}
        <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500 {{ $isUser ? 'text-right' : 'text-left' }}">
            {{ \Carbon\Carbon::parse($message['timestamp'])->format('g:i A') }}
        </div>
    </div>

    {{-- User avatar --}}
    @if($isUser)
        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
            <x-heroicon-s-user class="w-4 h-4 text-gray-500 dark:text-gray-400" />
        </div>
    @endif
</div>
