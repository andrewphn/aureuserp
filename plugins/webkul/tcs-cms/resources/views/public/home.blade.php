@extends('tcs-cms::layouts.public')

@section('title', 'TCS Woodwork - Custom Cabinetry & Fine Woodworking')

@section('content')
    @foreach($homeSections as $section)
        @php
            $sectionType = $section->section_type ?? 'custom';
            $viewName = 'tcs-cms::home-sections.' . str_replace('_', '-', $sectionType);
        @endphp

        @if(View::exists($viewName))
            @include($viewName, ['section' => $section])
        @endif
    @endforeach
@endsection
