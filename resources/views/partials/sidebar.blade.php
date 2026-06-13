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
        ['label' => 'Laporan', 'route' => 'tenant.reports.index', 'match' => 'tenant.reports*', 'icon' => 'chart'],
        ['label' => 'Staff', 'route' => 'tenant.staff.index', 'match' => 'tenant.staff*', 'icon' => 'users'],
    ],
    default => [
        ['label' => 'Beranda', 'route' => 'tenant.home', 'match' => 'tenant.home', 'icon' => 'home'],
        ['label' => 'Laporan', 'route' => 'tenant.reports.index', 'match' => 'tenant.reports*', 'icon' => 'chart'],
    ],
};

$roleLabel = match($role) {
    'superadmin' => 'Vendor Admin',
    'tenant_owner' => 'Pemilik Tenant',
    'tenant_staff' => 'Staff Tenant',
    default => $role,
};

$roleBadgeVariant = match($role) {
    'superadmin' => 'info',
    'tenant_owner' => 'success',
    'tenant_staff' => 'warning',
    default => 'neutral',
};
@endphp

<!-- M3 Navigation Rail -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full transform flex-col border-r border-outline-variant bg-surface-container-low transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
       x-cloak>
    <!-- Brand -->
    <div class="flex h-16 items-center gap-3 border-b border-outline-variant px-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-on-primary shadow-elevated">
            <x-ui.icon name="home" class="h-6 w-6" />
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
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
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
            <x-ui.badge :variant="$roleBadgeVariant" size="sm">{{ $roleLabel }}</x-ui.badge>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="text" size="sm" class="text-on-surface-variant hover:text-error">
                    Keluar
                </x-ui.button>
            </form>
        </div>
    </div>
</aside>
