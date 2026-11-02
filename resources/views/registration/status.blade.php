@extends('layouts.app')

@section('title', 'Status Pendaftaran · MTQ Kota Malang 2026')

@section('content')
    <section class="narrow-section">
        <div class="result-card">
            <span class="section-kicker">Status pendaftaran</span>
            <h1 class="display-title">{{ $participant->full_name }}</h1>
            <p>Kode <strong>{{ $participant->registration_code }}</strong></p>
            <a class="button button-primary" href="{{ route('registration.receipt', $participant->registration_code) }}" target="_blank" rel="noopener">⬇ Unduh bukti pendaftaran (PDF)</a>
            <div class="status-banner status-{{ $participant->status }}">
                <span class="status-dot"></span>Data peserta: {{ \App\Models\Participant::STATUSES[$participant->status] }}
            </div>
            <dl class="summary-list">
                <div><dt>Cabang</dt><dd>{{ $participant->branchLabel() }}</dd></div>
                @if ($participant->categoryLabel())
                    <div><dt>Kategori</dt><dd>{{ $participant->categoryLabel() }}</dd></div>
                @endif
                <div><dt>Kecamatan</dt><dd>{{ $participant->district }}</dd></div>
                <div><dt>Tanggal daftar</dt><dd>{{ $participant->created_at->translatedFormat('d F Y') }}</dd></div>
                <div><dt>Pembayaran</dt><dd>{{ $participant->paymentStatusLabel() }}</dd></div>
            </dl>
            @if ($participant->status === 'rejected')
                <p class="result-note">Data Anda perlu diperbaiki. Silakan hubungi panitia melalui kontak di bagian bawah halaman ini.</p>
            @elseif ($participant->status === 'pending')
                <p class="result-note">Panitia sedang memeriksa data Anda. Periksa kembali status ini secara berkala.</p>
            @else
                <p class="result-note">Pendaftaran Anda telah diverifikasi. Sampai bertemu di MTQ Kota Malang 2026!</p>
            @endif
        </div>

        @include('partials.payment-card')

        <div class="result-actions">
            <a class="button button-outline" href="{{ route('status.form') }}">Cek kode lain</a>
            <a class="text-link" href="{{ route('home') }}">Kembali ke halaman utama</a>
        </div>
    </section>
@endsection
