@extends('layouts.app')

@section('title', 'Tidak Ditemukan — Kostly.')

@section('content')

<div class="error-page">
    <span class="empty-state-mark">⌖</span>
    <h1>Halaman atau kost tidak ditemukan</h1>
    <p class="page-description">Tautan mungkin salah, atau listing belum disetujui sehingga tidak tampil di halaman publik.</p>
    <div class="dashboard-actions">
        <a href="{{ route('listings') }}" class="primary-button" data-testid="error-to-listings">Cari Kost</a>
        <a href="{{ route('home') }}" class="secondary-button">Ke Beranda</a>
    </div>
</div>

@endsection
