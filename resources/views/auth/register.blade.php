@extends('layouts.app')

@section('title', 'Daftar — Kostly.')

@section('content')

<div class="login-wrapper">

    <div class="login-box">

        <div class="section-label">CREATE ACCOUNT</div>

        <h1>Mulai menggunakan Kostly.</h1>

        <p class="section-subtitle">
            Buat akun untuk menyimpan favorit, mengajukan booking, atau mendaftarkan kost.
        </p>


        <form method="POST" action="{{ route('register.process') }}" data-testid="register-form">

            @csrf

            <div class="form-group">

                <label>Nama</label>

                <input type="text" name="name" class="form-control" placeholder="Nama lengkap" value="{{ old('name') }}" required data-testid="register-name">

            </div>


            <div class="form-group">

                <label>Email</label>

                <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" required data-testid="register-email">

            </div>


            <div class="form-group">

                <label>Password (minimal 8 karakter)</label>

                <input type="password" name="password" class="form-control" placeholder="Buat password" required minlength="8" data-testid="register-password">

            </div>


            <div class="form-group">

                <label>Konfirmasi password</label>

                <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required data-testid="register-password-confirmation">

            </div>


            <div class="form-group">

                <label>Daftar sebagai</label>

                <div class="role-radios" data-testid="register-role">
                    <label class="check-chip">
                        <input type="radio" name="role" value="penyewa" @checked(old('role', 'penyewa')) required>
                        <span>Penyewa — cari & booking kost</span>
                    </label>
                    <label class="check-chip">
                        <input type="radio" name="role" value="pemilik" @checked(old('role') === 'pemilik')>
                        <span>Pemilik Kost — daftarkan kost</span>
                    </label>
                </div>

                <small>Registrasi admin dibuka melalui seeder, tidak melalui halaman publik.</small>

            </div>


            @if($errors->any())

                <div class="form-error" data-testid="register-errors">

                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach

                </div>

            @endif


            <button type="submit" class="full-button" data-testid="register-submit">Buat Akun</button>

        </form>


        <p class="auth-switch">

            Sudah punya akun?

            <a href="{{ route('login') }}">Masuk</a>

        </p>

    </div>

</div>

@endsection
