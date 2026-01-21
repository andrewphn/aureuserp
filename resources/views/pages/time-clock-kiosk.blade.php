<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Time Clock - TCS Woodwork</title>

    {{-- Favicon and App Icons --}}
    <link rel="icon" type="image/png" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('tcs_logo.png') }}">
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('tcs_logo.png') }}">
    <meta name="apple-mobile-web-app-title" content="TCS Time Clock">

    {{-- Prevent zoom on mobile for kiosk mode --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#111827">

    {{-- Standalone Kiosk CSS (bypasses Tailwind v4 build issues) --}}
    <link rel="stylesheet" href="/css/time-clock-kiosk.css">

    {{-- Livewire Styles --}}
    @livewireStyles
</head>
<body class="antialiased">
    {{-- Time Clock Kiosk Component --}}
    @livewire('time-clock-kiosk')

    {{-- Livewire Scripts --}}
    @livewireScripts

    {{-- Vite JS --}}
    @vite(['resources/js/app.js'])
</body>
</html>
