@extends('layouts.app')

@section('title', 'Cari Kost — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">

        <div class="section-label">
            EXPLORE KOST
        </div>

        <h1 class="page-title">
            Cari kost yang sesuai.
        </h1>

        <p class="page-description">
            Kost dari pemilik, admin, dan direktori publik. Lencana "Terverifikasi" berarti datanya sudah dicek; "Info dari web" berarti baru nama dan alamat.
        </p>

        @if($selectedCampus)
            <div class="active-filter-note" data-testid="active-campus-note">
                Menampilkan kost terkait <strong>{{ $selectedCampus->name }}</strong> ({{ $selectedCampus->city }}) —
                <a href="{{ route('listings') }}">hapus filter kampus</a>
            </div>
        @endif

        <form action="{{ route('listings') }}"
              method="GET"
              class="filter-panel"
              data-testid="listing-filter-form">

            <div class="filter-row">
                <label class="filter-field">
                    <span>Kata kunci (nama / area / alamat)</span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Contoh: Srijaya Negara">
                </label>

                <label class="filter-field">
                    <span>Kampus tujuan</span>
                    <select name="campus_id" data-testid="filter-campus">
                        <option value="">Semua kampus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected(request('campus_id') == $campus->id)>{{ $campus->short_name }} — {{ $campus->city }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Tipe kost</span>
                    <select name="type" data-testid="filter-type">
                        <option value="">Semua tipe</option>
                        <option value="putra" @selected(request('type') == 'putra')>Putra</option>
                        <option value="putri" @selected(request('type') == 'putri')>Putri</option>
                        <option value="campur" @selected(request('type') == 'campur')>Campur</option>
                    </select>
                </label>
            </div>

            <div class="filter-row">
                <label class="filter-field">
                    <span>Harga minimum (Rp/bulan)</span>
                    <select name="min_price" data-testid="filter-min-price">
                        <option value="">Tanpa batas bawah</option>
                        @foreach([300000, 500000, 750000, 1000000] as $min)
                            <option value="{{ $min }}" @selected(request('min_price') == $min)>≥ Rp{{ number_format($min, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Harga maksimum (Rp/bulan)</span>
                    <select name="max_price" data-testid="filter-max-price">
                        <option value="">Tanpa batas atas</option>
                        @foreach([750000, 1000000, 1500000, 2000000] as $max)
                            <option value="{{ $max }}" @selected(request('max_price') == $max)>≤ Rp{{ number_format($max, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Urutkan</span>
                    <select name="sort" data-testid="filter-sort">
                        <option value="latest" @selected(request('sort') == 'latest' || !request('sort'))>Terbaru / relevansi</option>
                        <option value="price_asc" @selected(request('sort') == 'price_asc')>Harga termurah</option>
                        <option value="price_desc" @selected(request('sort') == 'price_desc')>Harga termahal</option>
                        <option value="distance" @selected(request('sort') == 'distance')>Jarak terdekat (butuh kampus &amp; koordinat)</option>
                    </select>
                </label>
            </div>

            <div class="filter-row filter-facilities">
                <span class="filter-label">Fasilitas wajib:</span>
                @foreach($facilityOptions as $option)
                    <label class="check-chip">
                        <input type="checkbox" name="facilities[]" value="{{ $option }}" @checked(in_array($option, (array) request('facilities', [])))>
                        <span>{{ ucfirst($option) }}</span>
                    </label>
                @endforeach
                <label class="check-chip check-chip-strong">
                    <input type="checkbox" name="available" value="1" @checked(request('available'))>
                    <span>Harus ada kamar kosong</span>
                </label>
            </div>

            <div class="filter-actions">
                <button type="submit" class="primary-button" data-testid="filter-apply">Terapkan Filter</button>
                <a href="{{ route('listings') }}" class="secondary-button">Reset</a>
            </div>

        </form>
    </div>
</section>


<section class="section">

    <div class="results-heading">
        <div>
            <div class="section-label">
                HASIL PENCARIAN
            </div>

            <h2 class="section-title">
                Pilihan kost
            </h2>
        </div>

        <span class="result-count" data-testid="result-count">
            {{ $properties->total() }} pilihan
        </span>
    </div>


    <div class="property-grid">

        @forelse($properties as $property)

            @include('partials.property-card', ['property' => $property])

        @empty

            <div class="empty-state" data-testid="empty-state">
                <h3>
                    Tidak ada kost yang cocok dengan filter ini.
                </h3>

                <p>
                    Data kost masih terbatas — kami tidak menampilkan data karangan. Coba longgarkan filter, atau lihat kampus lain.
                </p>

                <a href="{{ route('listings') }}" class="primary-button" data-testid="empty-reset">
                    Reset Pencarian
                </a>
            </div>

        @endforelse

    </div>

    @if($properties->lastPage() > 1)
        {{ $properties->links('pagination') }}
    @endif


</section>

<section class="listing-map-panel" aria-labelledby="listing-map-title">

    <div class="listing-map-heading">
        <div>
            <div class="section-label">
                PETA LOKASI
            </div>

            <h2 class="section-title" id="listing-map-title">
                Lokasi kost di peta
            </h2>

            <p class="page-description">
                Pin hanya ditampilkan untuk kost yang sudah memiliki koordinat valid. Kampus tujuan ditandai pin emas.
            </p>
        </div>
    </div>

    <div id="kostly-listings-map" class="listing-map-frame" role="region" aria-label="Peta lokasi kost" data-testid="listings-map"></div>

    <p class="listing-map-note">
        Jika peta gagal dimuat, alamat lengkap tetap tersedia pada tiap halaman detail kost.
    </p>

</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const mapElement = document.getElementById('kostly-listings-map');

    if (!mapElement) {
        return;
    }

    if (!window.L) {
        mapElement.innerHTML = '<p class="map-load-message">Peta memerlukan koneksi internet. Informasi alamat tetap tersedia di tiap detail kost.</p>';
        return;
    }

    const properties = {{ \Illuminate\Support\Js::from($properties->getCollection()->map(fn ($p) => [
        'name' => $p->name,
        'latitude' => $p->latitude,
        'longitude' => $p->longitude,
        'url' => route('detail', $p->id),
    ])->values()) }};

    const campuses = {{ \Illuminate\Support\Js::from(collect($selectedCampus ? [$selectedCampus] : [])->map(fn ($c) => [
        'name' => $c->short_name, 'latitude' => $c->latitude, 'longitude' => $c->longitude,
    ])->values()) }};

    const valid = (list) => list.filter(function (item) {
        const lat = Number(item.latitude);
        const lng = Number(item.longitude);
        return Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
    });

    const map = L.map(mapElement).setView([-2.9909, 104.7566], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = [];

    valid(properties).forEach(function (property) {
        const point = [Number(property.latitude), Number(property.longitude)];
        bounds.push(point);
        L.marker(point).addTo(map).bindPopup('<strong>' + property.name + '</strong><br><a href="' + property.url + '">Lihat detail kost →</a>');
    });

    valid(campuses).forEach(function (campus) {
        const point = [Number(campus.latitude), Number(campus.longitude)];
        bounds.push(point);
        L.marker(point, { icon: L.divIcon({ className: 'kostly-map-pin-wrap', html: '<span class="kostly-map-pin kostly-map-pin-campus">🎓</span>', iconSize: [30, 38], iconAnchor: [15, 34] }) })
            .addTo(map).bindPopup('<strong>' + campus.name + '</strong><br><span>Kampus tujuan</span>');
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
    } else {
        mapElement.innerHTML = '<div class="map-load-message"><strong>Koordinat kost belum tersedia.</strong><br>Isi latitude dan longitude pada data kost agar pin muncul di peta.</div>';
    }

});
</script>

@endsection
