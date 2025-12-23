@props(['section'])

@php
    // Get a featured project to display
    $featuredProject = \Webkul\TcsCms\Models\PortfolioProject::where('featured', true)
        ->where('is_published', true)
        ->orderBy('portfolio_order')
        ->first();
@endphp

<section id="hero" class="relative min-h-screen overflow-hidden pt-0">
    <!-- Background Layer -->
    <div class="absolute inset-0 z-0">
        @php
            $backgroundImage = $section->background_image ?? '/images/hero.jpg';
            $isVideo = str_contains($backgroundImage, '.mov') || str_contains($backgroundImage, '.mp4');
        @endphp

        @if($isVideo)
            <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('/images/frames/dining-table-first-frame.jpg');"></div>
            <video
                class="absolute inset-0 object-cover w-full h-full transition-opacity duration-700 opacity-0"
                autoplay
                muted
                loop
                playsinline
                preload="auto"
                poster="/images/frames/dining-table-first-frame.jpg"
                onloadeddata="this.classList.remove('opacity-0')"
            >
                <source src="{{ $backgroundImage }}" type="video/mp4">
            </video>
        @else
            <img
                src="{{ $backgroundImage }}"
                alt="TCS Woodworking craftsmanship"
                class="absolute inset-0 object-cover w-full h-full"
            >
        @endif
    </div>

    <!-- Warm Sepia/Golden Overlay - Matches Original TCS -->
    <div class="absolute inset-0 z-10" style="background: linear-gradient(to bottom, rgba(139, 90, 43, 0.4) 0%, rgba(101, 67, 33, 0.5) 100%);"></div>

    <!-- Featured Project Caption - Exact Original TCS Style -->
    @if($featuredProject)
    <div class="catalog-caption-container absolute z-40 opacity-0 animate-slide-in">
        <a href="/work/{{ $featuredProject->slug }}" class="block group">
            <div class="transition-all duration-500 border-t border-b border-r rounded-r opacity-70 catalog-caption backdrop-blur-sm hover:backdrop-blur-md border-white/20 hover:border-white/30 hover:opacity-100 bg-gradient-to-r from-black/30 to-black/20">
                <div class="flex flex-col">
                    <span class="mb-1 uppercase transition-colors duration-500 text-white/70 group-hover:text-white/90 caption-subtitle">Project</span>
                    <p class="font-light leading-relaxed tracking-wide transition-colors duration-500 text-white/80 group-hover:text-white caption-title">
                        Featured Project: {{ $featuredProject->title }}
                    </p>
                    <div class="flex items-center mt-1.5">
                        <div class="h-[1px] bg-white/30 group-hover:bg-white/50 transition-colors duration-500 caption-line"></div>
                        <span class="tracking-widest uppercase transition-all duration-500 text-white/60 group-hover:text-white/90 caption-view">View</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif

    <!-- Text Content -->
    <div class="absolute inset-0 z-20 flex items-center justify-center">
        <div class="w-full px-4 sm:px-6 md:px-8">
            <div class="container mx-auto text-center max-w-5xl">
                <h1 class="mb-6 font-serif font-normal leading-tight text-white text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl max-w-4xl mx-auto">
                    {!! $section->title !!}
                </h1>
                @if($section->subtitle)
                    <p class="max-w-2xl mx-auto mb-10 font-sans font-light leading-relaxed text-white/80 text-base sm:text-lg md:text-xl">
                        {{ $section->subtitle }}
                    </p>
                @endif
                @if($section->cta_text)
                    <div class="mt-8">
                        <a href="{{ $section->cta_link ?? '/contact' }}" class="btn-hero">
                            {{ $section->cta_text }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Scroll Indicator (Bottom Right) -->
    <div class="absolute bottom-8 right-8 z-20 hidden md:block">
        <img src="/images/scroll-indicator.svg" alt="Scroll" class="w-6 h-auto opacity-60 animate-bounce">
    </div>
</section>
