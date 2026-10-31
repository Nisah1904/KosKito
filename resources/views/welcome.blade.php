@extends('layouts.app')

@section('title', 'Beranda — Kostly.')

@section('content')
<div class="dashboard-home">
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">KOSTLY · PENCARIAN KOST PALEMBANG</span>
            <h1>Cari kost yang pas,<br>tinggal nyaman,<br><em>fokus kuliah.</em></h1>
            <p>Mulai dari kampus tujuanmu di Palembang — Smart Match menyocokkan anggaran, jarak, dan fasilitas; kalkulator menghitung total biaya hidup bulanan; booking diajukan langsung ke pemilik.</p>
            <div class="dashboard-hero-actions">
                <a href="{{ route('listings') }}" class="dashboard-button dashboard-button-primary" data-testid="hero-explore">Jelajahi kost <span>→</span></a>
                <a href="{{ route('recommendations') }}" class="dashboard-button dashboard-button-outline" data-testid="hero-smart-match">Coba Smart Match <span>✳</span></a>
            </div>
            <div class="dashboard-hero-note"><span class="status-dot"></span>
                @if($totalApproved > 0)
                    {{ $totalApproved }} kost di Palembang & Indralaya tercatat di Kostly
                @else
                    Belum ada kost terverifikasi — pemilik dapat mendaftarkan kost melalui dashboard
                @endif
            </div>
        </div>
        <div class="dashboard-hero-visual" role="img" aria-label="Inspirasi ruang tinggal yang nyaman">
            <div class="hero-visual-caption"><span>01 / FIND YOUR PLACE</span><strong>Ruang untuk mulai<br>cerita baru.</strong></div>
        </div>
    </section>

    <section class="dashboard-search-wrap" aria-label="Pencarian kost">
        <div class="dashboard-search-heading"><span class="dashboard-eyebrow">MULAI DARI KAMPUS</span><h2>Temukan tempat yang sesuai</h2><p>Pilih kampus tujuan, masukkan anggaran, dan tentukan tipe kost. Pencarian juga bisa memakai alamat atau titik lain lewat kolom kata kunci.</p></div>
        <form action="{{ route('listings') }}" method="GET" class="dashboard-search-form" data-testid="home-search-form">
            <label class="dashboard-field"><span>Nama, area, atau alamat</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Contoh: Srijaya Negara" aria-label="Kata kunci"></label>
            <label class="dashboard-field"><span>Kampus tujuan</span><select name="campus_id" aria-label="Kampus tujuan" data-testid="home-campus-select"><option value="">Semua kampus</option>@foreach($campuses as $campus)<option value="{{ $campus->id }}" @selected(request('campus_id') == $campus->id)>{{ $campus->short_name }} — {{ $campus->name }}</option>@endforeach</select></label>
            <label class="dashboard-field"><span>Budget sewa maksimal</span><select name="max_price" aria-label="Budget maksimal" data-testid="home-budget-select"><option value="">Semua budget</option><option value="750000" @selected(request('max_price') == '750000')>Rp750 ribu/bulan</option><option value="1000000" @selected(request('max_price') == '1000000')>Rp1 juta/bulan</option><option value="1500000" @selected(request('max_price') == '1500000')>Rp1,5 juta/bulan</option><option value="2000000" @selected(request('max_price') == '2000000')>Rp2 juta/bulan</option></select></label>
            <label class="dashboard-field"><span>Tipe penghuni</span><select name="type" aria-label="Tipe penghuni" data-testid="home-type-select"><option value="">Semua tipe</option><option value="putri" @selected(request('type') === 'putri')>Putri</option><option value="putra" @selected(request('type') === 'putra')>Putra</option><option value="campur" @selected(request('type') === 'campur')>Campur</option></select></label>
            <button type="submit" class="dashboard-search-button" data-testid="home-search-submit"><span>⌕</span> Cari kost</button>
        </form>
    </section>

    <section class="dashboard-content-grid">
        <div class="dashboard-listing-section">
            <div class="dashboard-section-heading"><div><span class="dashboard-eyebrow">DAFTAR KOST</span><h2>Kost terbaru</h2><p>Data dari pemilik kost, admin, dan direktori publik.</p></div><a href="{{ route('listings') }}" class="dashboard-text-link">Lihat daftar <span>→</span></a></div>
            <div class="property-grid home-featured-grid">
                @forelse($featured as $property)
                    @include('partials.property-card', ['property' => $property])
                @empty
                    <div class="empty-state">
                        <h3>Belum ada kost terverifikasi.</h3>
                        <p>Listing baru muncul di sini setelah admin menyetujui pengajuan pemilik atau hasil impor data. Kami tidak menampilkan data yang belum diperiksa.</p>
                        <a href="{{ route('register') }}" class="primary-button">Daftar sebagai pemilik kost</a>
                    </div>
                @endforelse
            </div>
            <p class="dashboard-data-note">Kost bertanda <strong>Info dari web</strong> berisi nama dan alamat dari direktori publik, belum dicek langsung. Pemilik bisa mendaftar untuk melengkapi data kostnya.</p>
        </div>
        <aside class="dashboard-map-column" id="peta-kost">
            <div class="dashboard-map-heading"><div><span class="dashboard-eyebrow">PETA INTERAKTIF</span><h2>Titik lokasi kost</h2></div><span class="dashboard-map-count">{{ count($mapPoints) }} titik</span></div>
            <div id="kostlyMap" class="dashboard-map" aria-label="Peta interaktif lokasi kost"><div class="map-loading">Memuat peta dan titik lokasi…</div></div>
            <div class="dashboard-map-legend"><span><i></i> Kost (pin hijau) · Kampus (pin emas)</span><a href="https://www.openstreetmap.org/#map=12/-2.99/104.75" target="_blank" rel="noopener">© OpenStreetMap ↗</a></div>
            <div id="mapStatus" class="dashboard-map-status" role="status">Hanya kost dengan koordinat yang ditampilkan sebagai pin. Jika peta gagal dimuat, alamat tetap tersedia di tiap detail kost.</div>
        </aside>
    </section>

    <section class="campus-section" aria-labelledby="campus-title">
        <div class="dashboard-section-heading"><div><span class="dashboard-eyebrow">KAMPUS TUJUAN</span><h2 id="campus-title">{{ $campuses->count() }} kampus Palembang & sekitarnya</h2><p>Unsri sengaja dipisah: Kampus Bukit (Kota Palembang) dan Kampus Indralaya (Kabupaten Ogan Ilir) — jangan sampai tertukar.</p></div></div>
        <div class="campus-grid" data-testid="campus-grid">
            @foreach($campuses as $campus)
                <a href="{{ route('listings', ['campus_id' => $campus->id]) }}" class="campus-card" data-testid="campus-card-{{ $campus->id }}">
                    <span class="campus-card-count">{{ $campus->approved_properties_count }} kost</span>
                    <strong>{{ $campus->short_name }}</strong>
                    <small>{{ $campus->name }}</small>
                    <em>{{ $campus->city }}</em>
                </a>
            @endforeach
        </div>
    </section>

    <section class="dashboard-tools-section"><div class="dashboard-section-heading"><div><span class="dashboard-eyebrow">ALAT BANTU</span><h2>Ambil keputusan lebih mudah</h2><p>Gunakan fitur Kostly sesuai kebutuhanmu.</p></div></div><div class="dashboard-tools-grid">
        <a href="{{ route('recommendations') }}" class="dashboard-tool-card" data-testid="tool-smart-match"><span class="dashboard-tool-icon">✳</span><span class="dashboard-tool-label">SMART MATCH</span><h3>Cari yang paling sesuai</h3><p>Skor kecocokan nyata dari anggaran, jarak, dan fasilitasmu — lengkap dengan alasannya.</p><span class="dashboard-tool-link">Lihat rekomendasi →</span></a>
        <a href="{{ route('compare') }}" class="dashboard-tool-card" data-testid="tool-compare"><span class="dashboard-tool-icon">⇄</span><span class="dashboard-tool-label">PERBANDINGAN</span><h3>Bandingkan 2–3 pilihan</h3><p>Tinjau harga, jarak, fasilitas, dan estimasi biaya total berdampingan.</p><span class="dashboard-tool-link">Bandingkan kost →</span></a>
        <a href="{{ route('cost-planner') }}" class="dashboard-tool-card" data-testid="tool-cost"><span class="dashboard-tool-icon">Rp</span><span class="dashboard-tool-label">KALKULATOR</span><h3>Atur budget bulanan</h3><p>Hitung estimasi biaya hidup: sewa, listrik, internet, makan, transport, dan lainnya.</p><span class="dashboard-tool-link">Hitung biaya →</span></a>
    </div></section>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const target = document.getElementById('kostlyMap');
    const status = document.getElementById('mapStatus');
    if (!target) return;
    if (!window.L) {
        if (status) status.textContent = 'Peta tidak dapat dimuat (kemungkinan tanpa internet). Alamat lengkap tetap tersedia di setiap halaman detail kost.';
        return;
    }
    const points = @json($mapPoints);
    const campuses = @json($mapCampuses);
    const map = L.map(target, { scrollWheelZoom: false }).setView([-2.99, 104.74], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    const kostIcon = L.divIcon({ className: 'kostly-map-pin-wrap', html: '<span class="kostly-map-pin">⌂</span>', iconSize: [30, 38], iconAnchor: [15, 34], popupAnchor: [0, -30] });
    const campusIcon = L.divIcon({ className: 'kostly-map-pin-wrap', html: '<span class="kostly-map-pin kostly-map-pin-campus">🎓</span>', iconSize: [30, 38], iconAnchor: [15, 34], popupAnchor: [0, -30] });
    const bounds = [];
    points.forEach(p => {
        L.marker([p.lat, p.lng], { icon: kostIcon }).addTo(map)
            .bindPopup('<strong>' + p.name + '</strong><br><a href="' + p.url + '">Lihat detail kost →</a>');
        bounds.push([p.lat, p.lng]);
    });
    campuses.forEach(c => {
        L.marker([c.lat, c.lng], { icon: campusIcon }).addTo(map)
            .bindPopup('<strong>' + c.name + '</strong><br><span>Kampus</span>');
        bounds.push([c.lat, c.lng]);
    });
    if (bounds.length) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
    } else {
        status.textContent = 'Belum ada kost dengan koordinat. Pin akan muncul setelah pemilik/admin mengisi latitude & longitude.';
    }
    setTimeout(() => map.invalidateSize(), 150);
})();
</script>

@endsection
