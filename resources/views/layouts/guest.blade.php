<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Fabric Management Dashboard'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex">
            {{-- Branding panel --}}
            <div class="hidden lg:flex lg:w-1/2 bg-slate-800 text-slate-100 flex-col justify-between p-12 relative overflow-hidden">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 30%, #22d3ee 0, transparent 40%), radial-gradient(circle at 80% 70%, #06b6d4 0, transparent 35%);"></div>

                <div class="relative z-10 flex items-center gap-2">
                    <svg class="h-9 w-9 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m3 10V4m3 13v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="font-semibold text-lg">Fabric Dashboard</span>
                </div>

                <div class="relative z-10">
                    <h1 class="text-3xl font-bold leading-tight">Fabric Management<br><span class="text-cyan-400">Quality &amp; Inventory</span></h1>
                    <p class="mt-4 text-slate-300 max-w-md">Track receipts, approvals, and rejection rates across suppliers, buyers, and styles — all in one dashboard.</p>
                </div>

                <div class="relative z-10 text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Fabric Dashboard') }}
                </div>
            </div>

            {{-- Form panel --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 bg-gray-50">
                <div class="lg:hidden mb-8 flex items-center gap-2">
                    <svg class="h-8 w-8 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m3 10V4m3 13v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="font-semibold text-lg text-slate-800">Fabric Dashboard</span>
                </div>

                <div class="w-full sm:max-w-md bg-white rounded-lg shadow-sm border border-gray-100 px-8 py-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
