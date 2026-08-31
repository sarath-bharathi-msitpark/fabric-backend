<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Fabric Dashboard'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex">
        @auth
        <aside class="w-64 bg-slate-800 text-slate-100 flex flex-col fixed inset-y-0 left-0 z-30 print:hidden" x-data="{ open: true }">
            <div class="h-16 flex items-center px-6 border-b border-slate-700">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <svg class="h-8 w-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m3 10V4m3 13v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="font-semibold text-sm">Fabric Dashboard</span>
                </a>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 text-sm">
                <x-nav-item-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="chart">Dashboard</x-nav-item-link>
                @hasanyrole('admin|manager')
                <span class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Admin Panel</span>
                <x-nav-item-link :href="route('admin.overview')" :active="request()->routeIs('admin.overview')" icon="grid">Overview</x-nav-item-link>
                @can('upload data')
                <x-nav-item-link :href="route('admin.upload.index')" :active="request()->routeIs('admin.upload.*')" icon="upload">Data Upload</x-nav-item-link>
                @endcan
                <x-nav-item-link :href="route('admin.fabric-records.index')" :active="request()->routeIs('admin.fabric-records.*')" icon="table">Fabric Records</x-nav-item-link>
                <x-nav-item-link :href="route('admin.suppliers.index')" :active="request()->routeIs('admin.suppliers.*')" icon="truck">Suppliers</x-nav-item-link>
                <x-nav-item-link :href="route('admin.buyers.index')" :active="request()->routeIs('admin.buyers.*')" icon="users">Buyers</x-nav-item-link>
                <x-nav-item-link :href="route('admin.styles.index')" :active="request()->routeIs('admin.styles.*')" icon="tag">Styles</x-nav-item-link>
                @endhasanyrole
                @role('admin')
                <x-nav-item-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="user">User Management</x-nav-item-link>
                @endrole
            </nav>
            <div class="border-t border-slate-700 p-3">
                <div class="px-2 py-1 text-xs text-slate-400">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="text-slate-500">{{ auth()->user()->email }}</div>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded bg-slate-700 text-[10px] uppercase">{{ auth()->user()->role }}</span>
                </div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-1.5 rounded hover:bg-slate-700 text-xs">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="w-full text-left px-3 py-1.5 rounded hover:bg-slate-700 text-xs">Log Out</button>
                    </form>
                </div>
            </div>
        </aside>
        @endauth

        <div class="@auth flex-1 ml-64 print:ml-0 @endauth min-h-screen flex flex-col">
            @auth
            <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 print:hidden">
                <h1 class="text-lg font-semibold text-gray-800">@yield('header', 'Dashboard')</h1>
                <div class="ml-auto flex items-center gap-3">
                    @yield('actions')
                </div>
            </header>
            @endauth

            <main class="flex-1 p-6 @auth max-w-[1400px] w-full mx-auto @endauth">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                         class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm flex justify-between items-center">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="text-green-600">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
                         class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm flex justify-between items-center">
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="text-red-600">&times;</button>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('modals')
    @stack('scripts')
</body>
</html>
