@php($bank = config('mtq.bank'))
@php($amount = $participant->formattedPaymentAmount())
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Judul dokumen menjadi nama file bawaan saat disimpan sebagai PDF. --}}
    <title>Bukti-Pendaftaran-{{ $participant->registration_code }}</title>
    @fonts
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; color: #2b2d2c; background: #e9e7e0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; font-size: 13px; }
        .toolbar { position: sticky; top: 0; z-index: 10; padding: 12px; display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; background: #0a4a2a; }
        .toolbar button, .toolbar a { padding: 10px 20px; border: 0; border-radius: 999px; font: inherit; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { color: #06331d; background: linear-gradient(90deg, #a8781c, #f6dc85 35%, #d4a63a 60%, #fbe7a1); }
        .toolbar a { color: #fff; background: rgb(255 255 255 / .12); }
        .toolbar p { width: 100%; margin: 0; color: #cfe0d6; font-size: 12px; text-align: center; }
        .sheet { position: relative; overflow: hidden; width: 210mm; min-height: 297mm; margin: 20px auto; padding: 22mm 18mm 26mm; background: #fbfaf6; box-shadow: 0 10px 40px rgb(0 0 0 / .18); }
        .svg-sprite { position: absolute; width: 0; height: 0; }
        .corner { position: absolute; top: 0; right: 0; width: 82mm; }
        .corner svg { display: block; width: 100%; height: auto; }
        .base { position: absolute; left: 0; right: 0; bottom: 0; height: 9mm; background: linear-gradient(90deg, #a8781c, #f6dc85 35%, #d4a63a 60%, #fbe7a1); }
        .base-lanterns { position: absolute; left: 10mm; bottom: 6mm; display: flex; align-items: flex-end; }
        .base-lanterns svg:first-child { width: 11mm; } .base-lanterns svg:last-child { width: 7mm; }
        .kicker { color: #9a6b14; font-size: 10px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 6px 0 4px; color: #0b6b3a; font-size: 34px; font-weight: 800; line-height: 1; text-transform: uppercase; }
        .event { margin: 0; max-width: 115mm; color: #333; font-size: 13px; font-weight: 800; line-height: 1.4; text-transform: uppercase; }
        .organizer { margin: 6px 0 0; color: #0b6b3a; font-size: 12px; font-weight: 700; }
        .code { margin: 10mm 0 7mm; padding: 6mm 7mm; display: flex; justify-content: space-between; align-items: center; gap: 6mm; background: #e8f1eb; border: 1.5px dashed #9cc2ab; border-radius: 14px; }
        .code span { display: block; color: #6b716d; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .code strong { display: block; margin-top: 3px; color: #0a4a2a; font-size: 26px; font-weight: 800; letter-spacing: .06em; }
        .code .date { text-align: right; }
        .code .date strong { font-size: 14px; letter-spacing: 0; }
        h2 { margin: 0 0 3mm; color: #0b6b3a; font-size: 15px; font-weight: 800; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: 1.15fr 1fr; gap: 7mm; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2.2mm 0; border-bottom: 1px solid #e6e3da; vertical-align: top; }
        td:first-child { width: 36%; color: #6b716d; }
        td:last-child { font-weight: 700; }
        .amount { padding: 5mm; text-align: center; background: linear-gradient(160deg, #fffaf0, #fdf3d8); border: 1px solid #efdca3; border-radius: 14px; }
        .amount span { color: #9a6b14; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .amount strong { display: block; margin: 2mm 0; color: #06331d; font-size: 30px; font-weight: 800; }
        .amount em { padding: 0 3px; font-style: normal; background: linear-gradient(90deg, #a8781c, #f6dc85 35%, #d4a63a 60%, #fbe7a1); border-radius: 5px; }
        .amount small { display: block; color: #6e6650; font-size: 11px; }
        .bank { margin-top: 4mm; padding: 4mm; text-align: center; background: #fff; border-radius: 14px; box-shadow: inset 0 0 0 1px #e6e3da; }
        .bank b { display: block; color: #1756a9; font-size: 12px; text-transform: uppercase; }
        .bank strong { display: block; margin-top: 1mm; color: #0b6b3a; font-size: 17px; font-weight: 800; }
        .pill { display: inline-block; padding: 1.2mm 3mm; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .pill-unpaid { color: #5f6662; background: #eeeeea; }
        .pill-waiting, .pill-pending { color: #8a6514; background: #fbf3dc; }
        .pill-verified, .pill-approved { color: #0b6b3a; background: #e3f2e8; }
        .pill-rejected { color: #a3402c; background: #fbe9e4; }
        .notes { margin-top: 8mm; padding: 5mm 6mm; background: #fff; border-left: 4px solid #d4a63a; border-radius: 0 12px 12px 0; }
        .notes ol { margin: 2mm 0 0; padding-left: 5mm; line-height: 1.7; }
        .footer { margin-top: 8mm; display: flex; justify-content: space-between; gap: 6mm; color: #6b716d; font-size: 11px; line-height: 1.6; }
        .footer strong { color: #0a4a2a; }
        .credit { position: absolute; left: 30mm; right: 18mm; bottom: 13mm; margin: 0; padding-top: 3mm; border-top: 1px dashed #e2d4a8; color: #6b716d; font-size: 10.5px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 3mm; }
        .credit-logo { width: auto; height: 8mm; flex: none; }
        .credit strong { color: #0a4a2a; }
        @media print {
            body { background: none; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; }
        }
        @media screen and (max-width: 820px) {
            .sheet { width: auto; min-height: 0; margin: 12px; padding: 64px 18px 90px; }
            .corner { width: 150px; }
            .grid { grid-template-columns: 1fr; }
            .code { flex-direction: column; align-items: flex-start; }
            .code .date { text-align: left; }
            .footer { flex-direction: column; }
            .credit { position: static; margin-top: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">⬇ Unduh PDF / Cetak</button>
        <a href="{{ $backUrl }}">← Kembali</a>
        <p>Pada jendela cetak, pilih tujuan <strong>“Simpan sebagai PDF”</strong> untuk mengunduh dokumen.</p>
    </div>

    <article class="sheet">
        @include('partials.ornament-sprite')
        <div class="corner" aria-hidden="true">
            <svg viewBox="0 0 420 300"><path d="M150 0 H420 V252 C396 176 340 122 270 99 C214 81 171 52 150 0 Z" fill="url(#g-green)"/><path d="M120 0 C148 64 204 88 268 106 C342 128 398 184 420 270" fill="none" stroke="url(#g-gold)" stroke-width="7" stroke-linecap="round"/><use href="#lantern" x="286" y="112" width="34" height="78"/><use href="#lantern" x="333" y="128" width="40" height="112"/><use href="#mandala" x="300" y="6" width="112" height="112"/><use href="#mandala" x="226" y="46" width="58" height="58"/></svg>
        </div>

        <span class="kicker">Dokumen resmi pendaftaran</span>
        <h1>Bukti Pendaftaran</h1>
        <p class="event">Musabaqah Tilawatil Qur’an (MTQ) Tingkat Kota Malang Tahun 2026</p>
        <p class="organizer">PHBI &amp; Ketakmiran Masjid Jami’ Blimbing Kota Malang</p>

        <div class="code">
            <div><span>Nomor registrasi</span><strong>{{ $participant->registration_code }}</strong></div>
            <div class="date"><span>Tanggal daftar</span><strong>{{ $participant->created_at->translatedFormat('d F Y, H.i') }} WIB</strong></div>
        </div>

        <div class="grid">
            <section>
                <h2>Data peserta</h2>
                <table>
                    <tr><td>Nama lengkap</td><td>{{ $participant->full_name }}</td></tr>
                    <tr><td>NIK</td><td>{{ $participant->maskedNik() ?? '—' }}</td></tr>
                    <tr><td>Tanggal lahir</td><td>{{ $participant->birth_date->translatedFormat('d F Y') }}</td></tr>
                    <tr><td>Jenis kelamin</td><td>{{ $participant->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                    <tr><td>Kecamatan</td><td>{{ $participant->district }}</td></tr>
                    <tr><td>Asal sekolah</td><td>{{ $participant->institution ?: '—' }}</td></tr>
                    <tr><td>WhatsApp</td><td>{{ $participant->phone }}</td></tr>
                    <tr><td>Cabang lomba</td><td>{{ $participant->branchLabel() }}{{ $participant->categoryLabel() ? ' · '.$participant->categoryLabel() : '' }}</td></tr>
                    <tr><td>Status data</td><td><span class="pill pill-{{ $participant->status }}">{{ \App\Models\Participant::STATUSES[$participant->status] }}</span></td></tr>
                </table>
            </section>

            <section>
                <h2>Pembayaran</h2>
                <div class="amount">
                    <span>Total transfer</span>
                    <strong>{{ substr($amount, 0, -3) }}<em>{{ substr($amount, -3) }}</em></strong>
                    <small>Biaya Rp {{ number_format($participant->registration_fee, 0, ',', '.') }} + kode unik {{ $participant->formattedPaymentUniqueCode() }}</small>
                    <p style="margin: 3mm 0 0;"><span class="pill pill-{{ $participant->payment_status }}">{{ $participant->paymentStatusLabel() }}</span></p>
                </div>
                <div class="bank">
                    <b>Rekening {{ $bank['name'] }}</b>
                    <strong>{{ $bank['account_number'] }}</strong>
                    <small>a.n. {{ $bank['account_name'] }}</small>
                </div>
            </section>
        </div>

        <div class="notes">
            <h2>Catatan</h2>
            <ol>
                <li>Transfer <strong>tepat {{ $amount }}</strong> (termasuk 3 digit kode unik) agar pembayaran dapat dikenali.</li>
                <li>Unggah bukti transfer dan pantau status melalui menu <strong>Cek pendaftaran</strong> dengan nomor registrasi dan nomor WhatsApp terdaftar.</li>
                <li>Ikuti <strong>technical meeting</strong> (H-5) pada <strong>{{ \Illuminate\Support\Carbon::parse(config('mtq.technical_meeting_date'))->translatedFormat('l, j F Y') }}</strong>; tempat dan waktu diumumkan panitia.</li>
                <li>Simpan dokumen ini dan tunjukkan saat registrasi ulang pada hari pelaksanaan.</li>
            </ol>
        </div>

        <div class="footer">
            <div><strong>Pelaksanaan</strong><br>Sabtu–Minggu, 26–27 Desember 2026<br>Pukul 07.30 WIB s.d. selesai<br>Malang Creative Center (MCC) Lt. 2, Blimbing</div>
            <div><strong>Kontak panitia</strong><br>@foreach (config('mtq.contacts') as $contact){{ $contact['name'] }} · {{ implode('.', str_split($contact['phone'], 3)) }}<br>@endforeach</div>
            <div style="text-align: right;"><strong>Dicetak</strong><br>{{ now()->translatedFormat('d F Y, H.i') }} WIB</div>
        </div>

        <p class="credit"><img class="credit-logo" src="{{ asset('images/logo-jti-polinema.png') }}" alt="Logo Jurusan Teknologi Informasi POLINEMA" width="500" height="526"><span>Sistem pendaftaran didukung oleh <strong>Jurusan Teknologi Informasi · Politeknik Negeri Malang (POLINEMA)</strong></span></p>

        <div class="base-lanterns" aria-hidden="true">
            <svg viewBox="8 22 24 68"><use href="#lantern-body"/></svg>
            <svg viewBox="8 22 24 68"><use href="#lantern-body"/></svg>
        </div>
        <div class="base" aria-hidden="true"></div>
    </article>
</body>
</html>
