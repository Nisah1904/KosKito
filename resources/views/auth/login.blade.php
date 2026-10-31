@extends('layouts.app')

@section('title', 'Masuk — Kostly.')

@section('content')

<div class="login-wrapper">

    <div class="login-box">

        <div class="section-label">WELCOME BACK</div>

        <h1>Selamat datang di Kostly.</h1>

        <p class="section-subtitle">
            Masuk untuk booking, favorit, dan mengelola kost. Pencarian & kalkulator bisa dipakai tanpa akun.
        </p>


        <form method="POST" action="{{ route('login.process') }}" data-testid="login-form">

            @csrf


            <div class="form-group">

                <label>Email</label>

                <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" required data-testid="login-email">

            </div>


            <div class="form-group">

                <label>Password</label>

                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required data-testid="login-password">

            </div>


            @if($errors->any())

                <div class="form-error" data-testid="login-errors">

                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach

                </div>

            @endif


            <button type="submit" class="full-button" data-testid="login-submit">Masuk</button>

        </form>


        <p class="auth-switch">
            Belum punya akun?
            <a href="{{ route('register') }}" data-testid="login-to-register">Daftar sekarang</a>
        </p>

    </div>

</div>

@endsection
