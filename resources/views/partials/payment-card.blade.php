@php($bank = config('mtq.bank'))
<section class="payment-card" id="pembayaran">
    <div class="payment-head">
        <div>
            <span class="section-kicker">Biaya pendaftaran</span>
            <h2>Pembayaran</h2>
        </div>
        <span class="status-pill payment-{{ $participant->payment_status }}">{{ $participant->paymentStatusLabel() }}</span>
    </div>

    <div class="amount-box">
        <span>Total yang harus ditransfer</span>
        @php($amount = $participant->formattedPaymentAmount())
        <strong>{{ substr($amount, 0, -3) }}<em>{{ substr($amount, -3) }}</em></strong>
        <button class="copy-button" type="button" data-copy="{{ $participant->paymentAmount() }}">Salin nominal</button>
        <dl>
            <div><dt>Biaya pendaftaran</dt><dd>Rp {{ number_format($participant->registration_fee, 0, ',', '.') }}</dd></div>
            <div><dt>Kode unik</dt><dd>+ {{ $participant->formattedPaymentUniqueCode() }}</dd></div>
        </dl>
        <p>Transfer <strong>tepat sampai tiga digit terakhir</strong>. Kode unik membantu panitia mengenali pembayaran atas nama Anda.</p>
    </div>

    <div class="bank-card">
        <svg class="bank-ribbon" viewBox="0 0 200 30" aria-hidden="true"><path d="M4 18 C40 -4 70 34 100 14 C130 -6 160 30 196 10" fill="none" stroke="url(#g-gold)" stroke-width="9" stroke-linecap="round"/></svg>
        <span class="bank-name">Rekening {{ $bank['name'] }}</span>
        <small>Atas nama rekening</small>
        <strong class="bank-holder">{{ $bank['account_name'] }}</strong>
        <small>Nomor rekening</small>
        <strong class="bank-number">{{ $bank['account_number'] }}</strong>
        <button class="copy-button" type="button" data-copy="{{ preg_replace('/\D/', '', $bank['account_number']) }}">Salin nomor rekening</button>
    </div>

    @if (session('success'))
        <div class="notice notice-success">{{ session('success') }}</div>
    @endif

    @if ($participant->payment_status === 'rejected' && $participant->payment_note)
        <div class="notice notice-danger"><strong>Bukti sebelumnya ditolak:</strong> {{ $participant->payment_note }}</div>
    @endif

    @if ($participant->canUploadPaymentProof())
        <form class="upload-form" action="{{ route('registration.payment.store', $participant->registration_code) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="upload-drop" for="payment_proof">
                <span class="upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3l5 5h-3v6h-4V8H7l5-5zM5 17h14v3H5z" fill="currentColor"/></svg></span>
                <span><strong>Unggah bukti transfer</strong><small data-file-label>JPG, PNG, WEBP, atau PDF · maks. 5 MB</small></span>
                <input id="payment_proof" name="payment_proof" type="file" data-max-mb="5" data-label="Bukti transfer" accept="image/jpeg,image/png,image/webp,application/pdf" required>
            </label>
            @error('payment_proof') <small class="field-error">{{ $message }}</small> @enderror
            <button class="button button-primary" type="submit">Kirim bukti pembayaran <span aria-hidden="true">→</span></button>
        </form>
    @else
        @error('payment_proof') <small class="field-error">{{ $message }}</small> @enderror
        <p class="result-note">
            @if ($participant->payment_status === 'verified')
                Pembayaran telah dikonfirmasi panitia{{ $participant->payment_verified_at ? ' pada '.$participant->payment_verified_at->translatedFormat('d F Y') : '' }}. Jazākumullāhu khairan.
            @else
                Bukti transfer diunggah {{ $participant->payment_uploaded_at?->translatedFormat('d F Y, H.i') }} dan sedang diperiksa panitia.
            @endif
        </p>
    @endif
</section>
