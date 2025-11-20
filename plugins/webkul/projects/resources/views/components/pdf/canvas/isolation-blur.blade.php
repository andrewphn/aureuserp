{{-- Isolation Mode Blur - Positioned exactly like annotation overlay --}}
<div
    x-show="isolationMode"
    x-cloak
    x-ref="isolationBlur"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute top-0 left-0 pointer-events-none"
    style="z-index: 1; display: none;"
    :style="`width: ${overlayWidth}; height: ${overlayHeight}; display: ${isolationMode ? 'block' : 'none'};`"
>
    <!-- SVG for blur with proper masking -->
    <!-- Only render viewBox when we have pixel dimensions (not '100%') -->
    <svg
        x-show="overlayWidth.includes('px')"
        xmlns="http://www.w3.org/2000/svg"
        preserveAspectRatio="none"
        style="display: block; width: 100%; height: 100%;"
        :viewBox="`0 0 ${overlayWidth.replace('px', '')} ${overlayHeight.replace('px', '')}`"
    >
        <defs>
            <!-- Blur filter for background -->
            <filter id="blur">
                <feGaussianBlur in="SourceGraphic" stdDeviation="4"/>
            </filter>

            <!-- Feather filter for soft mask edges -->
            <filter id="feather">
                <feGaussianBlur in="SourceGraphic" stdDeviation="15"/>
            </filter>

            <!-- Mask: white = show blur, black = hide blur -->
            <mask id="blurMask">
                <!-- White everywhere = show blur everywhere -->
                <!-- Dynamically sized to match canvas -->
                <rect
                    x="0"
                    y="0"
                    :width="overlayWidth.replace('px', '')"
                    :height="overlayHeight.replace('px', '')"
                    fill="white"
                />

                <!-- Black rectangle at selected annotation and its visible children = hide blur there -->
                <!-- This excludes the focused area from the darkening blur in isolation mode -->
                <g id="maskRects"></g>
            </mask>
        </defs>

        <!-- Dark overlay with blur, masked to exclude annotation -->
        <!-- Dynamically sized to match canvas -->
        <rect
            x="0"
            y="0"
            :width="overlayWidth.replace('px', '')"
            :height="overlayHeight.replace('px', '')"
            fill="rgba(0, 0, 0, 0.65)"
            filter="url(#blur)"
            mask="url(#blurMask)"
        />
    </svg>
</div>
