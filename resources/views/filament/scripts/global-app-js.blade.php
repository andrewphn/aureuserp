{{-- Global app.js loader - ensures entity store is available on all FilamentPHP pages --}}
@once
    @vite('resources/js/app.js')
    @vite('resources/js/login-debug.js')
@endonce
