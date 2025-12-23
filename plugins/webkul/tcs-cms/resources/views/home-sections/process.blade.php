@props(['section'])

@php
    $settings = $section->settings ?? [];
    $bgColor = $settings['background_color'] ?? 'bg-white';
    $processSteps = $section->process_steps ?? [];
@endphp

<section class="section-padding {{ $bgColor }}">
    <div class="container-tcs">
        <div class="content-standard text-center mb-20">
            <h2 class="section-subtitle mb-4">
                {{ $section->title ?? 'Our Journey' }}
            </h2>
            @if($section->subtitle)
                <h3 class="section-intro">
                    {{ $section->subtitle }}
                </h3>
            @endif
        </div>

        @if(count($processSteps) > 0)
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 sm:gap-10 lg:gap-8">
                @foreach($processSteps as $step)
                    <div class="process-step text-center">
                        @if(isset($step['icon']))
                            <div class="mb-6 flex justify-center">
                                <img src="{{ Storage::url($step['icon']) }}" alt="{{ $step['title'] ?? '' }}" class="w-12 h-12 sm:w-16 sm:h-16 transition-transform duration-300 group-hover:scale-110">
                            </div>
                        @endif
                        <div class="mb-4 font-serif text-2xl sm:text-3xl text-brand-metallic transition-colors duration-300">
                            {{ $step['number'] ?? '' }}
                        </div>
                        <h4 class="mb-4 font-serif text-lg sm:text-xl font-medium leading-tight">
                            {{ $step['title'] ?? '' }}
                        </h4>
                        <p class="content-tertiary">
                            {{ $step['description'] ?? '' }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        @if($section->cta_text)
            <div class="mt-20 text-center">
                <a href="{{ $section->cta_link ?? '/process' }}" class="btn-text">
                    {{ $section->cta_text }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-2">
                        <path d="M5 12h14"></path>
                        <path d="M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>
