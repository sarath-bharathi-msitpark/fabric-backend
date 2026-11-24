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
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        @auth
        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-30 lg:hidden print:hidden"></div>

        {{-- Sidebar --}}
        <aside class="w-64 bg-slate-800 text-slate-100 flex flex-col fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 -translate-x-full lg:translate-x-0 print:hidden"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 flex items-center px-4 lg:px-6 border-b border-slate-700 justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <svg class="h-8 w-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m3 10V4m3 13v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="font-semibold text-sm">Fabric Dashboard</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 text-sm">
                <x-nav-item-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="chart">Dashboard</x-nav-item-link>
                @hasanyrole('admin|manager')
                <span class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Admin Panel</span>
                <x-nav-item-link :href="route('admin.overview')" :active="request()->routeIs('admin.overview')" icon="grid">Overview</x-nav-item-link>
                <x-nav-item-link :href="route('admin.fabric-records.index')" :active="request()->routeIs('admin.fabric-records.*')" icon="table">Fabric Records</x-nav-item-link>
                <x-nav-item-link :href="route('admin.suppliers.index')" :active="request()->routeIs('admin.suppliers.*')" icon="truck">Suppliers</x-nav-item-link>
                <x-nav-item-link :href="route('admin.buyers.index')" :active="request()->routeIs('admin.buyers.*')" icon="users">Buyers</x-nav-item-link>
                <x-nav-item-link :href="route('admin.styles.index')" :active="request()->routeIs('admin.styles.*')" icon="tag">Styles</x-nav-item-link>
                @endhasanyrole
                @hasanyrole('admin|manager|cutting_qc|cutting_incharge|qam|management|merchandiser')
                <span class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Cutting QMS</span>
                <x-nav-item-link :href="route('admin.cutting.dashboard')" :active="request()->routeIs('admin.cutting.dashboard')" icon="grid">Cutting Dashboard</x-nav-item-link>
                <x-nav-item-link :href="route('admin.cutting.audits.index')" :active="request()->routeIs('admin.cutting.audits.*') || request()->routeIs('admin.cutting.stages.*')" icon="table">Audit Register</x-nav-item-link>
                <x-nav-item-link :href="route('admin.cutting.reports.index')" :active="request()->routeIs('admin.cutting.reports.*')" icon="chart">Cutting Reports</x-nav-item-link>
                @role('admin')
                <x-nav-item-link :href="route('admin.cutting.checklists.index')" :active="request()->routeIs('admin.cutting.checklists.*')" icon="tag">Checklists</x-nav-item-link>
                <x-nav-item-link :href="route('admin.cutting.tables.index')" :active="request()->routeIs('admin.cutting.tables.*')" icon="table">Cutting Tables</x-nav-item-link>
                @endrole
                @endhasanyrole
                @hasanyrole('admin|manager|sewing_qc|sewing_supervisor|production_manager|qam|management|merchandiser')
                <span class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Sewing Inline Inspection</span>
                <x-nav-item-link :href="route('admin.sewing.dashboard')" :active="request()->routeIs('admin.sewing.dashboard')" icon="grid">Sewing Dashboard</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.sheets.index')" :active="request()->routeIs('admin.sewing.sheets.*') || request()->routeIs('admin.sewing.grid.*')" icon="table">Inspection Sheets</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.sheets.create')" :active="request()->routeIs('admin.sewing.sheets.create')" icon="tag">Create Inspection</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.live-board.index')" :active="request()->routeIs('admin.sewing.live-board.*')" icon="grid">Live Line Board</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.defect-log.index')" :active="request()->routeIs('admin.sewing.defect-log.*')" icon="table">Defect &amp; CAPA Log</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.reports.show', 'defect-analysis')" :active="request()->routeIs('admin.sewing.reports.show')" icon="chart">Defect Analysis</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.reports.show', 're-audit')" :active="false" icon="chart">Re-Audit Reports</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.repeat-red.index')" :active="request()->routeIs('admin.sewing.repeat-red.*')" icon="users">Repeat Red Operators</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.reports.index')" :active="request()->routeIs('admin.sewing.reports.index')" icon="chart">Inspection Reports</x-nav-item-link>
                @hasanyrole('admin|qam')
                <span class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-500">Sewing Settings</span>
                <x-nav-item-link :href="route('admin.sewing.settings.lines.index')" :active="request()->routeIs('admin.sewing.settings.lines.*')" icon="table">Lines</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.operations.index')" :active="request()->routeIs('admin.sewing.settings.operations.*')" icon="tag">Operations</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.machines.index')" :active="request()->routeIs('admin.sewing.settings.machines.*')" icon="grid">Machines</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.operators.index')" :active="request()->routeIs('admin.sewing.settings.operators.*')" icon="users">Operators</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.defects.index')" :active="request()->routeIs('admin.sewing.settings.defects.*')" icon="table">Defect Master (DCL)</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.rules.index')" :active="request()->routeIs('admin.sewing.settings.rules.*')" icon="chart">Inspection Rules</x-nav-item-link>
                <x-nav-item-link :href="route('admin.sewing.settings.rounds.index')" :active="request()->routeIs('admin.sewing.settings.rounds.*')" icon="grid">Round Configuration</x-nav-item-link>
                @endhasanyrole
                @endhasanyrole
                @role('admin')
                <x-nav-item-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="user">User Management</x-nav-item-link>
                @endrole
            </nav>
            <div class="border-t border-slate-700 p-3">
                <div class="px-2 py-1 text-xs text-slate-400">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="text-slate-500">{{ auth()->user()->email }}</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @forelse(auth()->user()->roles as $role)
                            <span class="inline-block px-2 py-0.5 rounded bg-slate-700 text-[10px] uppercase">{{ str_replace('_', ' ', $role->name) }}</span>
                        @empty
                            <span class="inline-block px-2 py-0.5 rounded bg-slate-700 text-[10px] uppercase">{{ auth()->user()->role }}</span>
                        @endforelse
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded hover:bg-slate-700 text-xs">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-slate-700 text-xs">Log Out</button>
                    </form>
                </div>
            </div>
        </aside>
        @endauth

        <div class="@auth flex-1 min-w-0 lg:ml-64 print:ml-0 @endauth min-h-screen flex flex-col">
            @auth
            <header class="min-h-16 bg-white border-b border-gray-200 flex flex-wrap items-center gap-x-3 gap-y-2 px-4 lg:px-6 py-2 print:hidden sticky top-0 z-30">
                {{-- Hamburger --}}
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded-md hover:bg-gray-100 text-gray-600" aria-label="Open menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-base lg:text-lg font-semibold text-gray-800 truncate min-w-0 flex-1">@yield('header', 'Dashboard')</h1>
                <div class="flex items-center gap-2 lg:gap-3 flex-wrap justify-end w-full sm:w-auto sm:ml-auto">
                    @yield('actions')
                </div>
            </header>
            @endauth

            <main class="flex-1 min-w-0 p-4 lg:p-6 @auth max-w-[1400px] w-full mx-auto @endauth">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                         class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm flex justify-between items-center gap-2">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="text-green-600 p-1 leading-none rounded hover:bg-green-100 flex-shrink-0">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
                         class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm flex justify-between items-center gap-2">
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="text-red-600 p-1 leading-none rounded hover:bg-red-100 flex-shrink-0">&times;</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        <p class="font-medium mb-1">Please fix the following:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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
