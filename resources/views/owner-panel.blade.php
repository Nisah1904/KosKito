@extends('layouts.app')

@section('title', ($isAdmin ? 'Kelola Kost — Admin' : 'Pemilik Kost') . ' — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">{{ $isAdmin ? 'ADMIN' : 'AREA PEMILIK' }}</div>
        <h1 class="page-title">{{ $isAdmin ? 'Kelola semua kost' : 'Area Pemilik Kost' }}</h1>
        <p class="page-description">{{ $isAdmin ? 'Tambah, ubah, nonaktifkan, atau hapus listing kost mana pun. Listing yang ditambahkan admin langsung tampil publik.' : 'Kelola properti, kamar, dan booking kost kamu. Kost baru diperiksa admin sebelum tampil publik.' }}</p>
    </div>
</section>

<section class="section">

    @if($isAdmin)
        @include('partials.admin-menu')
    @else
        <nav class="dashboard-menu" aria-label="Menu pemilik">
            <a href="{{ route('owner.properties') }}" class="dashboard-menu-item {{ request()->routeIs('owner.properties') ? 'is-active' : '' }}">Properti Saya</a>
            <a href="{{ route('owner.properties.create') }}" class="dashboard-menu-item {{ request()->routeIs('owner.properties.create') ? 'is-active' : '' }}">+ Tambah Kost</a>
            <a href="{{ route('owner.rooms') }}" class="dashboard-menu-item {{ request()->routeIs('owner.rooms') ? 'is-active' : '' }}">Kamar</a>
            <a href="{{ route('owner.bookings') }}" class="dashboard-menu-item {{ request()->routeIs('owner.bookings') ? 'is-active' : '' }}">Booking Masuk</a>
            <a href="{{ route('owner.statistics') }}" class="dashboard-menu-item {{ request()->routeIs('owner.statistics') ? 'is-active' : '' }}">Statistik</a>
        </nav>
    @endif

    @if($page === 'properties')

        <div class="results-heading">
            <div>
                <div class="section-label">{{ $isAdmin ? 'SEMUA LISTING' : 'PROPERTI SAYA' }}</div>
                <h2 class="section-title">{{ $isAdmin ? $properties->total() . ' kost di database' : 'Kost yang kamu kelola' }}</h2>
            </div>
            <a href="{{ route($rp . '.create') }}" class="primary-button" data-testid="owner-add-button">+ Tambah Kost</a>
        </div>

        @if($isAdmin)
            <form method="GET" action="{{ route('admin.kost') }}" class="inline-search" data-testid="admin-kost-search">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau alamat kost…" data-testid="admin-kost-search-input">
                <button type="submit" class="secondary-button">Cari</button>
            </form>
        @endif

        @if($properties->isNotEmpty())
            <div class="owner-property-list">
                @foreach($properties as $property)
                    <article class="admin-card" data-testid="owner-property-{{ $property->id }}">
                        <div class="admin-card-main">
                            @if($photo = $property->primaryPhoto())
                                <img class="admin-card-photo" src="{{ $photo->url }}" alt="Foto {{ $property->name }}">
                            @else
                                <div class="admin-card-photo property-image-placeholder"><span>⌂</span></div>
                            @endif
                            <div class="admin-card-body">
                                <div class="property-card-top">
                                    <span class="property-type">{{ $property->typeLabel() }}</span>
                                    <span class="status-badge status-{{ $property->status }}" data-testid="owner-property-status-{{ $property->id }}">{{ \App\Models\Property::STATUS_LABELS[$property->status] }}</span>
                                    @if($property->is_basic_info)<span class="badge badge-unverified">Info dasar dari web</span>@endif
                                </div>
                                <h3>{{ $property->name }}</h3>
                                <p>{{ $property->address ?? $property->location }}</p>
                                <p class="admin-card-meta">{{ $property->priceLabel() }}{{ $property->hasPrice() ? '/bulan' : '' }} · {{ $property->roomsLabel() }}@if($isAdmin) · Pemilik: {{ $property->owner?->name ?? '—' }}@endif</p>
                                @if($property->status === 'rejected' && filled($property->rejection_reason))
                                    <p class="rejection-reason" data-testid="owner-rejection-reason-{{ $property->id }}"><strong>Alasan penolakan admin:</strong> {{ $property->rejection_reason }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="inline-actions">
                            <a href="{{ route('detail', $property->id) }}" class="small-button secondary" data-testid="owner-view-{{ $property->id }}">Lihat</a>
                            <a href="{{ route($rp . '.edit', $property->id) }}" class="small-button" data-testid="owner-edit-{{ $property->id }}">Edit</a>
                            @if(in_array($property->status, ['approved', 'inactive']))
                                <form method="POST" action="{{ route($rp . '.toggle', $property->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="small-button secondary" data-testid="owner-toggle-{{ $property->id }}">{{ $property->status === 'approved' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route($rp . '.destroy', $property->id) }}" class="inline-form" onsubmit="return confirm('Hapus kost ini secara permanen?')">
                                @csrf
                                <button type="submit" class="small-button danger" data-testid="owner-delete-{{ $property->id }}">Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $properties->links('pagination') }}
        @else
            <div class="empty-state" data-testid="owner-properties-empty">
                <h3>Belum ada kost.</h3>
                <p>{{ $isAdmin ? 'Tidak ada listing yang cocok.' : 'Tambahkan properti pertama kamu. Nanti diverifikasi admin sebelum tampil publik.' }}</p>
                <a href="{{ route($rp . '.create') }}" class="primary-button">Tambah Kost</a>
            </div>
        @endif

    @elseif($page === 'create' || $page === 'edit')

        @php
            $isEdit = $page === 'edit';
            $property = $property ?? null;
            $formAction = $isEdit ? route($rp . '.update', $property->id) : route($rp . '.store');
            $currentFacilities = $property->facilities ?? [];
            $req = $isAdmin ? '' : ' *';
        @endphp

        <div class="planner-card form-card">
            <a href="{{ route($rp) }}" class="back-link">← Kembali ke daftar</a>
            <div class="section-label">{{ $isEdit ? 'EDIT KOST' : 'KOST BARU' }}</div>
            <h2 class="section-title">{{ $isEdit ? 'Perbarui ' . $property->name : 'Tambahkan kost' }}</h2>
            <p class="section-subtitle">
                @if($isAdmin)
                    Kolom harga dan jumlah kamar boleh kosong bila belum diketahui. Listing akan ditandai "info dasar" sampai datanya lengkap.
                @else
                    {{ $isEdit ? 'Perubahan tersimpan langsung. Listing yang ditolak akan dikirim ulang ke verifikasi admin.' : 'Kost baru masuk ke verifikasi admin sebelum tampil di pencarian publik.' }}
                @endif
            </p>

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" data-testid="property-form">
                @csrf

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama kost *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $property->name ?? '') }}" required data-testid="property-name">
                    </div>
                    <div class="form-group">
                        <label>Harga per bulan (Rp){{ $req }}</label>
                        <input type="number" name="price" class="form-control" min="50000" value="{{ old('price', ($property?->price ?: '')) }}" @if(!$isAdmin) required @endif placeholder="{{ $isAdmin ? 'Kosongkan bila belum diketahui' : 'Contoh: 900000' }}" data-testid="property-price">
                    </div>
                    <div class="form-group">
                        <label>Kawasan / area *</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location', $property->location ?? '') }}" placeholder="Contoh: Bukit Lama, Ilir Barat I" required data-testid="property-location">
                    </div>
                    <div class="form-group">
                        <label>Alamat lengkap *</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $property->address ?? '') }}" placeholder="Jalan, gang, kelurahan, kecamatan" required data-testid="property-address">
                    </div>
                    <div class="form-group">
                        <label>Kampus terdekat</label>
                        <select name="campus_id" class="form-control" data-testid="property-campus">
                            <option value="">Pilih kampus</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}" @selected(old('campus_id', $property->campus_id ?? null) == $campus->id)>{{ $campus->short_name }} ({{ $campus->city }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipe kost *</label>
                        <select name="type" class="form-control" required data-testid="property-type">
                            @foreach(\App\Models\Property::TYPE_LABELS as $value => $label)
                                @continue(!$isAdmin && $value === 'unknown')
                                <option value="{{ $value }}" @selected(old('type', $property->type ?? 'putri') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah kamar{{ $req }}</label>
                        <input type="number" name="rooms" class="form-control" min="{{ $isAdmin ? 0 : 1 }}" max="200" value="{{ old('rooms', ($property?->rooms ?: '')) }}" @if(!$isAdmin) required @endif data-testid="property-rooms">
                    </div>
                    <div class="form-group">
                        <label>Kamar tersedia saat ini{{ $req }}</label>
                        <input type="number" name="available_rooms" class="form-control" min="0" max="200" value="{{ old('available_rooms', $property->available_rooms ?? '') }}" @if(!$isAdmin) required @endif data-testid="property-available">
                    </div>
                    <div class="form-group">
                        <label>Listrik</label>
                        <label class="check-chip"><input type="checkbox" name="electricity_included" value="1" @checked(old('electricity_included', $property->electricity_included ?? false))><span>Sudah termasuk sewa</span></label>
                        <input type="number" name="electricity_fee" class="form-control" min="0" value="{{ old('electricity_fee', ($property?->electricity_fee ?: '')) }}" placeholder="Tarif bulanan bila ditagih terpisah" data-testid="property-electricity">
                    </div>
                    <div class="form-group">
                        <label>Internet</label>
                        <label class="check-chip"><input type="checkbox" name="internet_included" value="1" @checked(old('internet_included', $property->internet_included ?? false))><span>Sudah termasuk sewa</span></label>
                        <input type="number" name="internet_fee" class="form-control" min="0" value="{{ old('internet_fee', ($property?->internet_fee ?: '')) }}" placeholder="Tarif bulanan bila ditagih terpisah" data-testid="property-internet">
                    </div>
                    <div class="form-group">
                        <label>Koordinat (untuk peta & jarak)</label>
                        <div class="input-pair">
                            <input type="text" name="latitude" class="form-control" value="{{ old('latitude', $property->latitude ?? '') }}" placeholder="Latitude, mis. -2.9885" data-testid="property-latitude">
                            <input type="text" name="longitude" class="form-control" value="{{ old('longitude', $property->longitude ?? '') }}" placeholder="Longitude, mis. 104.7289" data-testid="property-longitude">
                        </div>
                        <small>Ambil titik dari aplikasi peta tepat di lokasi kost.</small>
                    </div>
                    <div class="form-group">
                        <label>Nomor kontak</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $property->phone ?? '') }}" placeholder="Contoh: 0812-3456-7890" data-testid="property-phone">
                    </div>
                    @if($isAdmin)
                        <div class="form-group">
                            <label>Pemilik listing</label>
                            <select name="owner_id" class="form-control" data-testid="property-owner">
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected(old('owner_id', $property->owner_id ?? auth()->id()) == $owner->id)>{{ $owner->name }} ({{ $owner->role }} · {{ $owner->email }})</option>
                                @endforeach
                            </select>
                            <small>Pindahkan ke akun pemilik asli agar ia bisa mengelola kost ini sendiri dan menerima booking.</small>
                        </div>
                        <div class="form-group">
                            <label>Link sumber data</label>
                            <input type="url" name="source_url" class="form-control" value="{{ old('source_url', $property->source_url ?? '') }}" placeholder="https://…" data-testid="property-source-url">
                            <label class="check-chip"><input type="checkbox" name="mark_verified" value="1" @checked(old('mark_verified', $property !== null && $property->data_verified_at !== null)) data-testid="property-mark-verified"><span>Data sudah dicek langsung (terverifikasi)</span></label>
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label>Fasilitas{{ $isAdmin ? '' : ' * (pilih minimal satu)' }}</label>
                    <div class="filter-checks" data-testid="property-facilities">
                        @foreach($facilityOptions as $option)
                            <label class="check-chip">
                                <input type="checkbox" name="facilities[]" value="{{ $option }}" @checked(in_array($option, old('facilities', $currentFacilities)))>
                                <span>{{ ucfirst($option) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <input type="text" name="facilities_extra" class="form-control" value="{{ old('facilities_extra') }}" placeholder="Fasilitas lain (pisahkan dengan koma)" data-testid="property-facilities-extra">
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4" maxlength="2000" placeholder="Kondisi kost, lingkungan, dan keunggulannya" data-testid="property-description">{{ old('description', $property->description ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Aturan kost</label>
                    <textarea name="rules" class="form-control" rows="2" maxlength="1000" placeholder="Contoh: jam malam 23.00, tamu di ruang tengah" data-testid="property-rules">{{ old('rules', $property->rules ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Foto kost (maksimal 4, jpg/png/webp, maks. 2 MB)</label>
                    <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp" class="form-control" data-testid="property-photos">
                    @if($isEdit && $property->photos->isNotEmpty())
                        <div class="photo-strip">
                            @foreach($property->photos as $photo)
                                <figure>
                                    <img src="{{ $photo->url }}" alt="Foto {{ $loop->iteration }}">
                                    <button type="submit" form="delete-photo-{{ $photo->id }}" class="photo-delete" aria-label="Hapus foto" data-testid="photo-delete-{{ $photo->id }}">×</button>
                                </figure>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($errors->any())
                    <div class="form-error" data-testid="property-errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <button type="submit" class="full-button" data-testid="property-submit">
                    {{ $isEdit ? 'Simpan perubahan' : ($isAdmin ? 'Tambah kost' : 'Ajukan kost') }}
                </button>
            </form>

            @if($isEdit)
                @foreach($property->photos as $photo)
                    <form id="delete-photo-{{ $photo->id }}" method="POST" action="{{ route($rp . '.photo.destroy', [$property->id, $photo->id]) }}" onsubmit="return confirm('Hapus foto ini?')" hidden>@csrf</form>
                @endforeach
            @endif
        </div>

    @elseif($page === 'rooms')

        <div class="section-header">
            <div>
                <div class="section-label">ROOM MANAGEMENT</div>
                <h2 class="section-title">Ketersediaan kamar</h2>
            </div>
        </div>


        @if($properties->isNotEmpty())

            <div class="admin-list">

                @foreach($properties as $property)

                    <div class="admin-card" data-testid="rooms-property-{{ $property->id }}">

                        <div class="admin-card-main">
                            <div>
                                <h3>{{ $property->name }}</h3>
                                <p>Status: {{ \App\Models\Property::STATUS_LABELS[$property->status] }}</p>
                            </div>
                        </div>


                        <form method="POST" action="{{ route('owner.rooms.update', $property->id) }}" class="rooms-form">
                            @csrf
                            <label>
                                <span>Kamar tersedia (maks {{ $property->rooms }})</span>
                                <input type="number" name="available_rooms" min="0" max="{{ $property->rooms }}" value="{{ $property->available_rooms }}" required data-testid="rooms-input-{{ $property->id }}">
                            </label>
                            <button type="submit" class="small-button" data-testid="rooms-save-{{ $property->id }}">Simpan</button>
                        </form>

                    </div>

                @endforeach

            </div>

        @else
            <div class="empty-state">Belum ada kost. Tambahkan properti terlebih dahulu.</div>
        @endif


    @elseif($page === 'bookings')

        <div class="section-header">
            <div>
                <div class="section-label">INCOMING BOOKING</div>
                <h2 class="section-title">Booking masuk</h2>
            </div>
        </div>


        @if($bookings->isNotEmpty())

            <div class="booking-list">

                @foreach($bookings as $booking)

                    <div class="booking-card" data-testid="owner-booking-{{ $booking->id }}">

                        <div>

                            <span class="section-label">{{ $booking->code ?? '#' . $booking->id }}</span>

                            <h3>{{ $booking->property?->name }}</h3>

                            <p>Penyewa: {{ $booking->tenant?->name }} ({{ $booking->tenant?->email }})</p>

                            <p>{{ $booking->duration }} bulan · mulai {{ $booking->start_date->format('d M Y') }}</p>

                            @if(filled($booking->note))
                                <p class="table-subnote">Catatan: {{ $booking->note }}</p>
                            @endif

                            @if($booking->status === 'rejected' && filled($booking->rejection_reason))
                                <p class="table-subnote">Alasan penolakan: {{ $booking->rejection_reason }}</p>
                            @endif

                        </div>


                        <div class="booking-actions-col">

                            <span class="status-badge status-{{ $booking->status }}" data-testid="owner-booking-status-{{ $booking->id }}">{{ $booking->label() }}</span>


                            @if($booking->status === 'pending')

                                <form method="POST" action="{{ route('owner.booking.accept', $booking->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="small-button" data-testid="booking-accept-{{ $booking->id }}">Terima</button>
                                </form>


                                <form method="POST" action="{{ route('owner.booking.reject', $booking->id) }}" class="inline-form reject-form">
                                    @csrf
                                    <input type="text" name="reason" placeholder="Tulis alasan penolakan…" required maxlength="500" data-testid="booking-reject-reason-{{ $booking->id }}">
                                    <button type="submit" class="small-button secondary" data-testid="booking-reject-{{ $booking->id }}">Tolak</button>
                                </form>


                            @elseif($booking->status === 'confirmed')

                                <form method="POST" action="{{ route('owner.booking.complete', $booking->id) }}" class="inline-form" onsubmit="return confirm('Tandai booking ini selesai? Satu kamar akan dikembalikan ke ketersediaan.')">
                                    @csrf
                                    <button type="submit" class="small-button" data-testid="booking-complete-{{ $booking->id }}">Selesaikan</button>
                                </form>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty-state" data-testid="owner-bookings-empty">Belum ada booking masuk.</div>

        @endif


    @else

        <div class="section-header">
            <div>
                <div class="section-label">PERFORMANCE</div>
                <h2 class="section-title">Statistik kost</h2>
            </div>
        </div>


        <div class="stats" data-testid="owner-stat-stats">
            <div class="stat"><small>Total Properti</small><strong>{{ $properties->count() }}</strong></div>
            <div class="stat"><small>Total Booking</small><strong>{{ $bookingStats['total'] }}</strong></div>
            <div class="stat"><small>Menunggu</small><strong>{{ $bookingStats['pending'] }}</strong></div>
            <div class="stat"><small>Diterima</small><strong>{{ $bookingStats['confirmed'] }}</strong></div>
            <div class="stat"><small>Ditolak</small><strong>{{ $bookingStats['rejected'] }}</strong></div>
            <div class="stat"><small>Selesai</small><strong>{{ $bookingStats['completed'] }}</strong></div>
        </div>

        <h2 class="section-title">Booking terbaru</h2>
        @if($bookings->isNotEmpty())
            <div class="booking-list">
                @foreach($bookings as $booking)
                    <div class="booking-card">
                        <div>
                            <span class="section-label">{{ $booking->code ?? '#' . $booking->id }}</span>
                            <h3>{{ $booking->property?->name }}</h3>
                            <p>{{ $booking->tenant?->name }} · {{ $booking->duration }} bulan · {{ $booking->start_date->format('d M Y') }}</p>
                        </div>
                        <span class="status-badge status-{{ $booking->status }}">{{ $booking->label() }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Belum ada booking.</div>
        @endif

    @endif

</section>

@endsection
