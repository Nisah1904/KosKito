@extends('layouts.app')

@section('title', 'Admin — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">ADMINISTRATION</div>
        <h1 class="page-title">Administrasi Kostly.</h1>
        <p class="page-description">Verifikasi listing, kelola pengguna & kampus, impor data, dan pantau booking.</p>
    </div>
</section>


<section class="section">

    @include('partials.admin-menu')


    @if($page === 'verification')

        <div class="section-header">
            <div>
                <div class="section-label">VERIFICATION</div>
                <h2 class="section-title">Kost menunggu verifikasi</h2>
            </div>
        </div>


        @if($properties->isNotEmpty())

            <div class="admin-list">

                @foreach($properties as $property)

                    <div class="admin-card" data-testid="verify-property-{{ $property->id }}">

                        <div class="admin-card-main">

                            @if($photo = $property->primaryPhoto())
                                <img class="admin-card-photo" src="{{ $photo->url }}" alt="Foto {{ $property->name }}">
                            @else
                                <div class="admin-card-photo property-image-placeholder"><span>⌂</span></div>
                            @endif


                            <div>
                                <div class="property-card-top">
                                    <span class="property-type">{{ $property->typeLabel() }}</span>
                                    <span class="badge badge-source">{{ \App\Models\Property::SOURCE_LABELS[$property->source] ?? $property->source }}</span>
                                </div>

                                <h3>{{ $property->name }}</h3>

                                <p>{{ $property->address ?? $property->location }}</p>

                                <strong>{{ $property->priceLabel() }}{{ $property->hasPrice() ? '/bulan' : '' }} · {{ $property->rooms }} kamar</strong>

                                <ul class="verify-details" data-testid="verify-details-{{ $property->id }}">
                                    <li>Pemilik: {{ $property->owner?->name }} ({{ $property->owner?->email }})</li>
                                    <li>Kampus: {{ $property->campus?->name ?? '—' }}</li>
                                    <li>Koordinat: @if($property->latitude !== null){{ $property->latitude }}, {{ $property->longitude }}@else <strong>belum diisi</strong>@endif</li>
                                    <li>Sumber data: {{ \App\Models\Property::SOURCE_LABELS[$property->source] ?? $property->source }}@if($property->source_url) · <a href="{{ $property->source_url }}" target="_blank" rel="noopener">tautan sumber ↗</a>@endif</li>
                                    <li>Diajukan: {{ $property->created_at->format('d M Y H:i') }}</li>
                                </ul>
                            </div>

                        </div>


                        <div class="verify-actions">

                            <form method="POST" action="{{ route('admin.property.approve', $property->id) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="small-button" data-testid="verify-approve-{{ $property->id }}">Setujui</button>
                            </form>

                            <form method="POST" action="{{ route('admin.property.reject', $property->id) }}" class="inline-form reject-form">
                                @csrf
                                <input type="text" name="reason" placeholder="Alasan penolakan (wajib)…" required maxlength="500" data-testid="verify-reject-reason-{{ $property->id }}">
                                <button type="submit" class="small-button secondary" data-testid="verify-reject-{{ $property->id }}">Tolak</button>
                            </form>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty-state" data-testid="verify-empty">Tidak ada kost yang menunggu verifikasi.</div>

        @endif


    @elseif($page === 'users')

        <div class="section-header">
            <div>
                <div class="section-label">USERS</div>
                <h2 class="section-title">Pengguna platform</h2>
            </div>
        </div>


        <div class="stats" data-testid="admin-user-stats">
            <div class="stat"><small>Total pengguna</small><strong>{{ $roleCounts['total'] }}</strong></div>
            <div class="stat"><small>Penyewa</small><strong>{{ $roleCounts['penyewa'] }}</strong></div>
            <div class="stat"><small>Pemilik</small><strong>{{ $roleCounts['pemilik'] }}</strong></div>
            <div class="stat"><small>Admin</small><strong>{{ $roleCounts['admin'] }}</strong></div>
        </div>


        <div class="table-wrap">
            <table class="data-table" data-testid="admin-users-table">
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>Role</th><th>Kost dimiliki</th><th>Booking</th><th>Terdaftar</th></tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr data-testid="admin-user-row-{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge badge-source">{{ ucfirst($user->role) }}</span></td>
                            <td>{{ $user->properties_count }}</td>
                            <td>{{ $user->bookings_count }}</td>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


    @elseif($page === 'bookings')

        <div class="section-header">
            <div>
                <div class="section-label">BOOKINGS</div>
                <h2 class="section-title">Monitoring booking</h2>
            </div>
        </div>


        @if($bookings->isNotEmpty())

            <div class="booking-list">

                @foreach($bookings as $booking)

                    <div class="booking-card" data-testid="admin-booking-{{ $booking->id }}">

                        <div>
                            <span class="section-label">{{ $booking->code ?? '#' . $booking->id }}</span>
                            <h3>{{ $booking->property?->name ?? 'Kost dihapus' }}</h3>
                            <p>Penyewa: {{ $booking->tenant?->name }} · Pemilik: {{ $booking->property?->owner?->name ?? '—' }}</p>
                            <p>{{ $booking->duration }} bulan · mulai {{ $booking->start_date->format('d M Y') }}</p>
                        </div>

                        <span class="status-badge status-{{ $booking->status }}" data-testid="admin-booking-status-{{ $booking->id }}">{{ $booking->label() }}</span>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty-state">Belum ada data booking.</div>

        @endif


    @elseif($page === 'campuses')

        <div class="section-header">
            <div>
                <div class="section-label">CAMPUSES</div>
                <h2 class="section-title">Data kampus & koordinat</h2>
            </div>
        </div>


        <div class="planner-card campus-form" data-testid="campus-form">

            <div class="section-label">TAMBAH KAMPUS</div>

            <form method="POST" action="{{ route('admin.campuses.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label>Nama kampus *</label><input type="text" name="name" required class="form-control" data-testid="campus-name"></div>
                    <div class="form-group"><label>Sebutan singkat</label><input type="text" name="short_name" class="form-control" placeholder="Contoh: Unsri (Bukit)" data-testid="campus-short"></div>
                    <div class="form-group"><label>Kota *</label><input type="text" name="city" value="Palembang" required class="form-control" data-testid="campus-city"></div>
                    <div class="form-group"><label>Alamat</label><input type="text" name="address" class="form-control" data-testid="campus-address"></div>
                    <div class="form-group"><label>Latitude *</label><input type="text" name="latitude" required class="form-control" placeholder="-2.9855" data-testid="campus-lat"></div>
                    <div class="form-group"><label>Longitude *</label><input type="text" name="longitude" required class="form-control" placeholder="104.7334" data-testid="campus-lng"></div>
                </div>
                <div class="form-group"><label>Catatan</label><input type="text" name="notes" class="form-control" placeholder="Contoh: sumber koordinat / keduanya kampus" data-testid="campus-notes"></div>
                <button type="submit" class="primary-button" data-testid="campus-submit">Tambah Kampus</button>
            </form>

        </div>


        <div class="table-wrap">
            <table class="data-table" data-testid="campus-table">
                <thead>
                    <tr><th>Kampus</th><th>Kota</th><th>Koordinat</th><th>Listing</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($campuses as $campus)
                        <tr data-testid="campus-row-{{ $campus->id }}">
                            <td><strong>{{ $campus->short_name }}</strong><br><small>{{ $campus->name }}</small>@if($campus->notes)<br><small class="table-subnote">{{ $campus->notes }}</small>@endif</td>
                            <td>{{ $campus->city }}</td>
                            <td><small>{{ $campus->latitude }}, {{ $campus->longitude }}</small></td>
                            <td>{{ $campus->properties_count }}</td>
                            <td>{{ $campus->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td>
                                @if($campus->properties_count === 0)
                                    <form method="POST" action="{{ route('admin.campuses.destroy', $campus->id) }}" class="inline-form" onsubmit="return confirm('Hapus kampus ini?')">
                                        @csrf
                                        <button type="submit" class="small-button danger" data-testid="campus-delete-{{ $campus->id }}">Hapus</button>
                                    </form>
                                @else
                                    <small>Dipakai listing</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


    @elseif($page === 'import')

        <div class="section-header">
            <div>
                <div class="section-label">DATA IMPORT</div>
                <h2 class="section-title">Impor data kost (CSV)</h2>
            </div>
        </div>


        <div class="planner-card import-form" data-testid="import-form">

            <p class="section-subtitle">Untuk data dari sumber yang <strong>mengizinkan penggunaan ulang</strong> (misalnya data milik sendiri atau sumber berizin). Semua baris hasil impor berstatus <strong>MENUNGGU VERIFIKASI</strong> — tidak otomatis terverifikasi.</p>

            <form method="POST" action="{{ route('admin.import.process') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>File CSV (maks 4MB)</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="form-control" data-testid="import-file">
                </div>
                <button type="submit" class="primary-button" data-testid="import-submit">Proses Impor</button>
            </form>

            <div class="import-format">
                <strong>Format kolom (header wajib: name, price, address, type):</strong>
                <code>name,price,address,type,rooms,facilities,latitude,longitude,campus_id,phone,source_url</code>
                <small>
                    type: putra/putri/campur · price: angka (850000 atau "Rp850.000") · facilities: pisahkan dengan koma ·
                    latitude/longitude opsional tapi divalidasi · campus_id opsional (harus ada di tabel kampus) · source_url wajib diisi bila data dari web.
                </small>
                <small>Kolom contoh tersedia di file <code>contoh-import-kost.csv</code> pada root project.</small>
            </div>

            @if(session('import_report'))
                @php $report = session('import_report'); @endphp
                <div class="import-report" data-testid="import-report">
                    <strong>Hasil impor terakhir:</strong>
                    <ul>
                        <li>Total baris: {{ $report['total'] }}</li>
                        <li>Berhasil: {{ $report['success'] }}</li>
                        <li>Duplikat terlewati: {{ $report['duplicate'] }}</li>
                        <li>Gagal: {{ $report['failed'] }}</li>
                    </ul>
                    @if($report['errors'])
                        <details>
                            <summary>Rincian baris bermasalah ({{ count($report['errors']) }})</summary>
                            <ul>
                                @foreach($report['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endif

        </div>


        <h2 class="section-title">Riwayat impor</h2>
        @if($imports->isNotEmpty())
            <div class="table-wrap">
                <table class="data-table" data-testid="import-history-table">
                    <thead><tr><th>File</th><th>Oleh</th><th>Total</th><th>Berhasil</th><th>Duplikat</th><th>Gagal</th><th>Waktu</th></tr></thead>
                    <tbody>
                        @foreach($imports as $import)
                            <tr data-testid="import-row-{{ $import->id }}">
                                <td>{{ $import->filename }}</td>
                                <td>{{ $import->user?->name ?? '—' }}</td>
                                <td>{{ $import->total_rows }}</td>
                                <td>{{ $import->success_rows }}</td>
                                <td>{{ $import->duplicate_rows }}</td>
                                <td>{{ $import->failed_rows }}</td>
                                <td>{{ $import->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">Belum ada impor.</div>
        @endif


    @else

        <div class="section-header">
            <div>
                <div class="section-label">PLATFORM STATISTICS</div>
                <h2 class="section-title">Statistik Kostly</h2>
            </div>
        </div>


        <div class="stats" data-testid="admin-stat-stats">
            <div class="stat"><small>Pengguna</small><strong>{{ $stats['users'] }}</strong></div>
            <div class="stat"><small>Pemilik</small><strong>{{ $stats['owners'] }}</strong></div>
            <div class="stat"><small>Penyewa</small><strong>{{ $stats['tenants'] }}</strong></div>
            <div class="stat"><small>Kost disetujui</small><strong>{{ $stats['approved'] }}</strong></div>
            <div class="stat"><small>Menunggu verifikasi</small><strong>{{ $stats['pending'] }}</strong></div>
            <div class="stat"><small>Ditolak</small><strong>{{ $stats['rejected'] }}</strong></div>
            <div class="stat"><small>Info dasar dari web</small><strong>{{ $stats['web'] }}</strong></div>
            <div class="stat"><small>Total booking</small><strong>{{ $stats['bookings'] }}</strong></div>
            <div class="stat"><small>Kampus</small><strong>{{ $stats['campuses'] }}</strong></div>
            <div class="stat"><small>Impor data</small><strong>{{ $stats['imports'] }}</strong></div>
        </div>

    @endif

</section>

@endsection
