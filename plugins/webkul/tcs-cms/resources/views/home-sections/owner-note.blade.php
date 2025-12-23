@props(['section'])

@php
    $settings = $section->settings ?? [];
    $authorInfo = $section->author_info ?? [];
@endphp

<!-- Owner Note Section - Matches Original TCS -->
<section id="owner-note" class="py-16 md:py-20 bg-neutral-900 text-white" data-section-name="Craftsman's Note">
    <div class="container-tcs text-center max-w-4xl mx-auto px-6">
        <h2 class="text-sm tracking-wider uppercase mb-10 text-white/80">{{ $section->title ?? 'From the Master Craftsman' }}</h2>

        <div class="mx-auto max-w-3xl">
            @if(isset($authorInfo['message']) && !empty($authorInfo['message']))
                <p class="mb-8 font-light text-lg md:text-xl leading-relaxed text-white/90">
                    "{{ $authorInfo['message'] }}"
                </p>
            @else
                <p class="mb-8 font-light text-lg md:text-xl leading-relaxed text-white/90">
                    Wood tells a story. It carries the memory of time—each grain a record of growth, endurance, and change. At TCS Woodworking, we honor that story by shaping wood into pieces that are not only functional but deeply meaningful.
                </p>
            @endif

            @if(isset($authorInfo['closing']) && !empty($authorInfo['closing']))
                <p class="mb-10 font-light text-lg md:text-xl leading-relaxed text-white/90">
                    "{{ $authorInfo['closing'] }}"
                </p>
            @else
                <p class="mb-10 font-light text-lg md:text-xl leading-relaxed text-white/90">
                    We appreciate the opportunity to bring your vision to life. Let's build something timeless together.
                </p>
            @endif
        </div>

        <div class="flex flex-col items-center mt-8">
            <div class="h-16 mb-2">
                @if(isset($authorInfo['signature']) && !empty($authorInfo['signature']))
                    <img src="{{ Storage::url($authorInfo['signature']) }}" alt="{{ $authorInfo['name'] ?? 'Bryan Patton' }} Signature" class="h-full w-auto">
                @else
                    <img src="/images/signature_white.svg" alt="Bryan Patton Signature" class="h-full w-auto">
                @endif
            </div>
            <p class="text-sm font-light text-white">{{ $authorInfo['name'] ?? 'Bryan Patton' }}</p>
            <p class="text-xs text-neutral-400">{{ $authorInfo['title'] ?? 'Lead Design Craftsman & Master Woodwright' }}</p>
        </div>
    </div>
</section>
