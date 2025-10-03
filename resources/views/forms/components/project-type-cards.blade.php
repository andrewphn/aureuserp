<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || [],
        }"
        class="grid grid-cols-2 gap-3"
    >
        @foreach($getOptions() as $value => $label)
            <button
                type="button"
                @click="
                    if (state.includes('{{ $value }}')) {
                        state = state.filter(v => v !== '{{ $value }}')
                    } else {
                        state = [...state, '{{ $value }}']
                    }
                "
                :class="{
                    'bg-primary-600 border-primary-600 dark:bg-primary-500 dark:border-primary-500': state.includes('{{ $value }}'),
                    'bg-white border-gray-300 hover:border-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:hover:border-gray-500': !state.includes('{{ $value }}')
                }"
                class="relative flex items-center justify-center px-4 py-6 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <span
                    :class="{
                        'text-white font-semibold': state.includes('{{ $value }}'),
                        'text-gray-700 font-medium dark:text-gray-300': !state.includes('{{ $value }}')
                    }"
                    class="text-sm"
                >
                    {{ $label }}
                </span>

                <!-- Checkmark -->
                <svg
                    x-show="state.includes('{{ $value }}')"
                    class="absolute top-2 right-2 w-5 h-5 text-white"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </button>
        @endforeach
    </div>
</x-dynamic-component>
