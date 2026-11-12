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
            <ul class="document-list">
                @foreach (\App\Models\Participant::DOCUMENTS as $column => $label)
                    <li><span>{{ $label }}</span>@if ($participant->{$column})<b>✓ Terunggah</b>@else<b class="missing">Belum</b>@endif</li>
                @endforeach
            </ul>

            @if ($participant->status === 'rejected')
                <div class="notice notice-danger"><strong>Perlu perbaikan:</strong> {{ $participant->status_note ?: 'Silakan hubungi panitia melalui kontak di bagian bawah halaman ini.' }}</div>
                <form class="upload-form" action="{{ route('registration.documents.update', $participant->registration_code) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="upload-grid">
                        <label class="upload-drop" for="identity_document">
                            <span class="upload-icon" aria-hidden="true">⬆</span>
                            <span><strong>Ganti identitas diri</strong><small data-file-label>JPG, PNG, PDF · maks. 5 MB</small></span>
                            <input id="identity_document" name="identity_document" type="file" accept="image/jpeg,image/png,image/webp,application/pdf">
                        </label>
                        <label class="upload-drop" for="photo">
                            <span class="upload-icon" aria-hidden="true">⬆</span>
                            <span><strong>Ganti pasfoto 3x4</strong><small data-file-label>JPG, PNG · maks. 2 MB</small></span>
                            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                        </label>
                    </div>
                    @foreach (['documents', 'identity_document', 'photo'] as $field)
                        @error($field) <small class="field-error">{{ $message }}</small> @enderror
                    @endforeach
                    <button class="button button-primary" type="submit">Kirim dokumen perbaikan</button>
                </form>
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
