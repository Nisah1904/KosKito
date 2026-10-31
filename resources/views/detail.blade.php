@extends('layouts.app')

@section('title', $property->name . ' — Kostly.')

@section('content')

<section class="section detail-page">

    <a href="{{ route('listings') }}" class="back-link">
        ← Kembali ke daftar kost
    </a>

    @if($property->is_basic_info)
        <div class="info-notice" data-testid="basic-info-notice">
            <strong>Info dasar dari web.</strong> Nama dan alamat kost ini diambil dari halaman publik @if($property->source_url) (<a href="{{ $property->source_url }}" target="_blank" rel="noopener nofollow">{{ parse_url($property->source_url, PHP_URL_HOST) }}</a>)@endif. Harga, fasilitas, dan ketersediaan kamar belum dicek Kostly. Hubungi pengelola sebelum survei atau membayar.
        </div>
    @endif

    <div class="detail-grid">

        <div class="detail-photo">
            @if($property->photos->isNotEmpty())
                <div class="detail-gallery" data-testid="detail-gallery">
                    <img id="galleryMain" class="gallery-main" src="{{ $property->photos->first()->url }}" alt="Foto {{ $property->name }}">
                    @if($property->photos->count() > 1)
                        <div class="gallery-thumbs">
                            @foreach($property->photos as $photo)
                                <button type="button" class="gallery-thumb @if($loop->first) is-active @endif" data-src="{{ $photo->url }}" data-testid="gallery-thumb-{{ $loop->index }}">
                                    <img src="{{ $photo->url }}" alt="Foto {{ $loop->iteration }} {{ $property->name }}">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="property-image-placeholder detail-image-placeholder"><span>⌂</span><small>Foto kost belum tersedia</small></div>
            @endif
        </div>


        <div class="detail-info">

            <div class="detail-badges">
                <span class="property-type">{{ $property->typeLabel() }}</span>
                @if($property->isVerified())
                    <span class="badge badge-verified" data-testid="badge-verified">✓ Terverifikasi {{ $property->data_verified_at->translatedFormat('d M Y') }}</span>
                @else
                    <span class="badge badge-unverified" data-testid="badge-unverified">Belum terverifikasi</span>
                @endif
                <span class="badge badge-source">Sumber: {{ \App\Models\Property::SOURCE_LABELS[$property->source] ?? $property->source }}</span>
            </div>

            <h1 class="page-title" data-testid="detail-name">
                {{ $property->name }}
            </h1>

            <p class="property-location">
                ⌖ {{ $property->location }}
            </p>

            <div class="detail-price {{ $property->hasPrice() ? '' : 'is-unknown' }}" data-testid="detail-price">
                {{ $property->priceLabel() }}
                @if($property->hasPrice())<small>/bulan</small>@endif
            </div>

            @if($property->hasPrice())
            <div class="fee-note" data-testid="fee-note">
                <strong>Estimasi biaya dari kost: {{ \App\Services\Geo::rupiahStatic($property->monthlyCost()) }}/bulan</strong>
                <ul>
                    <li>Sewa: {{ $property->priceLabel() }}</li>
                    <li>Listrik: @if($property->electricity_included) sudah termasuk sewa @elseif($property->electricity_fee > 0) Rp{{ number_format($property->electricity_fee, 0, ',', '.') }}/bulan (ditagih pemilik) @else belum diketahui — tanyakan pemilik @endif</li>
                    <li>Internet: @if($property->internet_included) sudah termasuk sewa @elseif($property->internet_fee > 0) Rp{{ number_format($property->internet_fee, 0, ',', '.') }}/bulan (ditagih pemilik) @else belum diketahui — tanyakan pemilik @endif</li>
                </ul>
            </div>
            @endif

            <div class="detail-meta">
                <span class="{{ $property->is_basic_info ? 'rooms-unknown' : ($property->available_rooms > 0 ? 'rooms-ok' : 'rooms-full') }}" data-testid="detail-rooms">{{ $property->roomsLabel() }}</span>
            </div>

            <p class="detail-description">
                {{ filled($property->description) ? $property->description : 'Deskripsi kost belum diisi. Silakan periksa kembali informasi kepada pemilik.' }}
            </p>

            @if(filled($property->rules))
                <h3>Aturan kost</h3>
                <p class="detail-rules">{{ $property->rules }}</p>
            @endif

            <h3>Fasilitas</h3>

            <div class="property-facilities detail-facilities">
                @forelse($property->facilities ?? [] as $facility)
                    <span>{{ ucfirst($facility) }}</span>
                @empty
                    <span>Informasi fasilitas belum diisi</span>
                @endforelse
            </div>

            <h3>Lokasi & jarak</h3>
            <p class="detail-address"><strong>Alamat:</strong> {{ $property->address ?? $property->location }}</p>
            @if($distances->isNotEmpty())
                <ul class="distance-list" data-testid="distance-list">
                    @foreach($distances as $item)
                        <li>±{{ \App\Services\Geo::formatKm($item['km']) }} garis lurus ke {{ $item['campus']->name }} ({{ $item['campus']->city }})</li>
                    @endforeach
                </ul>
                <p class="distance-disclaimer">Jarak adalah <em>garis lurus</em> dari koordinat (bukan jarak rute jalan). Estimasi waktu perjalanan tidak ditampilkan karena tidak dihitung dari sumber rute.</p>
            @else
                <p class="distance-disclaimer" data-testid="no-coords-note">Jarak ke kampus belum bisa dihitung karena koordinat kost belum diisi. Gunakan alamat di atas untuk mencari lokasinya.</p>
            @endif

            <h3>Kontak pemilik</h3>
            @if(filled($property->phone))
                @if(auth()->check())
                    <p class="detail-phone" data-testid="owner-phone">{{ $property->phone }} <small>({{ $property->owner?->name ?? 'Pemilik' }})</small></p>
                @else
                    <p class="detail-phone" data-testid="owner-phone-gated">🔒 Kontak pemilik hanya tampil setelah <a href="{{ route('login') }}">masuk</a>.</p>
                @endif
            @elseif($property->source_url)
                <p class="detail-phone">Kontak tersedia di halaman sumber: <a href="{{ $property->source_url }}" target="_blank" rel="noopener nofollow" data-testid="source-link">{{ parse_url($property->source_url, PHP_URL_HOST) }} ↗</a></p>
            @else
                <p class="detail-phone">Nomor kontak belum dicantumkan pemilik.</p>
            @endif

            <div class="detail-actions">
                @if($property->is_basic_info || (!$property->canBeBooked() && $property->available_rooms > 0))
                    <span class="primary-button is-disabled" data-testid="book-unavailable">Booking online belum tersedia</span>
                @elseif($property->available_rooms > 0)
                    @if(!auth()->check())
                        <a href="{{ route('login') }}" class="primary-button" data-testid="login-to-book">Masuk untuk Booking</a>
                    @elseif(auth()->user()->isTenant())
                        <a href="{{ route('booking', $property->id) }}" class="primary-button" data-testid="book-now">Ajukan Booking</a>
                    @endif
                @else
                    <span class="primary-button is-disabled" data-testid="book-full">Kamar Penuh</span>
                @endif

                <form method="POST" action="{{ route('compare.add') }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <button type="submit" class="secondary-button" data-testid="detail-compare">
                        {{ $inCompare ? '✓ Di perbandingan — tambah lagi?' : 'Bandingkan Kost' }}
                    </button>
                </form>

                @if(auth()->check() && auth()->user()->isTenant())
                    <form method="POST" action="{{ route('favorites.toggle', $property->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="secondary-button" data-testid="detail-favorite">
                            {{ $isFavorited ? '♥ Hapus dari Favorit' : '♡ Simpan ke Favorit' }}
                        </button>
                    </form>
                @endif
            </div>

        </div>

    </div>

    <section class="detail-map-panel">
        <div class="detail-map-heading">
            <div>
                <span class="section-label">LOKASI KOST</span>
                <h2 class="section-title">Lihat lokasi di peta</h2>
                <p class="page-description">Peta & tile © OpenStreetMap — gratis tanpa API key. Periksa pin sebelum berkunjung.</p>
            </div>
            @if($property->latitude !== null && $property->longitude !== null)
                <a href="https://www.openstreetmap.org/?mlat={{ $property->latitude }}&mlon={{ $property->longitude }}#map=16/{{ $property->latitude }}/{{ $property->longitude }}" target="_blank" rel="noopener noreferrer" class="primary-button" data-testid="open-osm">Buka di OpenStreetMap ↗</a>
            @endif
        </div>
        @if($property->latitude !== null && $property->longitude !== null)
            <div id="detailMap" class="detail-map-frame" data-testid="detail-map"></div>
        @else
            <div class="detail-map-placeholder" data-testid="detail-map-placeholder">
                <span class="map-placeholder-icon">⌖</span>
                <strong>Koordinat belum diisi</strong>
                <p>Peta hanya ditampilkan jika kost memiliki koordinat valid. Gunakan alamat di atas untuk mencari lokasinya — kami tidak menampilkan pin tebakan.</p>
            </div>
        @endif
    </section>

</section>

@if($property->latitude !== null && $property->longitude !== null)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const el = document.getElementById('detailMap');
    if (!el || !window.L) return;
    const lat = {{ $property->latitude }};
    const lng = {{ $property->longitude }};
    const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    L.marker([lat, lng], { icon: L.divIcon({ className: 'kostly-map-pin-wrap', html: '<span class="kostly-map-pin">⌂</span>', iconSize: [30, 38], iconAnchor: [15, 34], popupAnchor: [0, -30] }) }).addTo(map).bindPopup('{{ $property->name }}');
    const campuses = @json($distances->map(fn ($d) => ['name' => $d['campus']->short_name, 'lat' => $d['campus']->latitude, 'lng' => $d['campus']->longitude]));
    campuses.forEach(c => {
        L.marker([c.lat, c.lng], { icon: L.divIcon({ className: 'kostly-map-pin-wrap', html: '<span class="kostly-map-pin kostly-map-pin-campus">🎓</span>', iconSize: [30, 38], iconAnchor: [15, 34] }) }).addTo(map).bindPopup('<strong>' + c.name + '</strong><br>Kampus');
    });
    setTimeout(() => map.invalidateSize(), 150);
})();
</script>
<script>
document.addEventListener('click', function (e) {
    const thumb = e.target.closest('.gallery-thumb');
    if (!thumb) return;
    document.getElementById('galleryMain').src = thumb.dataset.src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('is-active'));
    thumb.classList.add('is-active');
});
</script>
@endif

@endsection
