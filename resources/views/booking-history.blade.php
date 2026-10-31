@extends('layouts.app')

@section('title', 'Riwayat Booking — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">BOOKING HISTORY</div>
        <h1 class="page-title">Riwayat booking.</h1>
        <p class="page-description">
            Pantau semua pengajuan booking dan statusnya — tersinkron dengan dashboard pemilik.
        </p>
    </div>
</section>


<section class="section">

    @if($bookings->isNotEmpty())

        <div class="booking-list">

            @foreach($bookings as $booking)

                <div class="booking-card" data-testid="history-booking-{{ $booking->id }}">

                    <div>

                        <span class="section-label">{{ $booking->code ?? 'BOOKING #' . $booking->id }}</span>

                        <h3>{{ $booking->property?->name ?? 'Kost dihapus' }}</h3>

                        <p>
                            Mulai {{ $booking->start_date->format('d M Y') }} · {{ $booking->duration }} bulan
                            @if($booking->responded_at) · diproses {{ $booking->responded_at->format('d M Y') }} @endif
                        </p>

                        @if(filled($booking->note))
                            <p class="table-subnote">Catatanmu: {{ $booking->note }}</p>
                        @endif

                        @if($booking->status === 'rejected' && filled($booking->rejection_reason))
                            <p class="rejection-reason" data-testid="rejection-reason-{{ $booking->id }}"><strong>Alasan penolakan:</strong> {{ $booking->rejection_reason }}</p>
                        @endif

                    </div>


                    <div class="booking-actions-col">

                        <span class="status-badge status-{{ $booking->status }}" data-testid="booking-status-{{ $booking->id }}">

                            {{ $booking->label() }}

                        </span>

                        @if($booking->status === 'pending')
                            <form method="POST" action="{{ route('booking.cancel', $booking->id) }}" onsubmit="return confirm('Batalkan booking ini?')">
                                @csrf
                                <button type="submit" class="ghost-button" data-testid="booking-cancel-{{ $booking->id }}">Batalkan</button>
                            </form>
                        @endif

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="empty-state" data-testid="history-empty">

            <h3>Belum ada booking.</h3>

            <p>Pilih kost yang sesuai dan ajukan booking.</p>

            <a href="{{ route('listings') }}" class="primary-button">Cari Kost</a>

        </div>

    @endif

</section>

@endsection
