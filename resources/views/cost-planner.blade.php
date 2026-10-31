@extends('layouts.app')

@section('title', 'Hitung Biaya — Kostly.')

@section('content')

<section class="page-hero">
    <div class="section">
        <div class="section-label">KALKULATOR BIAYA</div>
        <h1 class="page-title">Hitung biaya tinggalmu.</h1>
        <p class="page-description">
            Biaya hidup mahasiswa bukan cuma harga kost. Pilih kost dari database untuk mengisi otomatis, lalu lengkapi sisanya.
        </p>
    </div>
</section>

<section class="section planner-grid">

    <div class="planner-card">
        <div class="section-label">KOMPONEN BIAYA BULANAN</div>

        <form method="GET" action="{{ route('cost-planner') }}" id="kostPicker" data-testid="planner-picker-form">
            <label>Pilih kost dari database (opsional)</label>
            <select name="kost_id" id="kostSelect" data-testid="planner-kost-select">
                <option value="">— Hitung tanpa kost tertentu —</option>
                @foreach($kosts as $kost)
                    <option value="{{ $kost->id }}" @selected($selected?->id === $kost->id)>{{ $kost->name }} — {{ $kost->priceLabel() }}{{ $kost->hasPrice() ? '/bulan' : '' }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="secondary-button">Muat data kost</button></noscript>
        </form>

        <label>Harga sewa kost / bulan (Rp)</label>
        <input id="rent" type="number" min="0" step="10000" value="{{ $selected?->price ?? old('rent') }}" placeholder="Masukkan harga kost" data-testid="planner-rent">

        <label>Listrik (Rp)</label>
        @if($selected && $selected->electricity_included)
            <input id="electricity" type="number" min="0" value="0" disabled data-testid="planner-electricity-disabled">
            <small class="included-note">Sudah termasuk sewa — tidak dihitung dua kali.</small>
        @else
            <input id="electricity" type="number" min="0" step="10000" value="{{ $selected?->electricity_fee ?? '' }}" placeholder="Masukkan biaya listrik" data-testid="planner-electricity">
        @endif

        <label>Internet (Rp)</label>
        @if($selected && $selected->internet_included)
            <input id="internet" type="number" min="0" value="0" disabled data-testid="planner-internet-disabled">
            <small class="included-note">Sudah termasuk sewa — tidak dihitung dua kali.</small>
        @else
            <input id="internet" type="number" min="0" step="10000" value="{{ $selected?->internet_fee ?? '' }}" placeholder="Masukkan biaya internet" data-testid="planner-internet">
        @endif

        <label>Laundry (Rp)</label>
        <input id="laundry" type="number" min="0" step="10000" placeholder="Masukkan biaya laundry" data-testid="planner-laundry">

        <label>Transportasi (Rp)</label>
        <input id="transport" type="number" min="0" step="10000" placeholder="Contoh: bensin + parkir" data-testid="planner-transport">

        <label>Makan (Rp)</label>
        <input id="food" type="number" min="0" step="50000" placeholder="Contoh: 900000" data-testid="planner-food">

        <label>Kebutuhan harian (Rp)</label>
        <input id="daily" type="number" min="0" step="10000" placeholder="Sabun, air minum, dll" data-testid="planner-daily">

        <label>Pengeluaran tambahan lainnya (Rp)</label>
        <input id="other" type="number" min="0" step="10000" placeholder="Kuota pribadi, jajan, dll" data-testid="planner-other">

        <div class="cost-breakdown" id="breakdown" data-testid="planner-breakdown"></div>

        <a href="#" id="sendToMatch" class="secondary-button" data-testid="planner-to-match">✳ Pakai angka ini di Smart Match</a>
    </div>


    <div class="cost-result">
        <span>Perkiraan biaya per bulan</span>
        <strong id="totalCost" data-testid="planner-total">Rp 0</strong>

        <ul class="cost-notes">
            <li data-testid="planner-note-unknown" id="unknownNote">Komponen yang belum diisi ditandai "belum diketahui" — tidak dianggap Rp0.</li>
            <li>Hasil ini <strong>estimasi dari inputmu</strong>, bukan tarif resmi pemilik — kecuali angka yang diisi otomatis dari data kost terverifikasi.</li>
            <li>Biaya yang sudah termasuk sewa tidak dihitung dua kali.</li>
        </ul>
    </div>

</section>

<script>
(function () {
    const ids = ['rent', 'electricity', 'internet', 'laundry', 'transport', 'food', 'daily', 'other'];
    const labels = { rent: 'Sewa kost', electricity: 'Listrik', internet: 'Internet', laundry: 'Laundry', transport: 'Transportasi', food: 'Makan', daily: 'Kebutuhan harian', other: 'Lainnya' };
    const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

    const select = document.getElementById('kostSelect');
    if (select) {
        select.addEventListener('change', () => {
            if (select.value) document.getElementById('kostPicker').submit();
        });
    }

    function recalc() {
        let total = 0;
        const parts = [];
        let unknownCount = 0;

        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.disabled) return;
            const value = el.value.trim();
            if (value === '') {
                unknownCount++;
                parts.push(labels[id] + ': <em>belum diketahui</em>');
                return;
            }
            const num = Number(value) || 0;
            total += num;
            parts.push(labels[id] + ': ' + rupiah(num));
        });

        document.getElementById('totalCost').textContent = rupiah(total);
        document.getElementById('breakdown').innerHTML = parts.map((p) => '<div class="breakdown-row">' + p + '</div>').join('');

        const unknown = document.getElementById('unknownNote');
        unknown.textContent = unknownCount > 0
            ? unknownCount + ' komponen belum diketahui dan tidak dihitung — hasil ini kemungkinan lebih rendah dari realita.'
            : 'Semua komponen sudah diisi.';

        const extras = ids.filter((i) => i !== 'rent')
            .reduce((sum, i) => { const el = document.getElementById(i); return sum + (el && !el.disabled && el.value !== '' ? (Number(el.value) || 0) : 0); }, 0);

        const link = document.getElementById('sendToMatch');
        if (link) {
            const params = new URLSearchParams();
            const rent = Number(document.getElementById('rent')?.value) || 0;
            if (rent > 0) params.set('budget', rent);
            if (extras > 0) params.set('extras', extras);
            const totalBudget = rent + extras;
            if (totalBudget > 0) params.set('total_budget', totalBudget);
            link.href = '{{ route('recommendations') }}' + (params.toString() ? '?' + params.toString() : '');
        }
    }

    ids.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', recalc);
    });

    recalc();
})();
</script>

@endsection
