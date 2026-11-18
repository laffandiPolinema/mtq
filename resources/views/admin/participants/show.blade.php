@extends('layouts.app')

@section('title', $participant->full_name.' · Panel Panitia MTQ 2026')

@section('content')
    @php($documentRoutes = ['identity_document_path' => 'identitas', 'photo_path' => 'pasfoto', 'payment_proof_path' => 'bukti-pembayaran'])
    <section class="admin-section">
        <a class="text-link back-link" href="{{ route('admin.participants.index') }}">← Kembali ke data peserta</a>
        <div class="admin-heading">
            <div><span class="section-kicker">{{ $participant->registration_code }}</span><h1 class="display-title">{{ $participant->full_name }}</h1><p>Terdaftar {{ $participant->created_at->translatedFormat('d F Y, H.i') }}</p></div>
            <div class="heading-actions">
            <a class="button button-outline" href="{{ route('admin.participants.receipt', $participant) }}" target="_blank" rel="noopener">⬇ Bukti pendaftaran</a>
            <form action="{{ route('admin.participants.destroy', $participant) }}" method="POST" onsubmit="return confirm({{ Js::from("Hapus data {$participant->full_name} ({$participant->registration_code})?\n\nData dan semua dokumen akan dihapus permanen dan tidak bisa dikembalikan.") }})">
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
                    <div><dt>NIK</dt><dd>{{ $participant->nik ?? '—' }}</dd></div>
                    <div><dt>Tanggal lahir</dt><dd>{{ $participant->birth_place ? $participant->birth_place.', ' : '' }}{{ $participant->birth_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt>Jenis kelamin</dt><dd>{{ $participant->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                    <div><dt>Alamat domisili</dt><dd>{{ $participant->address }}, Kec. {{ $participant->district }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $participant->phone }}</dd></div>
                    <div><dt>Asal sekolah/lembaga</dt><dd>{{ $participant->institution ?: '—' }}</dd></div>
                    <div><dt>Alamat sekolah</dt><dd>{{ $participant->school_address ?: '—' }}</dd></div>
                    <div><dt>Cabang</dt><dd>{{ $participant->branchLabel() }}{{ $participant->categoryLabel() ? ' · '.$participant->categoryLabel() : '' }}</dd></div>
                </dl>

                <form class="status-form status-form-stacked" action="{{ route('admin.participants.status', $participant) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="field">
                        <label for="status">Status data peserta</label>
                        <select id="status" name="status">@foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(old('status', $participant->status) === $key)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="field">
                        <label for="status_note">Catatan perbaikan <small>wajib jika “Perlu perbaikan”, terlihat oleh peserta</small></label>
                        <input id="status_note" name="status_note" value="{{ old('status_note', $participant->status_note) }}" placeholder="Mis. pasfoto buram, mohon unggah ulang">
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
                    <p>Cocokkan dengan <strong>mutasi rekening {{ config('mtq.bank.name') }}</strong>, bukan hanya dengan gambar bukti transfer. Kode unik: <strong>{{ $participant->formattedPaymentUniqueCode() }}</strong></p>
                </div>

                @if ($duplicateProofOwners->isNotEmpty())
                    <div class="notice notice-danger">
                        <strong>Peringatan:</strong> file bukti transfer ini identik dengan milik
                        @foreach ($duplicateProofOwners as $owner)
                            <a class="text-link" href="{{ route('admin.participants.show', $owner) }}">{{ $owner->full_name }} ({{ $owner->registration_code }})</a>@if (! $loop->last), @endif
                        @endforeach.
                        Kemungkinan bukti dipakai ulang.
                    </div>
                @endif

                @if ($participant->payment_proof_path)
                    <p class="result-note">Bukti diunggah {{ $participant->payment_uploaded_at?->translatedFormat('d F Y, H.i') }}.</p>
                    @if ($participant->payment_status === 'verified')
                        <p class="result-note">Ditandai lunas oleh <strong>{{ $participant->paymentVerifier?->name ?? 'panitia' }}</strong> pada {{ $participant->payment_verified_at?->translatedFormat('d F Y, H.i') }}.</p>
                    @endif

                    <div class="payment-actions">
                        <form action="{{ route('admin.participants.payment', $participant) }}" method="POST" onsubmit="return confirm({{ Js::from("Tandai lunas?\n\nPastikan dana {$participant->formattedPaymentAmount()} sudah masuk di mutasi rekening.") }})">
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

        <div class="panel documents-panel">
            <h2>Dokumen persyaratan</h2>
            <div class="document-grid">
                @foreach (\App\Models\Participant::DOCUMENTS as $column => $label)
                    @php($url = $participant->{$column} ? route('admin.participants.document', [$participant, $documentRoutes[$column]]) : null)
                    <figure class="document-card">
                        @if ($url === null)
                            <div class="document-empty">Belum diunggah</div>
                        @elseif ($participant->documentIsPdf($column))
                            <a class="document-empty" href="{{ $url }}">📄 Unduh PDF</a>
                        @else
                            <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="{{ $label }} {{ $participant->full_name }}" loading="lazy"></a>
                        @endif
                        <figcaption>{{ $label }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="result-note">Dokumen disimpan terenkripsi dan hanya dapat dibuka oleh panitia yang login.</p>
        </div>
    </section>
@endsection
