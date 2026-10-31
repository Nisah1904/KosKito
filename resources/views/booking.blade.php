@extends('layouts.app')

@section('title', 'Booking — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">BOOKING</div>
        <h1 class="page-title">Ajukan booking kost.</h1>
        <p class="page-description">
            Booking di Kostly adalah <strong>pengajuan penyewaan</strong> — belum ada pembayaran. Pemilik akan menerima atau menolak pengajuanmu.
        </p>
    </div>
</section>

<section class="section">

    <div class="booking-layout">

        <div class="booking-property">

            @if($photo = $property->primaryPhoto())
                <img src="{{ $photo->url }}" alt="Foto {{ $property->name }}">
            @else
                <div class="property-image-placeholder"><span>⌂</span><small>Foto kost belum tersedia</small></div>
            @endif

            <div>
                <div class="property-card-top">
                    <span class="property-type">{{ $property->typeLabel() }}</span>
                </div>
                <h2 data-testid="booking-property-name">{{ $property->name }}</h2>
                <p>{{ $property->address ?? $property->location }}</p>
                <strong>{{ $property->priceLabel() }}{{ $property->hasPrice() ? '/bulan' : '' }}</strong>
                <p class="table-subnote">
                    Estimasi biaya kost: Rp{{ number_format($property->monthlyCost(), 0, ',', '.') }}/bulan
                    @if($property->electricity_included) · listrik termasuk @endif
                    @if($property->internet_included) · internet termasuk @endif
                </p>
                <p class="{{ $property->available_rooms > 0 ? 'rooms-ok' : 'rooms-full' }}" data-testid="booking-rooms">{{ $property->available_rooms }}/{{ $property->rooms }} kamar tersedia</p>
            </div>

        </div>


        <div class="planner-card">

            <form method="POST" action="{{ route('booking.store', $property->id) }}" data-testid="booking-form">

                @csrf


                <div class="form-group">

                    <label>Durasi sewa</label>

                    <select name="duration" class="form-control" required data-testid="booking-duration">
                        <option value="1">1 bulan</option>
                        <option value="3">3 bulan</option>
                        <option value="6">6 bulan</option>
                        <option value="12">12 bulan</option>
                    </select>

                </div>


                <div class="form-group">

                    <label>Tanggal mulai</label>

                    <input type="date" name="start_date" class="form-control" min="{{ now()->toDateString() }}" required data-testid="booking-start-date">

                    <small>Tidak boleh di masa lalu.</small>

                </div>


                <div class="form-group">

                    <label>Catatan untuk pemilik</label>

                    <textarea name="note" class="form-control" rows="4" maxlength="500" placeholder="Contoh: saya butuh kamar yang jauh dari jalan, bawa sepeda motor…" data-testid="booking-note"></textarea>

                </div>


                @if($errors->any())
                    <div class="form-error" data-testid="booking-errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif


                <button type="submit" class="full-button" data-testid="booking-submit" @disabled($property->available_rooms < 1)>
                    Ajukan Booking
                </button>

                @if($property->available_rooms < 1)
                    <small class="rooms-full">Kamar penuh — booking tidak dapat diajukan.</small>
                @endif

            </form>

        </div>

    </div>

</section>

@endsection
