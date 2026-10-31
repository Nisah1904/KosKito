@extends('layouts.app')

@section('title', 'Smart Match — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">SMART MATCH</div>
        <h1 class="page-title">Kost yang lebih sesuai denganmu.</h1>
        <p class="page-description">
            Skor dihitung dari data database dengan bobot terdokumentasi — bukan angka acak. Komponen yang datanya tidak tersedia dilewati secara jujur.
        </p>
    </div>
</section>

<section class="section">
    <div class="match-layout">

        <form action="{{ route('recommendations') }}" method="GET" class="planner-card match-form" data-testid="smart-match-form">
            <div class="section-label">KEBUTUHANMU</div>

            <div class="form-group">
                <label>Kampus tujuan</label>
                <select name="campus_id" data-testid="match-campus">
                    <option value="">— Tanpa kampus spesifik —</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected(request('campus_id') == $campus->id)>{{ $campus->short_name }} ({{ $campus->city }})</option>
                    @endforeach
                </select>
                <small>Koordinat kampus dipakai untuk menghitung jarak garis lurus.</small>
            </div>

            <div class="form-group">
                <label>Anggaran sewa per bulan (Rp)</label>
                <input type="number" name="budget" min="0" step="50000" value="{{ request('budget') }}" placeholder="Contoh: 1000000" data-testid="match-budget">
            </div>

            <div class="form-group">
                <label>Preferensi jarak maksimal (km, opsional)</label>
                <input type="number" name="max_distance_km" min="0.5" step="0.5" value="{{ request('max_distance_km') }}" placeholder="Contoh: 3">
                <small>Jarak = garis lurus dari koordinat, bukan rute jalan.</small>
            </div>

            <div class="form-group">
                <label>Pengeluaran bulanan lain (transport, makan, dll — Rp)</label>
                <input type="number" name="extras" min="0" step="50000" value="{{ request('extras') }}" placeholder="Contoh: 1500000">
            </div>

            <div class="form-group">
                <label>Anggaran total bulanan (sewa + biaya kost + pengeluaran lain — Rp)</label>
                <input type="number" name="total_budget" min="0" step="50000" value="{{ request('total_budget') }}" placeholder="Contoh: 3000000">
            </div>

            <div class="form-group">
                <label>Tipe kost</label>
                <select name="type" data-testid="match-type">
                    <option value="">Semua tipe</option>
                    <option value="putra" @selected(request('type') == 'putra')>Putra</option>
                    <option value="putri" @selected(request('type') == 'putri')>Putri</option>
                    <option value="campur" @selected(request('type') == 'campur')>Campur</option>
                </select>
            </div>

            <div class="form-group">
                <label>Fasilitas wajib</label>
                <div class="filter-checks">
                    @foreach($facilityOptions as $option)
                        <label class="check-chip">
                            <input type="checkbox" name="facilities[]" value="{{ $option }}" @checked(in_array($option, (array) request('facilities', [])))>
                            <span>{{ ucfirst($option) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="check-chip check-chip-strong">
                <input type="checkbox" name="available_only" value="1" @checked(request('available_only'))>
                <span>Hanya kost yang masih ada kamar kosong</span>
            </label>

            <button type="submit" class="full-button" data-testid="match-submit">Hitung Kecocokan</button>
        </form>

        <div class="match-results">
            <div class="weights-card" data-testid="match-weights">
                <strong>Bobot skor Smart Match</strong>
                <ul>
                    <li>Anggaran sewa — 35%</li>
                    <li>Jarak ke kampus — 25% (lewati bila koordinat tidak ada)</li>
                    <li>Fasilitas wajib — 20%</li>
                    <li>Kamar tersedia — 10%</li>
                    <li>Total biaya bulanan — 10% (lewati bila anggaran total tidak diisi)</li>
                </ul>
                <small>Kost termurah TIDAK otomatis jadi terbaik — skor mengutamakan kecocokan kebutuhanmu.</small>
            </div>

            @if(!$hasInput)
                <div class="empty-state" data-testid="match-empty-form">
                    <span class="empty-state-mark">✳</span>
                    <h3>Isi kebutuhanmu di form sebelah kiri</h3>
                    <p>Rekomendasi dihitung dari data kost terverifikasi yang tersimpan di database.</p>
                </div>
            @elseif($results->isEmpty())
                <div class="empty-state" data-testid="match-empty-result">
                    <span class="empty-state-mark">✳</span>
                    <h3>Belum ada kost yang cocok.</h3>
                    <p>Coba longgarkan anggaran, kurangi fasilitas wajib, atau pilih kampus lain. Jumlah listing masih terbatas dan kami tidak menampilkan data karangan.</p>
                    <a href="{{ route('listings') }}" class="primary-button">Lihat semua kost</a>
                </div>
            @else
                <div class="match-result-list">
                    @foreach($results as $property)
                        <article class="match-result-card" data-testid="match-result-{{ $property->id }}">
                            <div class="match-result-head">
                                <div>
                                    <div class="property-card-top">
                                        <span class="property-type">{{ $property->typeLabel() }}</span>
                                        @if($property->isVerified())<span class="badge badge-verified">✓</span>@endif
                                    </div>
                                    <h3><a href="{{ route('detail', $property->id) }}">{{ $property->name }}</a></h3>
                                    <p class="property-location">⌖ {{ $property->location }} · {{ $property->priceLabel() }}{{ $property->hasPrice() ? '/bulan' : '' }} · {{ $property->roomsLabel() }}</p>
                                </div>
                                <div class="match-score" data-testid="match-score-{{ $property->id }}">
                                    <strong>{{ $property->match }}%</strong>
                                    <span>cocok</span>
                                </div>
                            </div>

                            <ul class="match-reasons" data-testid="match-reasons-{{ $property->id }}">
                                @foreach($property->match_reasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>

                            @if(!empty($property->match_notes))
                                @foreach($property->match_notes as $note)
                                    <p class="match-note">{{ $note }}</p>
                                @endforeach
                            @endif

                            <div class="ai-explain-box" id="ai-explain-{{ $property->id }}" hidden data-testid="match-ai-text-{{ $property->id }}"></div>

                            <div class="match-result-actions">
                                @auth
                                    <button type="button" class="ghost-button ai-explain-button" data-url="{{ route('recommendations.explain', $property->id) }}" data-target="ai-explain-{{ $property->id }}" data-detail-base="{{ url('/kost') }}" data-testid="match-ai-explain-{{ $property->id }}">✳ Jelaskan dengan AI</button>
                                @else
                                    <a href="{{ route('login') }}" class="ghost-button" data-testid="match-ai-login-{{ $property->id }}">✳ Masuk untuk penjelasan AI</a>
                                @endauth
                                <a href="{{ route('detail', $property->id) }}" class="card-detail-button">Lihat Detail</a>
                                <form method="POST" action="{{ route('compare.add') }}" class="inline-form">@csrf<input type="hidden" name="property_id" value="{{ $property->id }}"><button type="submit" class="ghost-button" data-testid="match-compare-{{ $property->id }}">⇄ Bandingkan</button></form>
                                <a href="{{ route('cost-planner', ['kost_id' => $property->id]) }}" class="ghost-button">Rp Hitung biaya kost ini</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</section>

@endsection
