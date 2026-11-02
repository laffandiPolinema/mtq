@extends('layouts.app')

@section('title', $participant->full_name.' · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section">
        <a class="text-link back-link" href="{{ route('admin.participants.index') }}">← Kembali ke data peserta</a>
        <div class="admin-heading">
            <div><span class="section-kicker">{{ $participant->registration_code }}</span><h1 class="display-title">{{ $participant->full_name }}</h1><p>Terdaftar {{ $participant->created_at->translatedFormat('d F Y, H.i') }}</p></div>
            <div class="heading-actions">
            <a class="button button-outline" href="{{ route('admin.participants.receipt', $participant) }}" target="_blank" rel="noopener">⬇ Bukti pendaftaran</a>
            <form action="{{ route('admin.participants.destroy', $participant) }}" method="POST" onsubmit="return confirm({{ Js::from("Hapus data {$participant->full_name} ({$participant->registration_code})?\n\nData dan bukti pembayaran akan dihapus permanen dan tidak bisa dikembalikan.") }})">
                @csrf @method('DELETE')
                <button class="button button-danger" type="submit">Hapus peserta</button>
            </form>
            </div>
        </div>

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="notice notice-danger">{{ $errors->first() }}</div> @endif

        <div class="admin-detail-grid">
            <div class="panel">
                <h2>Data peserta</h2>
                <dl class="summary-list">
                    <div><dt>Jenis kelamin</dt><dd>{{ $participant->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                    <div><dt>Tempat, tanggal lahir</dt><dd>{{ $participant->birth_place }}, {{ $participant->birth_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt>Kecamatan</dt><dd>{{ $participant->district }}</dd></div>
                    <div><dt>Alamat</dt><dd>{{ $participant->address }}</dd></div>
                    <div><dt>Lembaga</dt><dd>{{ $participant->institution ?: '—' }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $participant->phone }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $participant->email ?: '—' }}</dd></div>
                    <div><dt>Cabang</dt><dd>{{ $participant->branchLabel() }}{{ $participant->categoryLabel() ? ' · '.$participant->categoryLabel() : '' }}</dd></div>
                </dl>

                <form class="status-form" action="{{ route('admin.participants.status', $participant) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="field">
                        <label for="status">Status data peserta</label>
                        <select id="status" name="status">@foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected($participant->status === $key)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <button class="button button-primary" type="submit">Simpan status</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Pembayaran</h2>
                    <span class="status-pill payment-{{ $participant->payment_status }}">{{ $participant->paymentStatusLabel() }}</span>
                </div>
                <div class="amount-box amount-box-compact">
                    <span>Nominal yang harus masuk</span>
                    <strong>{{ substr($participant->formattedPaymentAmount(), 0, -3) }}<em>{{ substr($participant->formattedPaymentAmount(), -3) }}</em></strong>
                    <p>Cocokkan dengan mutasi rekening {{ config('mtq.bank.name') }}. Kode unik: <strong>{{ $participant->formattedPaymentUniqueCode() }}</strong></p>
                </div>

                @if ($participant->payment_proof_path)
                    <div class="proof-viewer">
                        @if ($participant->paymentProofIsPdf())
                            <a class="button button-outline" href="{{ route('admin.participants.payment-proof', $participant) }}" target="_blank" rel="noopener">Buka bukti transfer (PDF)</a>
                        @else
                            <a href="{{ route('admin.participants.payment-proof', $participant) }}" target="_blank" rel="noopener"><img src="{{ route('admin.participants.payment-proof', $participant) }}" alt="Bukti transfer {{ $participant->full_name }}"></a>
                        @endif
                        <small>Diunggah {{ $participant->payment_uploaded_at?->translatedFormat('d F Y, H.i') }}</small>
                    </div>

                    <div class="payment-actions">
                        <form action="{{ route('admin.participants.payment', $participant) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="payment_status" value="verified">
                            <button class="button button-primary" type="submit" @disabled($participant->payment_status === 'verified')>✓ Tandai lunas</button>
                        </form>
                        <form class="reject-form" action="{{ route('admin.participants.payment', $participant) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="payment_status" value="rejected">
                            <div class="field">
                                <label for="payment_note">Alasan penolakan</label>
                                <input id="payment_note" name="payment_note" value="{{ old('payment_note', $participant->payment_note) }}" placeholder="Mis. nominal tidak sesuai / bukti tidak terbaca" required>
                            </div>
                            <button class="button button-danger" type="submit">Tolak bukti</button>
                        </form>
                    </div>
                @else
                    <p class="result-note">Peserta belum mengunggah bukti pembayaran.</p>
                @endif
            </div>
        </div>
    </section>
@endsection
