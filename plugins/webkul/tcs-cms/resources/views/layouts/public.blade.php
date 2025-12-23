<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TCS Woodwork - Custom Cabinetry & Fine Woodworking')</title>

    <!-- Fonts - Same as Original TCS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* =================================================================
           TCS Design System - Extracted from Original
           ================================================================= */

        :root {
            --color-metallic: #D4A574;
            --color-accent: #B8935E;
            --color-foreground: #1a1a1a;
            --color-background: #ffffff;
            --color-muted: #6b7280;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 1rem;
            line-height: 1.625;
            color: var(--color-foreground);
            background-color: var(--color-background);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-weight: 300;
        }

        .font-serif { font-family: 'Cormorant Garamond', Georgia, serif; }

        /* Luxury Typography - clamp() based */
        h1, h2, h3, h4, h5, h6 { font-family: 'Cormorant Garamond', Georgia, serif; }

        p {
            font-size: clamp(0.875rem, 1.5vw, 1rem);
            line-height: clamp(1.25rem, 2.25vw, 1.375rem);
            color: var(--color-muted);
            margin-bottom: clamp(1rem, 2vw, 1.25rem);
            max-width: 65ch;
            font-weight: 300;
        }

        /* =================================================================
           SECTION SPACING - Fluid Responsive (CORRECT)
           ================================================================= */

        .section-padding {
            padding-top: clamp(3rem, 8vw, 6rem);
            padding-bottom: clamp(3rem, 8vw, 6rem);
        }

        .section-padding-large {
            padding-top: clamp(4rem, 10vw, 8rem);
            padding-bottom: clamp(4rem, 10vw, 8rem);
        }

        /* =================================================================
           CONTAINER SYSTEM
           ================================================================= */

        .container-tcs {
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding-left: clamp(1rem, 4vw, 3rem);
            padding-right: clamp(1rem, 4vw, 3rem);
        }

        @media (max-width: 768px) {
            .container-tcs { padding-left: 1rem; padding-right: 1rem; }
        }

        .content-narrow { max-width: 48rem; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem; }
        .content-standard { max-width: 56rem; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem; }
        .content-wide { max-width: 64rem; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem; }

        /* =================================================================
           CONTENT HIERARCHY
           ================================================================= */

        .content-primary { color: var(--color-foreground); font-weight: 500; }
        .content-secondary { color: var(--color-muted); }
        .content-tertiary { color: #737373; font-size: 0.875rem; }

        .section-subtitle {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 300;
            font-size: clamp(1rem, 2vw, 1.125rem);
            line-height: clamp(1.25rem, 2.5vw, 1.375rem);
            margin-bottom: clamp(0.75rem, 1.5vw, 1rem);
            letter-spacing: 0.025em;
        }

        .section-intro {
            font-size: clamp(0.9375rem, 1.75vw, 1.0625rem);
            line-height: clamp(1.375rem, 2.5vw, 1.5rem);
            color: var(--color-foreground);
            margin-bottom: clamp(1.25rem, 2.5vw, 1.5rem);
            font-weight: 300;
            max-width: 48rem;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* =================================================================
           BRAND COLORS
           ================================================================= */

        .text-brand-metallic { color: var(--color-metallic); }
        .text-brand-accent { color: var(--color-accent); }

        /* =================================================================
           BUTTON SYSTEM
           ================================================================= */

        .btn-primary {
            display: inline-flex; align-items: center;
            transition: all 0.5s ease;
            background-color: var(--color-foreground);
            color: var(--color-background);
            border: 1px solid var(--color-foreground);
            padding: 0.5rem 1.75rem;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            font-weight: 400;
            text-transform: uppercase;
            text-decoration: none;
        }
        .btn-primary:hover { background-color: transparent; color: var(--color-foreground); transform: translateY(-1px); }

        .btn-secondary {
            display: inline-flex; align-items: center;
            transition: all 0.5s ease;
            background-color: transparent;
            color: var(--color-foreground);
            border: 1px solid #d4d4d4;
            padding: 0.5rem 1.75rem;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            font-weight: 400;
            text-transform: uppercase;
            text-decoration: none;
        }
        .btn-secondary:hover { border-color: var(--color-foreground); background-color: var(--color-foreground); color: var(--color-background); transform: translateY(-1px); }

        .btn-hero {
            display: inline-block;
            transition: all 0.5s ease;
            background-color: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 0.5rem 1.75rem;
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            font-weight: 300;
            text-transform: uppercase;
            text-decoration: none;
        }
        .btn-hero:hover { background-color: white; color: var(--color-foreground); border-color: white; transform: translateY(-1px); }

        /* =================================================================
           CARD COMPONENTS
           ================================================================= */

        .service-card {
            transition: all 0.3s ease-out;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .service-card:hover { transform: scale(1.02); }
        .service-card:hover .service-number { color: var(--color-metallic); }

        .service-number {
            display: block;
            font-size: 0.625rem;
            letter-spacing: 0.3em;
            opacity: 0.5;
            margin-bottom: 0.75rem;
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
            transition: color 0.3s ease;
        }

        .portfolio-card { transition: all 0.3s ease-out; }
        .portfolio-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); }
        .portfolio-card:hover .portfolio-arrow { transform: translateX(0.5rem); color: var(--color-metallic); }

        .portfolio-arrow { transition: all 0.3s ease; opacity: 0.4; }

        .process-step { transition: all 0.3s ease-out; }
        .process-step:hover { transform: translateY(-2px); }
        .process-step:hover .process-number { color: var(--color-accent); }

        .process-number {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.5rem;
            color: var(--color-metallic);
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }

        /* =================================================================
           NAVIGATION - Exact Original TCS
           ================================================================= */

        #main-nav {
            position: fixed !important;
            top: 0; left: 0; right: 0;
            z-index: 9999 !important;
            width: 100% !important;
            padding: 1.5rem 0;
            transition: all 0.3s ease-in-out;
            will-change: background-color, backdrop-filter;
            transform: translateZ(0);
            backface-visibility: hidden;
        }

        #main-nav.scrolled {
            background: rgba(0, 0, 0, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            padding: 0.75rem 0;
        }

        #main-nav.scrolled .tcs-menu-item { color: white; }
        #main-nav.scrolled .tcs-menu-item:hover { opacity: 0.8; }
        #main-nav.scrolled #logo img { height: 2.25rem !important; }
        #main-nav.scrolled .tcs-section-divider { background: rgba(255, 255, 255, 0.6); }
        #main-nav.scrolled #current-section { color: white; }

        #logo img { height: 2.5rem; transition: all 0.3s ease; }
        @media (min-width: 1024px) { #logo img { height: 3rem; } }

        /* Section Indicator - Left side dropdown */
        #section-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tcs-section-divider {
            width: 1px;
            height: 2.5rem;
            background: rgba(255, 255, 255, 0.6);
            margin: 0 1.25rem;
            transition: background 0.3s ease;
        }

        #current-section {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: white;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        #current-section:hover { opacity: 0.9; }

        .tcs-menu-arrow {
            display: inline-block;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .tcs-menu-arrow svg {
            transition: transform 0.3s ease;
        }

        /* Section Menu - Right side */
        .tcs-section-menu {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
            gap: 3rem;
        }

        .tcs-menu-item {
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: white;
            text-decoration: none;
            transition: all 0.5s ease;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            white-space: nowrap;
        }

        .tcs-menu-item:hover { opacity: 0.8; }

        .nav-login {
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: all 0.3s ease;
            opacity: 0.9;
        }
        .nav-login:hover { opacity: 0.8; }
        #main-nav.scrolled .nav-login { color: white; }

        /* User Dropdown Menu */
        .user-dropdown-container {
            position: relative;
        }

        .user-dropdown-trigger {
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .user-dropdown-trigger svg {
            transition: transform 0.2s ease;
        }

        .user-dropdown-container:hover .user-dropdown-trigger svg {
            transform: rotate(180deg);
        }

        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            min-width: 160px;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 100;
            margin-top: 0.5rem;
        }

        .user-dropdown-container:hover .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: block;
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            background: none;
            border: none;
            cursor: pointer;
        }

        .user-dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--color-metallic);
        }

        /* Mobile User Section */
        .mobile-user-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 1rem;
        }

        .mobile-user-welcome {
            font-size: 0.875rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--color-metallic);
            opacity: 0.9;
        }

        .mobile-logout-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.125rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            text-align: left;
            padding: 0;
            transition: all 0.3s ease;
        }

        .mobile-logout-btn:hover {
            color: var(--color-metallic);
        }

        /* Mobile Menu Toggle - Original TCS */
        .tcs-mobile-menu-toggle-line {
            display: block;
            width: 22px;
            height: 1px;
            background: currentColor;
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .tcs-mobile-menu-toggle-line:first-child {
            margin-bottom: 6px;
        }

        #menu-toggle.active .tcs-mobile-menu-toggle-line:first-child {
            transform: rotate(45deg) translate(3px, 3px);
        }

        #menu-toggle.active .tcs-mobile-menu-toggle-line:last-child {
            transform: rotate(-45deg) translate(3px, -3px);
        }

        #main-nav.scrolled #menu-toggle { color: white; }

        .max-w-screen-2xl { max-width: 1536px; }

        /* Desktop menu scrolled state */
        #main-nav.scrolled .brightness-0 { filter: brightness(0) !important; }
        #main-nav.scrolled .invert { filter: invert(100%) !important; }

        #menu-overlay {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            z-index: 99; opacity: 0; visibility: hidden;
            transition: all 0.4s ease;
        }
        #menu-overlay.active { opacity: 1; visibility: visible; }
        #menu-overlay nav { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem; }
        #menu-overlay nav a {
            color: white; text-decoration: none;
            font-size: 1.5rem; font-weight: 300;
            letter-spacing: 0.1em; text-transform: uppercase;
            opacity: 0; transform: translateY(20px);
            transition: all 0.4s ease;
        }
        #menu-overlay.active nav a { opacity: 1; transform: translateY(0); }
        #menu-overlay nav a:nth-child(1) { transition-delay: 0.1s; }
        #menu-overlay nav a:nth-child(2) { transition-delay: 0.15s; }
        #menu-overlay nav a:nth-child(3) { transition-delay: 0.2s; }
        #menu-overlay nav a:nth-child(4) { transition-delay: 0.25s; }
        #menu-overlay nav a:nth-child(5) { transition-delay: 0.3s; }
        #menu-overlay nav a:nth-child(6) { transition-delay: 0.35s; }
        #menu-overlay nav a:nth-child(7) { transition-delay: 0.4s; }

        /* =================================================================
           ANIMATIONS
           ================================================================= */

        .fade-in-section { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .fade-in-section.is-visible { opacity: 1; transform: translateY(0); }

        /* =================================================================
           CATALOG CAPTION - Featured Project Badge (Original TCS)
           ================================================================= */

        @keyframes slideIn {
            0% { opacity: 0; transform: translateX(-50px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        .animate-slide-in {
            animation: slideIn 0.8s ease-out 0.8s forwards;
        }

        .catalog-caption {
            position: relative;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: clamp(8px, 1.5vw, 16px) clamp(12px, 2vw, 24px) clamp(8px, 1.5vw, 16px) clamp(10px, 1.8vw, 20px);
            max-width: 100%;
        }

        .catalog-caption .caption-title {
            font-size: clamp(10px, 1.5vw, 14px);
            line-height: 1.5;
            letter-spacing: 0.05em;
        }

        .catalog-caption .caption-subtitle {
            font-size: clamp(7px, 0.8vw, 9px);
            letter-spacing: 0.2em;
        }

        .catalog-caption .caption-line {
            width: clamp(16px, 2vw, 24px);
        }

        .catalog-caption .caption-view {
            margin-left: clamp(6px, 0.8vw, 8px);
            font-size: clamp(7px, 0.8vw, 9px);
        }

        .catalog-caption-container {
            left: 5%;
            top: 33%;
            width: clamp(200px, 30vw, 400px);
        }

        @media (min-width: 1536px) {
            .catalog-caption-container { left: 8%; top: 30%; }
            .catalog-caption { max-width: 400px; }
        }

        @media (min-width: 1024px) and (max-width: 1535px) {
            .catalog-caption-container { left: 5%; top: 33%; }
            .catalog-caption { max-width: 360px; }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .catalog-caption-container { left: 5%; top: 35%; }
            .catalog-caption { max-width: 320px; }
        }

        @media (min-width: 428px) and (max-width: 767px) {
            .catalog-caption-container { left: 5%; top: 33%; width: 90%; }
            .catalog-caption { backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); background: linear-gradient(to right, rgba(0,0,0,0.4), rgba(0,0,0,0.2)); }
        }

        @media (max-width: 428px) {
            .catalog-caption-container { left: 5% !important; top: 33% !important; width: 90% !important; }
            .catalog-caption { backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); background: linear-gradient(to right, rgba(0,0,0,0.5), rgba(0,0,0,0.3)); border-radius: 4px !important; transform: scale(0.9); transform-origin: left center; }
        }

        .backdrop-blur-sm { backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
        .hover\:backdrop-blur-md:hover { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .bg-gradient-to-r { background-image: linear-gradient(to right, var(--tw-gradient-stops)); }
        .from-black\/30 { --tw-gradient-from: rgba(0, 0, 0, 0.3); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(0, 0, 0, 0)); }
        .to-black\/20 { --tw-gradient-to: rgba(0, 0, 0, 0.2); }
        .text-white\/70 { color: rgba(255, 255, 255, 0.7); }
        .text-white\/60 { color: rgba(255, 255, 255, 0.6); }
        .border-white\/20 { border-color: rgba(255, 255, 255, 0.2); }
        .border-white\/30 { border-color: rgba(255, 255, 255, 0.3); }
        .bg-white\/30 { background-color: rgba(255, 255, 255, 0.3); }
        .bg-white\/50 { background-color: rgba(255, 255, 255, 0.5); }
        .rounded-r { border-top-right-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
        .z-40 { z-index: 40; }

        /* Fix link colors in catalog caption */
        .catalog-caption-container a,
        .catalog-caption-container a:visited,
        .catalog-caption-container a:hover,
        .catalog-caption-container a:active {
            color: inherit;
            text-decoration: none;
        }

        .title-smart-break { word-break: keep-all; hyphens: none; overflow-wrap: break-word; word-spacing: 0.05em; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }

        /* =================================================================
           UTILITY CLASSES
           ================================================================= */

        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        @media (min-width: 640px) { .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 768px) {
            .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) { .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .xl\:grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); } }

        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-center { justify-content: center; }
        .justify-between { justify-content: space-between; }

        .gap-4 { gap: 1rem; } .gap-6 { gap: 1.5rem; } .gap-8 { gap: 2rem; } .gap-10 { gap: 2.5rem; } .gap-12 { gap: 3rem; } .gap-16 { gap: 4rem; }
        .gap-x-8 { column-gap: 2rem; } .gap-x-16 { column-gap: 4rem; }
        .gap-y-8 { row-gap: 2rem; } .gap-y-16 { row-gap: 4rem; } .gap-y-24 { row-gap: 6rem; } .gap-y-32 { row-gap: 8rem; }
        @media (min-width: 768px) { .md\:gap-y-24 { row-gap: 6rem; } }
        @media (min-width: 1024px) { .lg\:gap-8 { gap: 2rem; } .lg\:gap-16 { gap: 4rem; } .lg\:gap-x-16 { column-gap: 4rem; } .lg\:gap-y-32 { row-gap: 8rem; } }

        .space-x-2 > * + * { margin-left: 0.5rem; } .space-y-2 > * + * { margin-top: 0.5rem; } .space-y-4 > * + * { margin-top: 1rem; }

        .mb-2 { margin-bottom: 0.5rem; } .mb-3 { margin-bottom: 0.75rem; } .mb-4 { margin-bottom: 1rem; } .mb-6 { margin-bottom: 1.5rem; } .mb-8 { margin-bottom: 2rem; } .mb-12 { margin-bottom: 3rem; } .mb-16 { margin-bottom: 4rem; } .mb-20 { margin-bottom: 5rem; }
        .mt-3 { margin-top: 0.75rem; } .mt-4 { margin-top: 1rem; } .mt-6 { margin-top: 1.5rem; } .mt-8 { margin-top: 2rem; } .mt-12 { margin-top: 3rem; } .mt-20 { margin-top: 5rem; } .mt-24 { margin-top: 6rem; }
        .ml-2 { margin-left: 0.5rem; } .ml-4 { margin-left: 1rem; } .mr-2 { margin-right: 0.5rem; } .mr-4 { margin-right: 1rem; }

        .px-4 { padding-left: 1rem; padding-right: 1rem; } .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; } .px-8 { padding-left: 2rem; padding-right: 2rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; } .py-4 { padding-top: 1rem; padding-bottom: 1rem; } .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; } .py-8 { padding-top: 2rem; padding-bottom: 2rem; } .py-12 { padding-top: 3rem; padding-bottom: 3rem; } .py-16 { padding-top: 4rem; padding-bottom: 4rem; } .py-20 { padding-top: 5rem; padding-bottom: 5rem; } .py-24 { padding-top: 6rem; padding-bottom: 6rem; }
        .p-4 { padding: 1rem; } .p-6 { padding: 1.5rem; } .p-8 { padding: 2rem; }
        .pr-4 { padding-right: 1rem; } .pr-8 { padding-right: 2rem; }

        @media (min-width: 768px) { .md\:px-6 { padding-left: 1.5rem; padding-right: 1.5rem; } .md\:px-8 { padding-left: 2rem; padding-right: 2rem; } .md\:py-24 { padding-top: 6rem; padding-bottom: 6rem; } }
        @media (min-width: 1024px) { .lg\:py-32 { padding-top: 8rem; padding-bottom: 8rem; } }

        .w-4 { width: 1rem; } .w-6 { width: 1.5rem; } .w-8 { width: 2rem; } .w-12 { width: 3rem; } .w-16 { width: 4rem; } .w-full { width: 100%; }
        .h-4 { height: 1rem; } .h-6 { height: 1.5rem; } .h-8 { height: 2rem; } .h-12 { height: 3rem; } .h-16 { height: 4rem; } .h-full { height: 100%; } .h-screen { height: 100vh; }
        .min-h-screen { min-height: 100vh; }

        .max-w-2xl { max-width: 42rem; } .max-w-3xl { max-width: 48rem; } .max-w-4xl { max-width: 56rem; } .max-w-5xl { max-width: 64rem; } .max-w-6xl { max-width: 72rem; } .max-w-7xl { max-width: 80rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        .text-xs { font-size: 0.75rem; } .text-sm { font-size: 0.875rem; } .text-base { font-size: 1rem; } .text-lg { font-size: 1.125rem; } .text-xl { font-size: 1.25rem; } .text-2xl { font-size: 1.5rem; } .text-3xl { font-size: 1.875rem; } .text-4xl { font-size: 2.25rem; }
        @media (min-width: 768px) { .md\:text-xl { font-size: 1.25rem; } .md\:text-2xl { font-size: 1.5rem; } .md\:text-3xl { font-size: 1.875rem; } }
        @media (min-width: 1024px) { .lg\:text-4xl { font-size: 2.25rem; } .lg\:text-5xl { font-size: 3rem; } }

        .font-light { font-weight: 300; } .font-normal { font-weight: 400; } .font-medium { font-weight: 500; } .font-semibold { font-weight: 600; }
        .italic { font-style: italic; } .uppercase { text-transform: uppercase; }
        .tracking-wide { letter-spacing: 0.025em; } .tracking-wider { letter-spacing: 0.05em; } .tracking-widest { letter-spacing: 0.1em; }
        .leading-tight { line-height: 1.25; } .leading-snug { line-height: 1.375; } .leading-relaxed { line-height: 1.625; } .leading-loose { line-height: 2; }
        .text-center { text-align: center; } .text-left { text-align: left; }

        .text-white { color: white; } .text-white\/80 { color: rgba(255, 255, 255, 0.8); } .text-white\/90 { color: rgba(255, 255, 255, 0.9); }
        .bg-white { background-color: white; } .bg-black { background-color: black; } .bg-black\/30 { background-color: rgba(0, 0, 0, 0.3); }

        .text-amber-600 { color: #d97706; } .text-amber-700 { color: #b45309; }
        .bg-amber-100 { background-color: #fef3c7; }

        .bg-neutral-50 { background-color: #fafafa; } .bg-neutral-100 { background-color: #f5f5f5; } .bg-neutral-800 { background-color: #262626; } .bg-neutral-900 { background-color: #171717; }
        .text-neutral-300 { color: #d4d4d4; } .text-neutral-400 { color: #a3a3a3; } .text-neutral-500 { color: #737373; } .text-neutral-600 { color: #525252; } .text-neutral-700 { color: #404040; } .text-neutral-800 { color: #262626; } .text-neutral-900 { color: #171717; }

        .border { border-width: 1px; } .border-b { border-bottom-width: 1px; } .border-t { border-top-width: 1px; }
        .border-white { border-color: white; } .border-neutral-200 { border-color: #e5e5e5; } .border-neutral-300 { border-color: #d4d4d4; } .border-neutral-400 { border-color: #a3a3a3; } .border-neutral-800 { border-color: #262626; } .border-neutral-900 { border-color: #171717; }
        .rounded { border-radius: 0.25rem; } .rounded-lg { border-radius: 0.5rem; } .rounded-full { border-radius: 9999px; }

        .relative { position: relative; } .absolute { position: absolute; } .fixed { position: fixed; }
        .inset-0 { top: 0; right: 0; bottom: 0; left: 0; } .z-0 { z-index: 0; } .z-10 { z-index: 10; } .z-20 { z-index: 20; } .z-50 { z-index: 50; }
        .overflow-hidden { overflow: hidden; }

        .block { display: block; } .inline-block { display: inline-block; } .inline-flex { display: inline-flex; } .hidden { display: none; }
        @media (min-width: 768px) { .md\:flex { display: flex; } .md\:hidden { display: none; } .md\:block { display: block; } .md\:order-1 { order: 1; } .md\:order-2 { order: 2; } }
        @media (min-width: 1024px) { .lg\:flex { display: flex; } .lg\:hidden { display: none; } }
        .order-1 { order: 1; } .order-2 { order: 2; }

        .object-cover { object-fit: cover; } .object-center { object-position: center; }
        .aspect-square { aspect-ratio: 1 / 1; } .aspect-video { aspect-ratio: 16 / 9; } .aspect-\[4\/3\] { aspect-ratio: 4 / 3; } .aspect-\[3\/2\] { aspect-ratio: 3 / 2; }
        @media (min-width: 768px) { .md\:aspect-\[3\/2\] { aspect-ratio: 3 / 2; } }
        @media (min-width: 1024px) { .lg\:aspect-\[4\/3\] { aspect-ratio: 4 / 3; } .lg\:aspect-video { aspect-ratio: 16 / 9; } }

        .transition { transition-property: all; transition-duration: 150ms; } .transition-all { transition-property: all; transition-duration: 150ms; } .transition-transform { transition-property: transform; transition-duration: 150ms; }
        .duration-300 { transition-duration: 300ms; } .duration-500 { transition-duration: 500ms; } .duration-700 { transition-duration: 700ms; }

        .hover\:scale-105:hover { transform: scale(1.05); } .hover\:-translate-y-2:hover { transform: translateY(-0.5rem); }
        .group:hover .group-hover\:translate-x-2 { transform: translateX(0.5rem); } .group:hover .group-hover\:scale-105 { transform: scale(1.05); }
        .hover\:opacity-70:hover { opacity: 0.7; } .hover\:bg-white:hover { background-color: white; } .hover\:bg-neutral-900:hover { background-color: #171717; } .hover\:text-white:hover { color: white; }

        .cursor-pointer { cursor: pointer; } .group { position: relative; }
        .-translate-y-1\/2 { transform: translateY(-50%); }
        .top-1\/2 { top: 50%; }
        .max-w-48 { max-width: 12rem; }
        .animate-bounce { animation: bounce 1s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(-25%); animation-timing-function: cubic-bezier(0.8, 0, 1, 1); } 50% { transform: translateY(0); animation-timing-function: cubic-bezier(0, 0, 0.2, 1); } }
        .opacity-0 { opacity: 0; } .opacity-40 { opacity: 0.4; } .opacity-50 { opacity: 0.5; } .opacity-60 { opacity: 0.6; } .opacity-70 { opacity: 0.7; } .opacity-80 { opacity: 0.8; }
        .brightness-0 { filter: brightness(0); } .invert { filter: invert(100%); }

        /* =================================================================
           FOOTER
           ================================================================= */

        .tcs-footer { background: #171717; color: white; padding: 6rem 2rem 2rem; }
        .footer-link { color: #a3a3a3; text-decoration: none; font-size: 0.75rem; letter-spacing: 0.1em; font-weight: 300; transition: all 0.5s ease; display: inline-block; }
        .footer-link:hover { color: white; transform: translateY(-1px); }
        .footer-heading { font-size: 0.875rem; margin-bottom: 1.25rem; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase; }
    </style>

    @livewireStyles
</head>
<body>
    <!-- Navigation - Exact Copy from Original TCS -->
    <nav id="main-nav">
        <div class="px-4 mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
            <!-- Mobile Layout (lg:hidden) -->
            <div class="flex items-center justify-between h-16 lg:hidden relative z-[95]">
                <!-- Left Side: Logo -->
                <a href="/" id="logo" class="relative block">
                    <img src="/images/logo.svg" alt="TCS Woodwork" class="h-10 brightness-0 invert">
                </a>
                <!-- Hamburger Menu -->
                <button id="menu-toggle" class="relative p-2 -mr-2 rounded-md text-white focus:outline-none" aria-label="Toggle mobile menu">
                    <span class="sr-only">Open menu</span>
                    <div class="flex flex-col">
                        <span class="tcs-mobile-menu-toggle-line"></span>
                        <span class="tcs-mobile-menu-toggle-line"></span>
                    </div>
                </button>
            </div>

            <!-- Desktop Layout (hidden lg:flex) -->
            <div class="items-center justify-between hidden h-16 lg:flex">
                <!-- Left Side: Logo and Section Indicator -->
                <div class="relative z-10 flex items-center">
                    <a href="/" id="logo" class="block">
                        <img src="/images/logo.svg" alt="TCS Woodwork" class="h-9 brightness-0 invert transition-all duration-300">
                    </a>
                    <!-- Section Indicator - Desktop Only -->
                    <div id="section-indicator" class="group relative z-[100] hidden md:ml-4 lg:ml-10 lg:flex lg:items-center text-white">
                        <div class="tcs-section-divider"></div>
                        <span id="current-section" class="relative flex items-center gap-1 text-sm font-light tracking-wider uppercase transition-all duration-300 cursor-pointer whitespace-nowrap hover:opacity-90 group-hover:opacity-90">Home</span>
                        <!-- Animated arrow indicator -->
                        <span class="tcs-menu-arrow group-hover:translate-y-1 opacity-80">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rotate-90">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </span>
                    </div>
                </div>
                <!-- Right Side: Navigation Menu -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center tcs-section-menu" aria-label="Desktop menu">
                        <a href="/residential" class="tcs-menu-item">Residential</a>
                        <a href="/commercial" class="tcs-menu-item">Commercial</a>
                        <a href="/furniture" class="tcs-menu-item">Furniture</a>
                        <a href="/work" class="tcs-menu-item">Our Work</a>
                        <a href="/contact" class="tcs-menu-item">Contact</a>
                        @auth
                            <div class="relative user-dropdown-container">
                                <button class="tcs-menu-item user-dropdown-trigger flex items-center gap-1">
                                    Welcome, {{ auth()->user()->name }}
                                    <svg class="w-3 h-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div class="user-dropdown-menu">
                                    <a href="{{ url('/admin') }}" class="user-dropdown-item">Dashboard</a>
                                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="w-full">
                                        @csrf
                                        <button type="submit" class="user-dropdown-item w-full text-left">Logout</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ url('/admin/login') }}" class="tcs-menu-item">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="menu-overlay">
        <nav>
            <a href="/">Home</a>
            <a href="/residential">Residential</a>
            <a href="/commercial">Commercial</a>
            <a href="/furniture">Furniture</a>
            <a href="/work">Our Work</a>
            <a href="/contact">Contact</a>
            @auth
                <div class="mobile-user-section">
                    <span class="mobile-user-welcome">Welcome, {{ auth()->user()->name }}</span>
                    <a href="{{ url('/admin') }}">Dashboard</a>
                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                        @csrf
                        <button type="submit" class="mobile-logout-btn">Logout</button>
                    </form>
                </div>
            @else
                <a href="{{ url('/admin/login') }}">Login</a>
            @endauth
        </nav>
    </div>

    <main>@yield('content')</main>

    <!-- Footer -->
    <footer class="tcs-footer">
        <div class="container-tcs">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <div>
                    <img src="/images/logo.svg" alt="TCS Woodwork" class="h-8 mb-6 brightness-0 invert">
                    <p class="text-neutral-400 text-sm leading-relaxed" style="max-width: none;">Exceptional craftsmanship for discerning clients. Custom cabinetry, fine millwork, and bespoke furniture crafted in the heart of New York.</p>
                </div>
                <div>
                    <h4 class="footer-heading">Services</h4>
                    <ul class="space-y-2">
                        <li><a href="/services#cabinetry" class="footer-link">Custom Cabinetry</a></li>
                        <li><a href="/services#furniture" class="footer-link">Bespoke Furniture</a></li>
                        <li><a href="/services#millwork" class="footer-link">Commercial Millwork</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-heading">Company</h4>
                    <ul class="space-y-2">
                        <li><a href="/about" class="footer-link">About Us</a></li>
                        <li><a href="/process" class="footer-link">Our Process</a></li>
                        <li><a href="/journal" class="footer-link">Journal</a></li>
                        <li><a href="/contact" class="footer-link">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-heading">Contact</h4>
                    <ul class="space-y-2 text-neutral-400 text-sm">
                        <li>Yonkers, NY</li>
                        <li><a href="mailto:info@tcswoodwork.com" class="footer-link">info@tcswoodwork.com</a></li>
                        <li><a href="tel:+19145989063" class="footer-link">(914) 598-9063</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-neutral-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-neutral-500 text-sm">&copy; {{ date('Y') }} TCS Woodwork. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="/privacy" class="footer-link text-xs uppercase tracking-wider">Privacy Policy</a>
                    <a href="/terms" class="footer-link text-xs uppercase tracking-wider">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
    <script>
        // Navigation scroll handling - Original TCS behavior
        const mainNav = document.getElementById('main-nav');
        let isScrolled = window.scrollY > 10;

        function updateNavOnScroll() {
            isScrolled = window.scrollY > 10;
            if (isScrolled) {
                mainNav.classList.add('scrolled');
            } else {
                mainNav.classList.remove('scrolled');
            }
        }

        window.addEventListener('scroll', updateNavOnScroll);
        updateNavOnScroll();

        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const menuOverlay = document.getElementById('menu-overlay');

        if (menuToggle && menuOverlay) {
            menuToggle.addEventListener('click', () => {
                menuToggle.classList.toggle('active');
                menuOverlay.classList.toggle('active');
                document.body.style.overflow = menuOverlay.classList.contains('active') ? 'hidden' : '';
            });

            menuOverlay.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    menuToggle.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
        }

        // Fade-in animations for sections
        const fadeInSections = document.querySelectorAll('.fade-in-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.1 });
        fadeInSections.forEach(section => observer.observe(section));
    </script>
</body>
</html>
