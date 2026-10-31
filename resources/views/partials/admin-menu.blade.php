<nav class="dashboard-menu" aria-label="Menu admin">
    <a href="{{ route('admin.kost') }}" class="dashboard-menu-item {{ request()->routeIs('admin.kost*') ? 'is-active' : '' }}" data-testid="admin-menu-kost">Kelola Kost</a>
    <a href="{{ route('admin.verification') }}" class="dashboard-menu-item {{ request()->routeIs('admin.verification') ? 'is-active' : '' }}" data-testid="admin-menu-verification">Verifikasi</a>
    <a href="{{ route('admin.users') }}" class="dashboard-menu-item {{ request()->routeIs('admin.users') ? 'is-active' : '' }}" data-testid="admin-menu-users">Pengguna</a>
    <a href="{{ route('admin.bookings') }}" class="dashboard-menu-item {{ request()->routeIs('admin.bookings') ? 'is-active' : '' }}" data-testid="admin-menu-bookings">Booking</a>
    <a href="{{ route('admin.campuses') }}" class="dashboard-menu-item {{ request()->routeIs('admin.campuses') ? 'is-active' : '' }}" data-testid="admin-menu-campuses">Kampus</a>
    <a href="{{ route('admin.import') }}" class="dashboard-menu-item {{ request()->routeIs('admin.import') ? 'is-active' : '' }}" data-testid="admin-menu-import">Impor Data</a>
    <a href="{{ route('admin.statistics') }}" class="dashboard-menu-item {{ request()->routeIs('admin.statistics') ? 'is-active' : '' }}" data-testid="admin-menu-statistics">Statistik</a>
</nav>
