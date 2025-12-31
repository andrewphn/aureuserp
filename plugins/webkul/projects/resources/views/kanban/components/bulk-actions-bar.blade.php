{{-- Bulk Actions Floating Bar - Shows when multiple cards selected --}}
<div
    x-show="selectedCards.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9999; background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 2px solid #3b82f6; padding: 12px 20px; display: none;"
    :style="selectedCards.length > 0 ? 'display: flex !important;' : 'display: none !important;'"
>
    <div style="display: flex; align-items: center; gap: 16px;">
        {{-- Selection Count - Large and Prominent --}}
        <div style="display: flex; align-items: center; gap: 8px; padding-right: 16px; border-right: 1px solid #e5e7eb;">
            <span
                style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 12px; border-radius: 9999px; background: #3b82f6; color: white; font-size: 14px; font-weight: 700;"
                x-text="selectedCards.length"
            ></span>
            <span style="font-size: 14px; font-weight: 500; color: #374151;">selected</span>
        </div>

        {{-- Move to Stage Dropdown --}}
        <div x-data="{ stageMenuOpen: false }" style="position: relative;">
            <button
                @click="stageMenuOpen = !stageMenuOpen"
                type="button"
                style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #374151; background: transparent; border: none; cursor: pointer; border-radius: 8px;"
                onmouseover="this.style.background='#f3f4f6'"
                onmouseout="this.style.background='transparent'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#3b82f6" style="width: 16px; height: 16px;">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM6.75 9.25a.75.75 0 0 0 0 1.5h4.59l-2.1 1.95a.75.75 0 0 0 1.02 1.1l3.5-3.25a.75.75 0 0 0 0-1.1l-3.5-3.25a.75.75 0 1 0-1.02 1.1l2.1 1.95H6.75Z" clip-rule="evenodd" />
                </svg>
                Move to Stage
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 12px; height: 12px;">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>

            {{-- Stage Dropdown --}}
            <div
                x-show="stageMenuOpen"
                @click.away="stageMenuOpen = false"
                x-cloak
                x-transition
                style="position: absolute; bottom: 100%; left: 0; margin-bottom: 8px; background: white; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; padding: 4px 0; min-width: 180px; z-index: 10000;"
            >
                @foreach(\Webkul\Project\Models\ProjectStage::where('is_active', true)->orderBy('sort')->get() as $stage)
                    <button
                        @click="stageMenuOpen = false; bulkChangeStage({{ $stage->id }})"
                        type="button"
                        style="width: 100%; padding: 8px 16px; text-align: left; display: flex; align-items: center; gap: 8px; font-size: 14px; color: #374151; background: transparent; border: none; cursor: pointer;"
                        onmouseover="this.style.background='#f3f4f6'"
                        onmouseout="this.style.background='transparent'"
                    >
                        <span style="width: 8px; height: 8px; border-radius: 9999px; background: {{ $stage->color ?? '#6b7280' }};"></span>
                        {{ $stage->name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Mark Blocked --}}
        <button
            @click="bulkMarkBlocked()"
            type="button"
            style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #9333ea; background: transparent; border: none; cursor: pointer; border-radius: 8px;"
            onmouseover="this.style.background='#faf5ff'"
            onmouseout="this.style.background='transparent'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                <path fill-rule="evenodd" d="M5.965 4.904l9.131 9.131a6.5 6.5 0 0 0-9.131-9.131Zm8.07 10.192L4.904 5.965a6.5 6.5 0 0 0 9.131 9.131ZM4.343 4.343a8 8 0 1 1 11.314 11.314A8 8 0 0 1 4.343 4.343Z" clip-rule="evenodd" />
            </svg>
            Block
        </button>

        {{-- Unblock --}}
        <button
            @click="bulkUnblock()"
            type="button"
            style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #16a34a; background: transparent; border: none; cursor: pointer; border-radius: 8px;"
            onmouseover="this.style.background='#f0fdf4'"
            onmouseout="this.style.background='transparent'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
            </svg>
            Unblock
        </button>

        {{-- Divider --}}
        <div style="width: 1px; height: 24px; background: #e5e7eb;"></div>

        {{-- Clear Selection --}}
        <button
            @click="clearSelection()"
            type="button"
            style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #6b7280; background: transparent; border: none; cursor: pointer; border-radius: 8px;"
            onmouseover="this.style.background='#f3f4f6'"
            onmouseout="this.style.background='transparent'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
            Clear
        </button>
    </div>
</div>
