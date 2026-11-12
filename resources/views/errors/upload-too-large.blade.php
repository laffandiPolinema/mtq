@extends('layouts.app')

@section('title', 'File Terlalu Besar · MTQ Kota Malang 2026')

@section('content')
    <section class="narrow-section">
        <div class="result-card">
            <span class="section-kicker">Unggah dokumen</span>
            <h1 class="display-title">File terlalu<br>besar</h1>
            <p>Dokumen belum terkirim. Perkecil ukuran file (misalnya foto ulang dengan resolusi lebih rendah), lalu kirim kembali.</p>
            <button class="button button-primary" type="button" onclick="history.back()">Kembali ke formulir <span aria-hidden="true">→</span></button>
            <a class="text-link" href="{{ route('home') }}">Kembali ke halaman utama</a>
        </div>
    </section>
@endsection
