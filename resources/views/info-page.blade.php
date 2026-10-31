@extends('layouts.app')

@section('title', $title . ' — Kostly.')

@section('content')

<section class="page-hero">

    <div class="section">

        <div class="section-label">
            KOSTLY INFORMATION
        </div>

        <h1 class="page-title">
            {{ $title }}
        </h1>

        <p class="page-description">
            {{ $subtitle }}
        </p>

    </div>

</section>


<section class="section">

    @if($type === 'help')

        <div class="info-grid">

            <div class="info-card">

                <h3>
                    Bagaimana cara mencari kost?
                </h3>

                <p>
                    Gunakan halaman Cari Kost untuk memfilter
                    pilihan berdasarkan lokasi dan budget.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Bagaimana melihat detail?
                </h3>

                <p>
                    Klik pilihan kost untuk melihat harga,
                    fasilitas, lokasi, jarak ke kampus, dan informasi lainnya.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Bagaimana melakukan booking?
                </h3>

                <p>
                    Masuk sebagai penyewa, buka detail kost,
                    kemudian pilih Booking Kost.
                </p>

            </div>

        </div>


    @elseif($type === 'booking')

        <div class="steps">

            <div class="step-card">
                <span>01</span>
                <h3>Masuk sebagai Penyewa</h3>
                <p>Gunakan akun penyewa untuk mengakses fitur booking.</p>
            </div>

            <div class="step-card">
                <span>02</span>
                <h3>Pilih Kost</h3>
                <p>Cari kost berdasarkan lokasi, budget, dan kebutuhanmu.</p>
            </div>

            <div class="step-card">
                <span>03</span>
                <h3>Ajukan Booking</h3>
                <p>Tentukan durasi dan tanggal mulai tinggal.</p>
            </div>

            <div class="step-card">
                <span>04</span>
                <h3>Pantau Status</h3>
                <p>Lihat status booking melalui Riwayat Booking.</p>
            </div>

        </div>


    @else

        <div class="contact-card">

            <div class="section-label">
                CONTACT
            </div>

            <h2>
                Ada yang ingin ditanyakan?
            </h2>

            <p>
                Untuk kebutuhan proyek ini, halaman kontak
                dapat dikembangkan menjadi sistem pesan,
                WhatsApp, atau email resmi Kostly.
            </p>

            <div class="contact-item">
                <strong>Email</strong>
                <span>hello@kostly.id</span>
            </div>

            <div class="contact-item">
                <strong>Jam Layanan</strong>
                <span>Senin–Jumat · 08.00–17.00</span>
            </div>

        </div>

    @endif

</section>

@endsection