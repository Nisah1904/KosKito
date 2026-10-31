@php
    $cardPhoto = $property->primaryPhoto();
    $cardFav = auth()->check() && auth()->user()->isTenant()
        ? auth()->user()->favorites()->where('property_id', $property->id)->exists()
        : false;
@endphp
<article class="property-card" data-testid="property-card-{{ $property->id }}">

    <a href="{{ route('detail', $property->id) }}" class="property-image-link" data-testid="card-link-{{ $property->id }}">

        @if($cardPhoto)
            <img src="{{ $cardPhoto->url }}" alt="Foto {{ $property->name }}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=&quot;property-image-placeholder&quot;><span>⌂</span><small>Foto gagal dimuat</small></div>'">
        @else
            <div class="property-image-placeholder"><span>⌂</span><small>Foto kost belum tersedia</small></div>
        @endif

        @if($property->is_basic_info)
            <span class="badge badge-source card-badge" data-testid="badge-web-{{ $property->id }}">Info dari web</span>
        @endif

    </a>


    <div class="property-card-body">

        <div class="property-card-top">

            <span class="property-type">{{ $property->typeLabel() }}</span>

            @if($property->isVerified())
                <span class="badge badge-verified" data-testid="badge-verified-{{ $property->id }}">✓ Terverifikasi</span>
            @else
                <span class="badge badge-unverified">Belum diverifikasi</span>
            @endif

        </div>


        <h3>
            <a href="{{ route('detail', $property->id) }}">
                {{ $property->name }}
            </a>
        </h3>


        <p class="property-location">
            ⌖ {{ $property->location }}
        </p>


        @if(!is_null($property->distance_km))
            <span class="distance-chip" data-testid="distance-chip-{{ $property->id }}">±{{ \App\Services\Geo::formatKm($property->distance_km) }} garis lurus ke kampus</span>
        @endif


        <div class="property-price {{ $property->hasPrice() ? '' : 'is-unknown' }}">

            {{ $property->priceLabel() }}
            @if($property->hasPrice())<small>/bulan</small>@endif

        </div>


        <div class="property-meta">

            <span class="{{ $property->is_basic_info ? 'rooms-unknown' : ($property->available_rooms > 0 ? 'rooms-ok' : 'rooms-full') }}" data-testid="rooms-availability-{{ $property->id }}">{{ $property->roomsLabel() }}</span>

            @if(isset($property->match))
                <span class="match-text">
                    {{ $property->match }}% cocok
                </span>
            @endif

        </div>


        <div class="property-facilities">

            @foreach(array_slice($property->facilities ?? [], 0, 4) as $facility)

                <span>
                    {{ ucfirst($facility) }}
                </span>

            @endforeach

        </div>


        <div class="card-actions">

            <a href="{{ route('detail', $property->id) }}"
               class="card-detail-button"
               data-testid="card-detail-{{ $property->id }}">
                Lihat Detail
            </a>

            <span class="card-action-row">

                @if(auth()->check() && auth()->user()->isTenant())
                    <form method="POST" action="{{ route('favorites.toggle', $property->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="ghost-button {{ $cardFav ? 'is-favorite' : '' }}" data-testid="favorite-toggle-{{ $property->id }}">
                            {{ $cardFav ? '♥ Favorit' : '♡ Favorit' }}
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('compare.add') }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <button type="submit" class="ghost-button" data-testid="compare-add-{{ $property->id }}" title="Tambahkan ke perbandingan">⇄ Bandingkan</button>
                </form>

            </span>

        </div>

    </div>

</article>
