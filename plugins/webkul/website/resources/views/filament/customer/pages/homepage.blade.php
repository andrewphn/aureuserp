<div class="tcs-homepage">
    {{-- Render TCS CMS Home Sections --}}
    @if($this->hasHomeSections())
        @include('tcs-cms::components.sections-renderer', ['sections' => $this->getHomeSections()])
    @endif

    {{-- Render basic content if no sections --}}
    @if(!$this->hasHomeSections() && $this->getContent())
        <div class="prose max-w-none">
            {!! $this->getContent() !!}
        </div>
    @endif

    {{-- Render TCS CMS page blocks --}}
    @if($this->hasBlocks())
        @include('tcs-cms::components.blocks-renderer', ['blocks' => $this->getBlocks()])
    @endif
</div>
