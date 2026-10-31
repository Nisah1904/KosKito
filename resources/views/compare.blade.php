@extends('layouts.app')

@section('title', 'Bandingkan Kost — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">COMPARE</div>
        <h1 class="page-title">Bandingkan pilihanmu.</h1>
        <p class="page-description">
            Hanya kost yang KAMU pilih yang dibandingkan — tambahkan lewat tombol "⇄ Bandingkan" pada kartu kost (maksimal 3).
        </p>
    </div>
</section>

<section class="section">

    <form method="GET" action="{{ route('compare') }}" class="compare-campus-picker" data-testid="compare-campus-form">
        <label>
            <span>Hitung jarak ke kampus:</span>
            <select name="campus_id" data-testid="compare-campus-select">
                <option value="">— Pilih kampus —</option>
                @foreach($campuses as $campus)
                    <option value="{{ $campus->id }}" @selected($selectedCampus?->id == $campus->id)>{{ $campus->short_name }} ({{ $campus->city }})</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="secondary-button" data-testid="compare-campus-apply">Hitung Jarak</button>
    </form>

    @if($properties->count() < 2)
        <div class="empty-state" data-testid="compare-insufficient">
            <span class="empty-state-mark">⇄</span>
            <h3>Belum cukup kost untuk dibandingkan</h3>
            @if($properties->count() === 1)
                <p>Saat ini 1 kost terpilih: <strong>{{ $properties->first()->name }}</strong>. Tambahkan minimal satu lagi dari halaman pencarian.</p>
                <div class="compare-selected-row">
                    <span>{{ $properties->first()->name }}</span>
                    <form method="POST" action="{{ route('compare.remove', $properties->first()->id) }}">@csrf<button type="submit" class="ghost-button" data-testid="compare-remove-{{ $properties->first()->id }}">Hapus</button></form>
                </div>
            @else
                <p>Belum ada kost terpilih. Buka daftar kost lalu tekan "⇄ Bandingkan" pada kartu yang kamu minati.</p>
            @endif
            <a href="{{ route('listings') }}" class="primary-button">Lihat daftar kost</a>
        </div>
    @else
    <div class="compare-table-wrap" data-testid="compare-table-wrap">

        <table class="compare-table">

            <thead>
                <tr>
                    <th>Fitur</th>
                    @foreach($properties as $property)
                        <th data-testid="compare-col-{{ $property->id }}">
                            {{ $property->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>


            <tbody>
                <tr>
                    <td>Foto</td>
                    @foreach($properties as $property)
                        <td>@if($photo = $property->primaryPhoto())<img class="compare-photo" src="{{ $photo->url }}" alt="Foto {{ $property->name }}">@else<div class="property-image-placeholder compare-photo"><span>⌂</span></div>@endif</td>
                    @endforeach
                </tr>

                <tr>
                    <td>Harga sewa / bulan</td>
                    @foreach($properties as $property)
                        <td><strong data-testid="compare-price-{{ $property->id }}">{{ $property->priceLabel() }}</strong></td>
                    @endforeach
                </tr>

                <tr>
                    <td>Estimasi total biaya kost / bulan</td>
                    @foreach($properties as $property)
                        <td data-testid="compare-total-{{ $property->id }}">
                            <strong>Rp {{ number_format($property->monthlyCost(), 0, ',', '.') }}</strong>
                            <small class="table-subnote">
                                @if($property->electricity_included) listrik termasuk @elseif($property->electricity_fee > 0) + listrik Rp{{ number_format($property->electricity_fee, 0, ',', '.') }} @else listrik belum diketahui @endif ·
                                @if($property->internet_included) internet termasuk @elseif($property->internet_fee > 0) + internet Rp{{ number_format($property->internet_fee, 0, ',', '.') }} @else internet belum diketahui @endif
                            </small>
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td>Tipe</td>
                    @foreach($properties as $property)
                        <td>{{ $property->typeLabel() }}</td>
                    @endforeach
                </tr>

                <tr>
                    <td>Jarak garis lurus ke kampus terpilih</td>
                    @foreach($properties as $property)
                        <td data-testid="compare-distance-{{ $property->id }}">
                            @if(!is_null($property->distance_km))
                                ±{{ \App\Services\Geo::formatKm($property->distance_km) }}
                            @elseif($selectedCampus)
                                <small>Belum bisa dihitung — koordinat kost belum diisi</small>
                            @else
                                <small>Pilih kampus dulu</small>
                            @endif
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td>Kamar tersedia</td>
                    @foreach($properties as $property)
                        <td class="{{ $property->available_rooms > 0 ? 'rooms-ok' : 'rooms-full' }}" data-testid="compare-rooms-{{ $property->id }}">
                            {{ $property->available_rooms > 0 ? $property->available_rooms . ' dari ' . $property->rooms : 'Penuh' }}
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td>Fasilitas</td>
                    @foreach($properties as $property)
                        <td>
                            @forelse($property->facilities ?? [] as $facility)
                                <span class="table-tag">{{ ucfirst($facility) }}</span>
                            @empty
                                <small>Belum diisi</small>
                            @endforelse
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td>Status verifikasi & sumber data</td>
                    @foreach($properties as $property)
                        <td data-testid="compare-verify-{{ $property->id }}">
                            @if($property->isVerified())
                                <span class="badge badge-verified">✓ Terverifikasi</span>
                            @else
                                <span class="badge badge-unverified">Belum terverifikasi</span>
                            @endif
                            <small class="table-subnote">{{ \App\Models\Property::SOURCE_LABELS[$property->source] ?? $property->source }}</small>
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td>Aksi</td>
                    @foreach($properties as $property)
                        <td>
                            <a href="{{ route('detail', $property->id) }}" class="card-detail-button" data-testid="compare-detail-{{ $property->id }}">Lihat Detail</a>
                            <form method="POST" action="{{ route('compare.remove', $property->id) }}" class="inline-form">@csrf<button type="submit" class="ghost-button" data-testid="compare-remove-{{ $property->id }}">Hapus dari banding</button></form>
                        </td>
                    @endforeach
                </tr>
            </tbody>

        </table>

    </div>
    @endif

</section>

@endsection
