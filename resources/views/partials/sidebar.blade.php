@php
$user = auth()->user();
$role = $user->role;
$navItems = match(true) {
    $user->isSuperadmin() => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Aplikasi', 'route' => 'admin.applications.index', 'match' => 'admin.applications*', 'icon' => 'cube'],
        ['label' => 'Tenant', 'route' => 'admin.tenants.index', 'match' => 'admin.tenants*', 'icon' => 'building'],
        ['label' => 'Pengguna', 'route' => 'admin.users.index', 'match' => 'admin.users*', 'icon' => 'users'],
        ['label' => 'Log Aktivitas', 'route' => 'admin.activity-logs.index', 'match' => 'admin.activity-logs*', 'icon' => 'list'],
    ],
    $user->isTenantOwner() => [
        ['label' => 'Beranda', 'route' => 'tenant.home', 'match' => 'tenant.home', 'icon' => 'home'],
        ['label' => 'Staff', 'route' => 'tenant.staff.index', 'match' => 'tenant.staff*', 'icon' => 'users'],
    ],
    default => [
        ['label' => 'Beranda', 'route' => 'tenant.home', 'match' => 'tenant.home', 'icon' => 'home'],
    ],
};

$roleLabel = match($role) {
    'superadmin' => 'Vendor Admin',
    'tenant_owner' => 'Pemilik Tenant',
    'tenant_staff' => 'Staff Tenant',
    default => $role,
};

$roleBg = match($role) {
    'superadmin' => 'bg-primary-container text-on-primary-container',
    'tenant_owner' => 'bg-secondary-container text-on-secondary-container',
    'tenant_staff' => 'bg-tertiary-container text-on-tertiary-container',
    default => 'bg-surface-container text-on-surface-variant',
};
@endphp

<!-- M3 Navigation Rail -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col border-r border-outline-variant bg-surface-container-low transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
       x-cloak>
    <!-- Brand -->
    <div class="flex h-16 items-center gap-3 border-b border-outline-variant px-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-on-primary shadow-elevated">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
        </div>
        <div>
            <div class="text-base font-bold tracking-tight text-on-surface leading-tight">Holding App</div>
            <div class="text-[10px] font-medium uppercase tracking-wider text-on-surface-variant leading-tight">Pusat Kendali</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Menu</div>
        <ul class="space-y-1">
            @foreach($navItems as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <li>
                <a href="{{ route($item['route']) }}"
                   class="group flex items-center gap-3 rounded-full px-3 py-2 text-sm font-medium transition {{ $active ? 'text-primary' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' }}">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full transition {{ $active ? 'bg-primary text-on-primary' : 'text-on-surface-variant group-hover:text-on-surface' }}">
                        @switch($item['icon'])
                            @case('home')<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>@break
                            @case('cube')<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>@break
                            @case('building')<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>@break
                            @case('users')<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>@break
                            @case('list')<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>@break
                        @endswitch
                    </span>
                    {{ $item['label'] }}
                </a>
            </li>
            @endforeach
        </ul>
    </nav>

    <!-- User footer (M3 ListItem feel) -->
    <div class="border-t border-outline-variant p-3">
        <div class="flex items-center gap-3 rounded-lg p-2 hover:bg-surface-container">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary text-sm font-semibold">
                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-on-surface">{{ $user->name }}</div>
                <div class="truncate text-xs text-on-surface-variant">{{ $user->email }}</div>
            </div>
        </div>
        <div class="mt-2 flex items-center justify-between px-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $roleBg }}">{{ $roleLabel }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-on-surface-variant hover:text-error transition">Keluar</button>
            </form>
        </div>
    </div>
</aside>
