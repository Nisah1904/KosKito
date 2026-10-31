<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kostly.')</title>
    <link rel="icon" href="{{ asset('images/kostly-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('styles')
</head>
@php
    $user = auth()->user();
    $navLink = fn ($route, $patterns, $icon, $label, $testid, $extra = '') =>
        '<a href="' . route($route) . '" class="sidebar-link ' . (request()->routeIs(...(array) $patterns) ? 'is-active' : '') . '" data-testid="' . $testid . '" title="' . e($label) . '"><span class="sidebar-icon" aria-hidden="true">' . $icon . '</span><span class="sidebar-text">' . e($label) . '</span>' . $extra . '</a>';
    $compareCount = count((array) session('compare_ids', []));
@endphp
<body class="kostly-body">
<div class="app-shell" id="appShell">

    <aside class="app-sidebar" id="appSidebar" aria-label="Menu samping">
        <div class="sidebar-brand">
            <a href="{{ route('home') }}" class="sidebar-brand-link" aria-label="Kostly beranda">
                <img src="{{ asset('images/kostly-logo.png') }}" alt="" class="sidebar-logo">
                <span class="sidebar-text sidebar-brand-name">Kostly<em>.</em></span>
            </a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Tutup menu" data-testid="sidebar-close">×</button>
        </div>

        <div class="sidebar-scroll">
            <div class="sidebar-caption">Jelajahi</div>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                {!! $navLink('home', 'home', '⌂', 'Beranda', 'nav-home') !!}
                {!! $navLink('listings', ['listings', 'detail'], '⌕', 'Cari kost', 'nav-listings') !!}
                {!! $navLink('recommendations', 'recommendations', '✳', 'Rekomendasi', 'nav-recommendations') !!}
                {!! $navLink('compare', 'compare', '⇄', 'Bandingkan', 'nav-compare', $compareCount ? '<span class="nav-count">' . $compareCount . '</span>' : '') !!}
                {!! $navLink('cost-planner', 'cost-planner', 'Rp', 'Hitung biaya', 'nav-cost-planner') !!}
                {!! $navLink('assistant', 'assistant', '✦', 'Asisten AI', 'nav-assistant') !!}
            </nav>

            @if($user)
                <div class="sidebar-caption">Akun saya</div>
                <nav class="sidebar-nav" aria-label="Menu akun">
                    {!! $navLink('dashboard', 'dashboard', '▦', 'Dashboard', 'nav-dashboard') !!}
                    @if($user->isTenant())
                        {!! $navLink('favorites', 'favorites', '♡', 'Kost favorit', 'nav-favorites') !!}
                        {!! $navLink('booking.history', 'booking.history', '◷', 'Riwayat booking', 'nav-booking-history') !!}
                    @elseif($user->isOwner())
                        {!! $navLink('owner.properties', ['owner.properties', 'owner.properties.*'], '⌂', 'Properti saya', 'nav-owner-properties') !!}
                        {!! $navLink('owner.rooms', 'owner.rooms', '▤', 'Kamar', 'nav-owner-rooms') !!}
                        {!! $navLink('owner.bookings', 'owner.bookings', '◷', 'Booking masuk', 'nav-owner-bookings') !!}
                        {!! $navLink('owner.statistics', 'owner.statistics', '▥', 'Statistik', 'nav-owner-statistics') !!}
                    @elseif($user->isAdmin())
                        {!! $navLink('admin.kost', 'admin.kost*', '⌂', 'Kelola kost', 'nav-admin-kost') !!}
                        {!! $navLink('admin.verification', 'admin.verification', '✓', 'Verifikasi', 'nav-admin-verification') !!}
                        {!! $navLink('admin.users', 'admin.users', '♙', 'Pengguna', 'nav-admin-users') !!}
                        {!! $navLink('admin.bookings', 'admin.bookings', '◷', 'Semua booking', 'nav-admin-bookings') !!}
                        {!! $navLink('admin.campuses', 'admin.campuses', '⌖', 'Kampus', 'nav-admin-campuses') !!}
                        {!! $navLink('admin.import', 'admin.import', '↓', 'Impor data', 'nav-admin-import') !!}
                        {!! $navLink('admin.statistics', 'admin.statistics', '▥', 'Statistik', 'nav-admin-statistics') !!}
                    @endif
                    {!! $navLink('profile', 'profile', '◎', 'Profil', 'nav-profile') !!}
                </nav>
            @endif

            <div class="sidebar-caption">Bantuan</div>
            <nav class="sidebar-nav" aria-label="Bantuan">
                {!! $navLink('help', ['help', 'how-booking', 'contact'], '?', 'Pusat bantuan', 'nav-help') !!}
            </nav>
        </div>

        <div class="sidebar-account">
            @if($user)
                <span class="account-avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span class="account-details sidebar-text"><strong>{{ $user->name }}</strong><small>{{ ucfirst($user->role) }}</small></span>
                <form method="POST" action="{{ route('logout') }}" class="account-logout-form">@csrf<button class="account-logout" type="submit" title="Keluar" data-testid="logout-button">Keluar</button></form>
            @else
                <a class="account-login" href="{{ route('login') }}" data-testid="sidebar-login"><span class="sidebar-icon">→</span><span class="sidebar-text">Masuk / Daftar</span></a>
            @endif
        </div>
    </aside>

    <div class="sidebar-scrim" id="sidebarScrim" data-testid="sidebar-scrim"></div>

    <div class="app-main-column">
        <header class="app-topbar">
            <button class="menu-toggle" type="button" id="menuToggle" aria-label="Buka atau tutup menu" aria-controls="appSidebar" aria-expanded="false" data-testid="mobile-menu-toggle">
                <span></span><span></span><span></span>
            </button>
            <a href="{{ route('home') }}" class="topbar-logo" aria-label="Kostly beranda" data-testid="topbar-logo">
                <img src="{{ asset('images/kostly-logo.png') }}" alt="">
                <span>Kostly<em>.</em></span>
            </a>
            <form action="{{ route('listings') }}" method="GET" class="topbar-search" role="search">
                <span aria-hidden="true">⌕</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, area, atau alamat kost" aria-label="Cari kost" data-testid="topbar-search-input">
                <button type="submit" data-testid="topbar-search-submit">Cari</button>
            </form>
            <div class="topbar-actions">
                @if($user)
                    <a href="{{ route('profile') }}" class="topbar-user" title="Profil" data-testid="topbar-user">
                        <span class="topbar-user-avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="topbar-user-name">{{ $user->name }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="topbar-signin" data-testid="topbar-signin">Masuk</a>
                    <a href="{{ route('register') }}" class="topbar-register" data-testid="topbar-register">Daftar</a>
                @endif
            </div>
        </header>

        <main class="app-content" id="mainContent">
            @if(session('success'))<div class="flash-message success" role="status" data-testid="flash-success"><span>✓</span><p>{{ session('success') }}</p><button type="button" class="flash-dismiss" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>@endif
            @if(session('error'))<div class="flash-message error" role="alert" data-testid="flash-error"><span>!</span><p>{{ session('error') }}</p><button type="button" class="flash-dismiss" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>@endif
            @yield('content')
        </main>

        <footer class="app-footer">
            <div class="footer-main-row">
                <div class="footer-brand">
                    <img src="{{ asset('images/kostly-logo.png') }}" alt="" class="footer-logo-image">
                    <div class="footer-brand-copy">
                        <strong>Kostly<span>.</span></strong>
                        <span>Cari kost yang pas, tinggal nyaman, fokus kuliah.</span>
                    </div>
                </div>
                <nav class="footer-links" aria-label="Navigasi footer">
                    <a href="{{ route('help') }}">Pusat bantuan</a>
                    <a href="{{ route('how-booking') }}">Cara booking</a>
                    <a href="{{ route('contact') }}">Hubungi kami</a>
                </nav>
            </div>
            <div class="footer-bottom-row">
                <span>© {{ date('Y') }} Kostly. Semua hak dilindungi.</span>
                <span>Peta © OpenStreetMap contributors</span>
            </div>
        </footer>
    </div>
</div>
<script>
(() => {
    const shell = document.getElementById('appShell');
    const toggle = document.getElementById('menuToggle');
    const closeBtn = document.getElementById('sidebarClose');
    const scrim = document.getElementById('sidebarScrim');
    const mobile = () => window.matchMedia('(max-width: 1024px)').matches;
    if (localStorage.getItem('kostly-sidebar') === 'collapsed' && !mobile()) shell.classList.add('sidebar-collapsed');
    const setOpen = (open) => {
        shell.classList.toggle('sidebar-open', open);
        document.body.classList.toggle('no-scroll', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', () => {
        if (mobile()) {
            setOpen(!shell.classList.contains('sidebar-open'));
        } else {
            const collapsed = shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('kostly-sidebar', collapsed ? 'collapsed' : 'open');
            toggle.setAttribute('aria-expanded', String(!collapsed));
        }
    });
    closeBtn.addEventListener('click', () => setOpen(false));
    scrim.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    window.addEventListener('resize', () => { if (!mobile()) setOpen(false); });
})();
</script>
@stack('scripts')
<script src="{{ asset('assets/js/kostly-ai.js') }}" defer></script>
</body>
</html>
