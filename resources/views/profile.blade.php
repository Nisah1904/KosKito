@extends('layouts.app')

@section('title', 'Profil — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">MY PROFILE</div>
        <h1 class="page-title">Profil akun</h1>
        <p class="page-description">Kelola informasi akun dan akses fitur Kostly.</p>
    </div>
</section>


<section class="section">

    <div class="profile-card">

        <div class="profile-avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>


        <div class="profile-info">

            <h2 data-testid="profile-name">{{ auth()->user()->name }}</h2>

            <p data-testid="profile-email">{{ auth()->user()->email }}</p>

            <span class="property-type" data-testid="profile-role">{{ ucfirst(auth()->user()->role) }}</span>

        </div>

    </div>


    <div class="action-grid">

        <a href="{{ route('dashboard') }}" class="action-card" data-testid="profile-dashboard">
            <strong>Dashboard</strong>
            <span>Lihat aktivitas akun</span>
        </a>


        @if(auth()->user()->isTenant())

            <a href="{{ route('favorites') }}" class="action-card">
                <strong>Favorit</strong>
                <span>Kost yang kamu simpan</span>
            </a>


            <a href="{{ route('booking.history') }}" class="action-card">
                <strong>Riwayat Booking</strong>
                <span>Pantau pengajuan booking</span>
            </a>

        @elseif(auth()->user()->isOwner())

            <a href="{{ route('owner.properties') }}" class="action-card">
                <strong>Properti Saya</strong>
                <span>Kelola kost milikmu</span>
            </a>

        @elseif(auth()->user()->isAdmin())

            <a href="{{ route('admin.verification') }}" class="action-card">
                <strong>Verifikasi Kost</strong>
                <span>Periksa pengajuan listing</span>
            </a>

        @endif

    </div>

</section>

@endsection
