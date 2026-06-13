<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Holding App')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-surface text-on-surface antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen flex-col">
        <!-- M3 Top App Bar -->
        <header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-outline-variant bg-surface-container-lowest px-4 shadow-elevated lg:hidden">
            <button @click="sidebarOpen = !sidebarOpen" type="button" class="-ml-2 inline-flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container" aria-label="Buka menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <span class="text-base font-semibold tracking-tight">Holding App</span>
            <span class="ml-auto inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold
                {{ auth()->user()->isSuperadmin() ? 'bg-primary-container text-on-primary-container' : (auth()->user()->isTenantOwner() ? 'bg-secondary-container text-on-secondary-container' : 'bg-tertiary-container text-on-tertiary-container') }}">
                {{ match(auth()->user()->role) { 'superadmin' => 'Vendor', 'tenant_owner' => 'Pemilik', 'tenant_staff' => 'Staff', default => auth()->user()->role } }}
            </span>
        </header>

        <div class="flex flex-1">
            @auth
                @include('partials.sidebar')
            @endauth

            @auth
            <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 z-30 bg-black/40 lg:hidden" x-cloak></div>
            @endauth

            <main class="flex min-w-0 flex-1 flex-col">
                @if (session('status'))
                    <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
                        <x-ui.alert variant="success" icon="check-circle">{{ session('status') }}</x-ui.alert>
                    </div>
                @endif
                @if ($errors->any() && ! request()->routeIs('login'))
                    <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
                        <x-ui.alert variant="error" icon="x-circle">{{ $errors->first() }}</x-ui.alert>
                    </div>
                @endif

                <div class="flex-1 px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                    @yield('content')
                </div>

                <footer class="border-t border-outline-variant bg-surface-container-lowest px-4 py-4 sm:px-6 lg:px-8">
                    <div class="mx-auto flex max-w-7xl flex-col gap-1 text-center text-xs text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
                        <span>&copy; {{ date('Y') }} Holding App &mdash; Pusat Kendali Multi-Tenant.</span>
                        <span>v0.1.0 &middot; <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">Masuk</a></span>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
