@props(['section'])

@php
    $settings = $section->settings ?? [];
    $bgColor = $settings['background_color'] ?? 'bg-white';
    $testimonialItems = $section->testimonial_items ?? [];
@endphp

<section class="section-padding {{ $bgColor }}">
    <div class="container-tcs">
        <div class="content-standard text-center mb-20">
            <h2 class="section-subtitle mb-8">
                {{ $section->title ?? 'Client Testimonials' }}
            </h2>
            @if($section->subtitle)
                <h3 class="section-intro mb-6">
                    {{ $section->subtitle }}
                </h3>
            @endif
            @if($section->content)
                <p class="content-secondary">
                    {!! $section->content !!}
                </p>
            @endif
        </div>

        @if(count($testimonialItems) > 0)
            <div class="grid grid-cols-1 gap-16 md:grid-cols-3">
                @foreach($testimonialItems as $testimonial)
                    <div class="text-center">
                        @if(isset($testimonial['images']) && count($testimonial['images']) > 0)
                            <div class="flex justify-center mb-6 space-x-2">
                                @foreach(array_slice($testimonial['images'], 0, 5) as $image)
                                    <img src="{{ Storage::url($image) }}" alt="Project image" class="w-8 h-8 rounded-full object-cover">
                                @endforeach
                            </div>
                        @else
                            <div class="flex justify-center mb-6 space-x-2">
                                @for($i = 0; $i < 5; $i++)
                                    <div class="w-8 h-8 rounded-full bg-brand-metallic/20"></div>
                                @endfor
                            </div>
                        @endif
                        <blockquote class="mb-6 content-primary italic">
                            "{{ $testimonial['quote'] ?? '' }}"
                        </blockquote>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $testimonial['author'] ?? '' }}</p>
                            @if(isset($testimonial['position']))
                                <p class="content-tertiary">{{ $testimonial['position'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($section->cta_text)
            <div class="mt-20 text-center">
                <a href="{{ $section->cta_link ?? '/contact' }}" class="btn-primary">
                    {{ $section->cta_text }}
                </a>
            </div>
        @endif
    </div>
</section>
