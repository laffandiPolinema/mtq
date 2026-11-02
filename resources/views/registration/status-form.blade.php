@extends('layouts.app')

@section('title', 'Cek Status Pendaftaran · MTQ Kota Malang 2026')

@section('content')
    <section class="narrow-section">
        <div class="result-card">
            <span class="section-kicker">Area peserta</span>
            <h1 class="display-title">Cek status<br>pendaftaran</h1>
            <p>Masukkan kode pendaftaran beserta nomor WhatsApp yang diisi saat mendaftar untuk melihat status dan mengunggah bukti pembayaran.</p>
            @if (session('info')) <div class="notice notice-info">{{ session('info') }}</div> @endif
            <form class="lookup-form" action="{{ route('status.lookup') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="registration_code">Kode pendaftaran</label>
                    <input id="registration_code" name="registration_code" value="{{ old('registration_code', request('kode')) }}" placeholder="Contoh: MTQ26-XXXXXXXXXX" autocapitalize="characters" required>
                    @error('registration_code') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="phone">Nomor WhatsApp terdaftar</label>
                    <input id="phone" name="phone" type="tel" placeholder="08xxxxxxxxxx" autocomplete="tel" required @if (request('kode')) autofocus @endif>
                    @error('phone') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <button class="button button-primary" type="submit">Periksa status <span aria-hidden="true">→</span></button>
            </form>
            <a class="text-link" href="{{ route('home') }}">Kembali ke halaman utama</a>
        </div>
    </section>
@endsection
