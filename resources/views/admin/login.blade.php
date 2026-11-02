@extends('layouts.app')

@section('title', 'Login Panitia · MTQ Kota Malang 2026')

@section('content')
    <section class="narrow-section">
        <div class="result-card">
            <span class="section-kicker">Khusus panitia</span>
            <h1 class="display-title">Masuk ke<br>panel panitia</h1>
            <p>Gunakan akun panitia yang telah dibuat oleh administrator sistem.</p>
            <form class="lookup-form" action="{{ route('admin.login') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                    @error('email') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    @error('password') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <button class="button button-primary" type="submit">Masuk <span aria-hidden="true">→</span></button>
            </form>
            <a class="text-link" href="{{ route('home') }}">Kembali ke halaman utama</a>
        </div>
    </section>
@endsection
