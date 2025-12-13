<div
    class="cabinet-ai-assistant"
    x-data="{
        isMinimized: @entangle('isMinimized'),
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    }"
    x-init="$watch('isMinimized', value => { if (!value) scrollToBottom(); })"
    @scroll-to-bottom.window="scrollToBottom()"
    wire:key="cabinet-ai-assistant-{{ $projectId }}"
>
    {{-- Minimized Bubble --}}
    <div
        x-show="isMinimized"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        class="fixed bottom-5 right-5 z-50"
        style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"
    >
        <button
            wire:click="expand"
            type="button"
            class="relative w-14 h-14 bg-gradient-to-br from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center group"
            style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(to bottom right, #a855f7, #4f46e5); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);"
        >
            <x-heroicon-s-sparkles class="w-7 h-7 group-hover:scale-110 transition-transform" />

            {{-- Unread badge --}}
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </button>
    </div>

    {{-- Expanded Widget --}}
    <div
        x-show="!isMinimized"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed bottom-5 right-5 z-50 w-[400px] max-w-[calc(100vw-40px)] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col"
        style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; width: 400px; max-width: calc(100vw - 40px); max-height: min(600px, calc(100vh - 100px)); background: white; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; overflow: hidden; display: flex; flex-direction: column;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
            <div class="flex items-center gap-2">
                <x-heroicon-s-sparkles class="w-5 h-5" />
                <span class="font-semibold">Cabinet AI Assistant</span>
            </div>

            <div class="flex items-center gap-1">
                {{-- Mode Toggle --}}
                <div class="flex items-center bg-white/20 rounded-lg p-0.5 mr-2">
                    <button
                        wire:click="setMode('quick')"
                        type="button"
                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors {{ $mode === 'quick' ? 'bg-white text-purple-600' : 'text-white/80 hover:text-white' }}"
                    >
                        Quick
                    </button>
                    <button
                        wire:click="setMode('guided')"
                        type="button"
                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors {{ $mode === 'guided' ? 'bg-white text-purple-600' : 'text-white/80 hover:text-white' }}"
                    >
                        Guided
                    </button>
                </div>

                {{-- Clear button --}}
                <button
                    wire:click="clearConversation"
                    wire:confirm="Clear all conversation history?"
                    type="button"
                    class="p-1.5 hover:bg-white/20 rounded-lg transition-colors"
                    title="Clear conversation"
                >
                    <x-heroicon-o-trash class="w-4 h-4" />
                </button>

                {{-- Minimize button --}}
                <button
                    wire:click="minimize"
                    type="button"
                    class="p-1.5 hover:bg-white/20 rounded-lg transition-colors"
                    title="Minimize"
                >
                    <x-heroicon-o-minus class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div
            x-ref="messagesContainer"
            class="flex-1 overflow-y-auto p-4 space-y-4 min-h-[200px]"
            style="max-height: 400px;"
        >
            @foreach($messages as $index => $message)
                @include('webkul-project::livewire.partials.ai-message-bubble', ['message' => $message])
            @endforeach

            {{-- Processing indicator --}}
            @if($isProcessing)
                <div class="flex items-start gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                        <x-heroicon-s-sparkles class="w-4 h-4 text-white" />
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-sm px-4 py-2">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms;"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms;"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms;"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Image Preview --}}
        @if($imagePreview)
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="relative inline-block">
                    <img src="{{ $imagePreview }}" alt="Upload preview" class="h-16 rounded-lg border border-gray-300 dark:border-gray-600" />
                    <button
                        wire:click="clearImage"
                        type="button"
                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                    >
                        <x-heroicon-m-x-mark class="w-3 h-3" />
                    </button>
                </div>
            </div>
        @endif

        {{-- Input Area --}}
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <form wire:submit="sendMessage" class="flex items-end gap-2">
                {{-- Image upload button --}}
                <label class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors flex-shrink-0">
                    <input
                        type="file"
                        wire:model="uploadedImage"
                        accept="image/*"
                        class="sr-only"
                    />
                    <x-heroicon-o-photo class="w-5 h-5" />
                </label>

                {{-- Text input --}}
                <div class="flex-1 relative">
                    <textarea
                        wire:model="inputMessage"
                        placeholder="{{ $mode === 'guided' ? 'Type your response...' : 'Add kitchen with L2 base cabinets...' }}"
                        rows="1"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm resize-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-shadow"
                        style="min-height: 42px; max-height: 120px;"
                        x-data
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); $el.style.height = '42px'; }"
                        @if($isProcessing) disabled @endif
                    ></textarea>
                </div>

                {{-- Send button --}}
                <button
                    type="submit"
                    class="p-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl transition-all flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($isProcessing) disabled @endif
                >
                    @if($isProcessing)
                        <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" />
                    @else
                        <x-heroicon-s-paper-airplane class="w-5 h-5" />
                    @endif
                </button>
            </form>

            {{-- Quick actions --}}
            @if(empty($specData) && empty($messages))
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button
                        wire:click="quickAddRoom('Kitchen')"
                        type="button"
                        class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-full transition-colors"
                    >
                        + Kitchen
                    </button>
                    <button
                        wire:click="quickAddRoom('Master Bath')"
                        type="button"
                        class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-full transition-colors"
                    >
                        + Master Bath
                    </button>
                    <button
                        wire:click="quickAddRoom('Laundry')"
                        type="button"
                        class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-full transition-colors"
                    >
                        + Laundry
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
