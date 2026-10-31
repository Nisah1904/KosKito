@extends('layouts.app')

@section('title', 'Dashboard — Kostly.')

@section('content')

<div class="dashboard">

    @if($role === 'admin')

        <div class="section-label">ADMIN AREA</div>
        <h1 class="dashboard-title">Dashboard Admin</h1>
        <p class="section-subtitle">Semua angka berasal dari database — bukan data palsu.</p>

        <div class="stats" data-testid="admin-stats">
            <div class="stat"><small>Total Pengguna</small><strong>{{ $stats['users'] }}</strong></div>
            <div class="stat"><small>Kost tampil publik</small><strong>{{ $stats['approved'] }}</strong><span class="stat-note">{{ $stats['verified'] }} terverifikasi</span></div>
            <div class="stat"><small>Menunggu Verifikasi</small><strong>{{ $stats['pending'] }}</strong></div>
            <div class="stat"><small>Booking menunggu</small><strong>{{ $stats['pendingBookings'] }}</strong></div>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('admin.verification') }}" class="primary-button" data-testid="admin-go-verification">Verifikasi Kost</a>
            <a href="{{ route('admin.campuses') }}" class="secondary-button">Kelola Kampus</a>
            <a href="{{ route('admin.import') }}" class="secondary-button">Impor Data Kost</a>
            <a href="{{ route('admin.statistics') }}" class="secondary-button">Lihat Statistik</a>
        </div>

        <h2 class="section-title">Pengajuan terbaru</h2>
        @if($pendingProperties->isNotEmpty())
            <div class="admin-list">
                @foreach($pendingProperties as $property)
                    <div class="admin-card" data-testid="dashboard-pending-{{ $property->id }}">
                        <div>
                            <span class="property-type">{{ \App\Models\Property::SOURCE_LABELS[$property->source] ?? $property->source }}</span>
                            <h3>{{ $property->name }}</h3>
                            <p>{{ $property->location }} · {{ $property->priceLabel() }}{{ $property->hasPrice() ? '/bulan' : '' }} · pemilik: {{ $property->owner?->name }}</p>
                        </div>
                        <a href="{{ route('admin.verification') }}" class="small-button">Periksa</a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Tidak ada pengajuan menunggu verifikasi.</div>
        @endif

        <h2 class="section-title">Booking terbaru</h2>
        @if($recentBookings->isNotEmpty())
            <div class="booking-list">
                @foreach($recentBookings as $booking)
                    <div class="booking-card">
                        <div>
                            <span class="section-label">{{ $booking->code ?? 'BOOKING #' . $booking->id }}</span>
                            <h3>{{ $booking->property?->name ?? 'Kost dihapus' }}</h3>
                            <p>{{ $booking->tenant?->name ?? 'Pengguna' }} · {{ $booking->duration }} bulan · mulai {{ $booking->start_date->format('d M Y') }}</p>
                        </div>
                        <span class="status-badge status-{{ $booking->status }}" data-testid="booking-status-{{ $booking->id }}">{{ $booking->label() }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Belum ada booking.</div>
        @endif


    @elseif($role === 'pemilik')

        <div class="section-label">OWNER AREA</div>
        <h1 class="dashboard-title">Dashboard Pemilik Kost</h1>
        <p class="section-subtitle">Kelola properti dan proses booking masuk.</p>

        <div class="stats" data-testid="owner-stats">
            <div class="stat"><small>Total Kost</small><strong>{{ $stats['total'] }}</strong></div>
            <div class="stat"><small>Disetujui / Menunggu</small><strong>{{ $stats['approved'] }} / {{ $stats['pending'] }}</strong></div>
            <div class="stat"><small>Kamar</small><strong>{{ $stats['availableRooms'] }} kosong dari {{ $stats['rooms'] }}</strong></div>
            <div class="stat"><small>Booking Menunggu</small><strong>{{ $stats['pendingBookings'] }}</strong></div>
        </div>

        @if($stats['rejected'] > 0)
            <div class="flash-message error" role="alert"><span>!</span>{{ $stats['rejected'] }} kost kamu ditolak admin — buka <a href="{{ route('owner.properties') }}">Properti saya</a> untuk melihat alasannya lalu perbaiki dan kirim ulang.</div>
        @endif

        <div class="dashboard-actions">
            <a href="{{ route('owner.properties.create') }}" class="primary-button" data-testid="owner-go-create">+ Tambah Kost</a>
            <a href="{{ route('owner.properties') }}" class="secondary-button">Kelola Kost</a>
            <a href="{{ route('owner.bookings') }}" class="secondary-button">Booking Masuk</a>
        </div>

        <h2 class="section-title">Booking terbaru untuk kostmu</h2>
        @if($incoming->isNotEmpty())
            <div class="booking-list">
                @foreach($incoming as $booking)
                    <div class="booking-card" data-testid="owner-incoming-{{ $booking->id }}">
                        <div>
                            <span class="section-label">{{ $booking->code ?? '#' . $booking->id }}</span>
                            <h3>{{ $booking->property?->name }}</h3>
                            <p>{{ $booking->tenant?->name }} · {{ $booking->duration }} bulan · mulai {{ $booking->start_date->format('d M Y') }}</p>
                        </div>
                        <span class="status-badge status-{{ $booking->status }}">{{ $booking->label() }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('owner.bookings') }}" class="secondary-button">Lihat semua booking</a>
        @else
            <div class="empty-state">Belum ada booking masuk.</div>
        @endif


    @else

        <div class="section-label">MY DASHBOARD</div>
        <h1 class="dashboard-title">Halo, {{ auth()->user()->name }}.</h1>
        <p class="section-subtitle">Temukan kost, simpan favorit, dan ajukan booking.</p>

        <div class="stats" data-testid="tenant-stats">
            <div class="stat"><small>Kost Favorit</small><strong>{{ $stats['favorites'] }}</strong></div>
            <div class="stat"><small>Total Booking</small><strong>{{ $stats['bookings'] }}</strong></div>
            <div class="stat"><small>Menunggu Konfirmasi</small><strong>{{ $stats['pendingBookings'] }}</strong></div>
            <div class="stat"><small>Diterima</small><strong>{{ $stats['confirmedBookings'] }}</strong></div>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('listings') }}" class="primary-button" data-testid="tenant-go-search">Cari Kost</a>
            <a href="{{ route('recommendations') }}" class="secondary-button">Smart Match</a>
            <a href="{{ route('booking.history') }}" class="secondary-button">Riwayat Booking</a>
        </div>

        <h2 class="section-title">Booking terakhirmu</h2>
        @if($bookings->isNotEmpty())
            <div class="booking-list">
                @foreach($bookings as $booking)
                    <div class="booking-card" data-testid="tenant-booking-{{ $booking->id }}">
                        <div>
                            <span class="section-label">{{ $booking->code ?? '#' . $booking->id }}</span>
                            <h3>{{ $booking->property?->name ?? 'Kost dihapus' }}</h3>
                            <p>{{ $booking->duration }} bulan · mulai {{ $booking->start_date->format('d M Y') }}</p>
                        </div>
                        <span class="status-badge status-{{ $booking->status }}">{{ $booking->label() }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Belum ada booking. Cari kost lalu ajukan booking pertamamu.</div>
        @endif

        <h2 class="section-title">Favorit terakhirmu</h2>
        @if($favorites->isNotEmpty())
            <div class="property-grid">
                @foreach($favorites as $property)
                    @include('partials.property-card', ['property' => $property])
                @endforeach
            </div>
        @else
            <div class="empty-state">Belum ada favorit. Tekan "♡ Favorit" pada kartu kost.</div>
        @endif

    @endif

</div>

@endsection
