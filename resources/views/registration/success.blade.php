@extends('layouts.app')

@section('title', 'Pendaftaran Berhasil · MTQ Kota Malang 2026')

@section('content')
    <section class="narrow-section">
        <div class="result-card">
            <div class="result-icon">✓</div>
            <span class="section-kicker">Formulir sudah diterima</span>
            <h1 class="display-title">Terima kasih,<br>{{ $participant->full_name }}.</h1>
            <p>Data pendaftaran Anda tersimpan. Langkah berikutnya: <strong>transfer biaya pendaftaran</strong> lalu unggah buktinya di bawah ini.</p>
            <div class="code-box"><span>Kode pendaftaran</span><strong>{{ $participant->registration_code }}</strong></div>
            <a class="button button-primary" href="{{ route('registration.receipt', $participant->registration_code) }}" target="_blank" rel="noopener">⬇ Unduh bukti pendaftaran (PDF)</a>
            <p class="result-note">Simpan atau catat kode ini. Untuk membuka status dan mengunggah bukti pembayaran dari perangkat lain, masukkan kode ini bersama nomor WhatsApp yang Anda daftarkan.</p>
        </div>

        @include('partials.payment-card')

        <div class="result-actions">
            <a class="button button-outline" href="{{ route('status.show', $participant->registration_code) }}">Lihat status pendaftaran <span aria-hidden="true">→</span></a>
            <a class="text-link" href="{{ route('home') }}">Kembali ke halaman utama</a>
        </div>
    </section>
@endsection
