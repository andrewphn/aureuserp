@props(['section'])

@php
    $settings = $section->settings ?? [];
    $bgColor = $settings['background_color'] ?? 'bg-neutral-50';
    $serviceItems = $section->service_items ?? [];
@endphp

<section id="services" class="section-padding-large {{ $bgColor }}">
    <div class="container-tcs">
        <h2 class="section-subtitle text-center mb-4">
            {{ $section->title ?? 'Our Services' }}
        </h2>

        <div class="content-standard text-center mb-20">
            <h3 class="section-intro mb-6">
                {{ $section->subtitle ?? 'Luxury Custom Woodworking for Distinctive Spaces' }}
            </h3>
            <p class="content-secondary">
                {!! $section->content ?? '' !!}
            </p>
        </div>

        @if(count($serviceItems) > 0)
            <div class="space-y-24 lg:space-y-32">
                @foreach($serviceItems as $index => $service)
                    <div class="service-card grid items-center grid-cols-1 md:grid-cols-2 gap-8 lg:gap-16">
                        <div class="@if(($service['imagePosition'] ?? 'right') === 'left') order-2 md:order-2 @else order-2 md:order-1 @endif">
                            <span class="block mb-3 section-subtitle text-brand-metallic">
                                {{ $service['sequence'] ?? sprintf('%02d', $index + 1) }}
                            </span>
                            <h4 class="mb-4 font-serif text-2xl font-normal leading-tight md:text-3xl lg:text-4xl">
                                {{ $service['title'] ?? '' }}
                            </h4>
                            @if(isset($service['subtitle']))
                                <p class="mb-4 font-medium text-lg text-neutral-700">
                                    {{ $service['subtitle'] }}
                                </p>
                            @endif
                            <p class="mb-6 content-secondary">
                                {{ $service['description'] ?? '' }}
                            </p>
                            @if(isset($service['features']) && count($service['features']) > 0)
                                <ul class="mb-8 space-y-2 content-secondary">
                                    @foreach($service['features'] as $feature)
                                        <li class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-brand-metallic" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(isset($service['linkUrl']) && isset($service['linkText']))
                                <a href="{{ $service['linkUrl'] }}" class="btn-text">
                                    {{ $service['linkText'] }}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-2">
                                        <path d="M5 12h14"></path>
                                        <path d="M12 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <div class="relative @if(($service['imagePosition'] ?? 'right') === 'left') order-1 md:order-1 @else order-1 md:order-2 @endif aspect-[4/3] md:aspect-[3/2] lg:aspect-[4/3] overflow-hidden rounded-lg">
                            <img
                                src="{{ $service['imageUrl'] ?? '/images/projects/kitchen-cabinetry.png' }}"
                                alt="{{ $service['imageAlt'] ?? $service['title'] ?? '' }}"
                                class="object-cover w-full h-full transition-transform duration-700 hover:scale-105"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($section->cta_text)
            <div class="mt-24 text-center">
                <a href="{{ $section->cta_link ?? '/services' }}" class="btn-secondary">
                    {{ $section->cta_text }}
                </a>
            </div>
        @endif
    </div>
</section>
