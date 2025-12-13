<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Time Clock - TCS Woodwork</title>

    {{-- Prevent zoom on mobile for kiosk mode --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#111827">

    {{-- Vite CSS --}}
    @vite(['resources/css/app.css'])

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
