@extends('layouts.app')

@section('title', 'Akses Ditolak — Kostly.')

@section('content')

<div class="error-page">
    <span class="empty-state-mark">🔒</span>
    <h1>Akses ditolak</h1>
    <p class="page-description">{{ $exception->getMessage() }}</p>
    <p class="section-subtitle">Setiap role (admin, pemilik, penyewa) hanya dapat membuka halaman miliknya. Gunakan menu sesuai akunmu.</p>
    <div class="dashboard-actions">
        <a href="{{ route('dashboard') }}" class="primary-button" data-testid="error-to-dashboard">Ke Dashboard Saya</a>
        <a href="{{ route('home') }}" class="secondary-button">Ke Beranda</a>
    </div>
</div>

@endsection
