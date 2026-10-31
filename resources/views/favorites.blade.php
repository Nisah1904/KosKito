@extends('layouts.app')

@section('title', 'Favorit — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">SAVED KOST</div>
        <h1 class="page-title">Kost favoritmu.</h1>
        <p class="page-description">
            Tersimpan di akunmu — aman walau ganti perangkat atau browser.
        </p>
    </div>
</section>

<section class="section">

    @if($favorites->isNotEmpty())
        <div class="property-grid">
            @foreach($favorites as $property)
                @include('partials.property-card', ['property' => $property])
            @endforeach
        </div>
    @else
        <div class="empty-state" data-testid="favorites-empty">
            <h3>Belum ada kost favorit.</h3>
            <p>Cari kost yang menarik lalu tekan "♡ Favorit" pada kartu kost.</p>
            <a href="{{ route('listings') }}" class="primary-button">Cari Kost</a>
        </div>
    @endif

</section>

@endsection
