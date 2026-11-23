@extends('layouts.app')

@section('title', 'Pendaftaran Peserta · MTQ Kota Malang 2026')

@section('content')
    @php($schedule = app(\App\Services\EventSchedule::class))
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow eyebrow-{{ $period['status'] }}"><span class="eyebrow-dot"></span>
                @switch($period['status'])
                    @case('upcoming') Pendaftaran dibuka {{ $period['opens_at']->translatedFormat('d F Y') }} @break
                    @case('open') Pendaftaran dibuka s.d. {{ $period['closes_at']->translatedFormat('d F Y') }} @break
                    @default Pendaftaran telah ditutup
                @endswitch
            </span>
            <h1 class="hero-title">Pendaftaran<br>Peserta</h1>
            <p class="hero-subtitle">Musabaqah Tilawatil Qur’an (MTQ)<br>Tingkat Kota Malang Tahun 2026</p>
            <p class="hero-organizer"><span>Diselenggarakan oleh:</span><strong>PHBI &amp; Ketakmiran Masjid Jami’ Blimbing Kota Malang</strong></p>
            <div class="event-details">
                <div>
                    <span class="detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm-2 7h14v10H5z" fill="currentColor"/></svg></span>
                    <span><small>Tanggal</small><strong>{{ $schedule->eventDaysLabel(short: true) }}</strong></span>
                </div>
                <div>
                    <span class="detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 10.4 3.5 2-1 1.8L11 13V6h2z" fill="currentColor"/></svg></span>
                    <span><small>Waktu</small><strong>{{ $schedule->eventTime() }}</strong></span>
                </div>
                <div>
                    <span class="detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/></svg></span>
                    <span><small>Tempat</small><strong>{{ $schedule->eventPlace() }}</strong></span>
                </div>
                <div>
                    <span class="detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5zM8 12h8v2H8zm0 4h8v2H8z" fill="currentColor"/></svg></span>
                    <span><small>Pendaftaran</small><strong>{{ $period['opens_at']->translatedFormat('j M') }} – {{ $period['closes_at']->translatedFormat('j M Y') }}</strong></span>
                </div>
                <div>
                    <span class="detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 2c-2.7 0-8 1.3-8 4v2h10v-2c0-1 .4-1.9 1.1-2.6C10 13.2 8.8 13 8 13zm8 0c-2.7 0-8 1.3-8 4v2h16v-2c0-2.7-5.3-4-8-4z" fill="currentColor"/></svg></span>
                    <span><small>Technical meeting{{ $schedule->technicalMeetingOffset() ? ' ('.$schedule->technicalMeetingOffset().')' : '' }}</small><strong>{{ $schedule->technicalMeetingLabel() }}</strong>@if ($schedule->technicalMeetingNote() !== '')<small class="detail-note">{{ $schedule->technicalMeetingNote() }}</small>@endif</span>
                </div>
            </div>
            <div class="hero-actions">
                @if ($period['status'] === 'open')
                    <a class="button button-primary" href="#daftar">Daftar sekarang <span aria-hidden="true">→</span></a>
                @endif
                <a class="button button-outline" href="#juknis">Baca juknis</a>
                <a class="button button-outline" href="{{ route('status.form') }}">Cek status &amp; pembayaran</a>
            </div>
        </div>

        <div class="hero-art" aria-hidden="true">
            <svg viewBox="0 0 360 420" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="180" cy="372" rx="170" ry="16" fill="url(#g-gold)" opacity=".55"/>
                <path d="M8 372 V232 C8 206 30 190 46 176 C56 166 62 154 64 140 C66 154 72 166 82 176 C98 190 120 206 120 232 V372 Z" fill="url(#p-lattice)" stroke="url(#g-gold-v)" stroke-width="7"/>
                <path d="M240 372 V232 C240 206 262 190 278 176 C288 166 294 154 296 140 C298 154 304 166 314 176 C330 190 352 206 352 232 V372 Z" fill="url(#p-lattice)" stroke="url(#g-gold-v)" stroke-width="7"/>
                <path d="M62 372 V178 C62 132 106 108 134 84 C154 66 172 44 180 12 C188 44 206 66 226 84 C254 108 298 132 298 178 V372 Z" fill="url(#p-lattice)" stroke="url(#g-gold-v)" stroke-width="9"/>
                <rect x="94" y="132" width="172" height="104" rx="18" fill="#06331d" fill-opacity=".88" stroke="url(#g-gold)" stroke-width="2.5"/>
                <text x="180" y="200" text-anchor="middle" class="art-title">MTQ</text>
                <text x="180" y="224" text-anchor="middle" class="art-year">2026</text>
                <g transform="translate(180 300)">
                    <path d="M-62 56 L52 -8 M62 56 L-52 -8" stroke="#0b5a3c" stroke-width="13" stroke-linecap="round"/>
                    <path d="M-62 56 L52 -8 M62 56 L-52 -8" stroke="#1f7a57" stroke-width="5" stroke-linecap="round"/>
                    <path d="M0 2 C-24 -14 -52 -16 -78 -6 L-70 22 C-46 12 -22 14 0 28 Z" fill="#fffdf6" stroke="#d9c79b" stroke-width="2"/>
                    <path d="M0 2 C24 -14 52 -16 78 -6 L70 22 C46 12 22 14 0 28 Z" fill="#fffdf6" stroke="#d9c79b" stroke-width="2"/>
                    <path d="M-66 -1 C-44 -8 -22 -6 -6 6 M-62 7 C-42 0 -22 2 -6 13 M6 6 C22 -6 44 -8 66 -1 M6 13 C22 2 42 0 62 7" stroke="#cdbb8f" stroke-width="1.2" fill="none"/>
                </g>
                <g fill="#fff">
                    <circle cx="28" cy="388" r="26"/><circle cx="70" cy="394" r="22"/><circle cx="300" cy="392" r="24"/><circle cx="338" cy="386" r="26"/>
                </g>
            </svg>
            <p class="art-caption">“Dengan Al-Qur’an kita wujudkan masyarakat Kota Malang yang religius, berakhlak mulia, dan berdaya saing.”</p>
        </div>
    </section>

    <section class="theme-band">
        <span class="section-kicker">Tema acara</span>
        <blockquote>
            <span class="quote-mark" aria-hidden="true">“</span>
            Menggali potensi generasi Qur’ani yang berakhlak mulia, berprestasi, dan berdaya saing
            <span class="quote-mark" aria-hidden="true">”</span>
        </blockquote>
    </section>

    @php($prizes = app(\App\Services\PrizeList::class))
    @if ($prizes->items())
        <section class="prize-section" id="hadiah" aria-labelledby="hadiah-title">
            <div class="prize-band">
                <div class="prize-heading">
                    <span class="prize-kicker">Hadiah &amp; penghargaan</span>
                    <h2 id="hadiah-title">{{ $prizes->heading() }}</h2>
                    @if ($prizes->intro() !== '')<p>{{ $prizes->intro() }}</p>@endif
                </div>
                <ol class="prize-grid">
                    @foreach ($prizes->items() as $prize)
                        <li class="prize-card">
                            <span class="prize-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $prizes->iconPath($prize['icon']) }}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="prize-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $prize['title'] }}</h3>
                            @if ($prize['description'] !== '')<p>{{ $prize['description'] }}</p>@endif
                        </li>
                    @endforeach
                </ol>
                @if ($period['status'] === 'open')
                    <a class="button button-light" href="#daftar">Daftar &amp; raih hadiahnya <span aria-hidden="true">→</span></a>
                @endif
            </div>
        </section>
    @endif

    <section class="content-section" id="cabang">
        <div class="section-heading">
            <div><span class="section-kicker">Ruang untuk bertumbuh</span><h2 class="display-title">Cabang perlombaan</h2></div>
            <p>Peserta bersifat umum atau perwakilan lembaga pada seluruh kecamatan di Kota Malang yang memenuhi persyaratan sesuai ketentuan LPTQ.</p>
        </div>
        @php($branchIcons = [
            'tilawah' => 'M12 6c-2-1.6-5-2-8-1.5V19c3-.5 6 0 8 1.5 2-1.5 5-2 8-1.5V4.5C17 4 14 4.4 12 6zm0 0v14',
            'khot_indah' => 'M4 20l4-1 11-11-3-3L5 16zm10-14 3 3M4 20h16',
            'mhq' => 'M12 4a5 5 0 0 0-5 5c0 1.6.8 3 2 4v3h6v-3c1.2-1 2-2.4 2-4a5 5 0 0 0-5-5zm-2 15h4',
            'murottal' => 'M12 3a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3zM6 11a6 6 0 0 0 12 0M12 17v4M9 21h6',
        ])
        <div class="branch-grid">
            @foreach ($branchConfig as $key => $branch)
                @php($isOpen = isset($openCatalogue[$key]))
                <article class="branch-card @unless ($isOpen) branch-card-soon @endunless">
                    <span class="icon-circle" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $branchIcons[$key] ?? $branchIcons['tilawah'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="branch-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $branch['label'] }}</h3>
                    <ul class="branch-fees">
                        @foreach ($isOpen ? $openCatalogue[$key]['categories'] : $branch['categories'] as $category)
                            <li>
                                <span><strong>{{ $category['label'] }}</strong>@if ($category['description'])<small>{{ $category['description'] }}</small>@endif</span>
                                @if ($isOpen)<b>Rp {{ number_format($category['fee'], 0, ',', '.') }}</b>@endif
                            </li>
                        @endforeach
                    </ul>
                    @unless ($isOpen)
                        <p class="soon-badge">Pendaftaran cabang ini belum/tidak dibuka</p>
                    @endunless
                </article>
            @endforeach
        </div>
    </section>

    @include('partials.juknis')

    <section class="content-section fee-section" id="biaya">
        <div class="fee-card">
            <span class="section-kicker">Biaya pendaftaran</span>
            @php($fees = collect($openCatalogue)->flatMap(fn ($branch) => array_column($branch['categories'], 'fee')))
            @php($exampleFee = $fees->min() ?? 0)
            <strong class="fee-amount"><small>mulai</small> Rp {{ number_format($exampleFee, 0, ',', '.') }}</strong>
            <p>per peserta <strong>sesuai cabang &amp; kategori</strong> (lihat kartu cabang di atas), <strong>ditambah 3 digit kode unik</strong> yang diberikan otomatis setelah formulir terkirim.</p>
            <div class="fee-example">
                <span>Contoh nominal transfer</span>
                <strong>Rp {{ substr(number_format($exampleFee + 123, 0, ',', '.'), 0, -3) }}<em>123</em></strong>
                <small>Tiga digit terakhir menjadi penanda pembayaran atas nama Anda.</small>
            </div>
        </div>
        <div class="flow">
            <div class="section-heading"><div><span class="section-kicker">Alur pendaftaran</span><h2 class="display-title">Empat langkah mudah</h2></div></div>
            <ol class="flow-steps">
                <li><span>1</span><div><h3>Isi formulir</h3><p>Lengkapi data peserta dan pilih cabang lomba.</p></div></li>
                <li><span>2</span><div><h3>Transfer sesuai nominal unik</h3><p>Kirim ke rekening {{ config('mtq.bank.name') }} a.n. {{ config('mtq.bank.account_name') }} tepat sampai 3 digit terakhir.</p></div></li>
                <li><span>3</span><div><h3>Unggah bukti transfer</h3><p>Langsung setelah mendaftar, atau kapan saja lewat menu Cek pendaftaran.</p></div></li>
                <li><span>4</span><div><h3>Verifikasi panitia</h3><p>Pantau status pembayaran dan data peserta dengan kode pendaftaran.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="registration-section" id="daftar">
        <div class="form-intro">
            <span class="section-kicker">Langkah pertama</span>
            <h2 class="display-title">Formulir<br>pendaftaran</h2>
            <p>Isi data peserta dengan benar. Setelah formulir terkirim Anda akan mendapat <strong>kode pendaftaran</strong> dan <strong>nominal transfer unik</strong>.</p>
            <div class="privacy-note"><span>✓</span><p>Data digunakan panitia untuk keperluan registrasi dan verifikasi peserta.</p></div>
            <a class="juknis-reminder" href="#juknis"><span aria-hidden="true">📘</span><span>Pastikan sudah membaca <strong>juknis perlombaan</strong> sebelum mengisi formulir.</span></a>
        </div>

        @if ($period['status'] !== 'open')
            <div class="registration-form registration-closed">
                <span class="result-icon">{{ $period['status'] === 'upcoming' ? '⏳' : '✓' }}</span>
                <h3>{{ $period['status'] === 'upcoming' ? 'Pendaftaran belum dibuka' : 'Pendaftaran telah ditutup' }}</h3>
                <p>
                    @if ($period['status'] === 'upcoming')
                        Formulir dapat diisi mulai <strong>{{ $period['opens_at']->translatedFormat('d F Y') }}</strong> hingga {{ $period['closes_at']->translatedFormat('d F Y') }}.
                    @else
                        Pendaftaran ditutup pada <strong>{{ $period['closes_at']->translatedFormat('d F Y') }}</strong>. Peserta yang sudah mendaftar tetap dapat mengecek status dan mengunggah bukti pembayaran.
                    @endif
                </p>
                <a class="button button-outline" href="{{ route('status.form') }}">Cek status pendaftaran</a>
            </div>
        @else
        <form class="registration-form" action="{{ route('registration.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-heading"><span class="step-badge">01</span><div><h3>Data peserta</h3><p>Sesuai dokumen identitas</p></div></div>

            <div class="field-grid">
                <div class="field field-wide">
                    <label for="full_name">Nama lengkap <span>*</span></label>
                    <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" placeholder="Nama sesuai identitas" autocomplete="name" required>
                    @error('full_name') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field field-wide">
                    <label for="nik">NIK <span>*</span></label>
                    <input id="nik" name="nik" type="text" inputmode="numeric" pattern="\d{16}" maxlength="16" value="{{ old('nik') }}" placeholder="16 digit sesuai KK/KTP/KIA" autocomplete="off" required>
                    <small class="field-hint">Satu NIK hanya dapat mengikuti satu cabang lomba. NIK disimpan terenkripsi.</small>
                    @error('nik') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="birth_date">Tanggal lahir <span>*</span></label>
                    <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" max="{{ now()->toDateString() }}" required>
                    @error('birth_date') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="gender">Jenis kelamin <span>*</span></label>
                    <select id="gender" name="gender" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="male" @selected(old('gender') === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender') === 'female')>Perempuan</option>
                    </select>
                    @error('gender') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="district">Kecamatan domisili (Kota Malang) <span>*</span></label>
                    <select id="district" name="district" required>
                        <option value="">Pilih kecamatan</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district }}" @selected(old('district') === $district)>{{ $district }}</option>
                        @endforeach
                    </select>
                    @error('district') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="phone">Nomor WhatsApp <span>*</span></label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" autocomplete="tel" required>
                    @error('phone') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field field-wide">
                    <label for="address">Alamat domisili <span>*</span></label>
                    <textarea id="address" name="address" rows="2" placeholder="Jalan, RT/RW, kelurahan" required>{{ old('address') }}</textarea>
                    @error('address') <small class="field-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-divider"></div>
            <div class="form-heading"><span class="step-badge">02</span><div><h3>Asal sekolah / lembaga</h3><p>Sekolah, madrasah, TPQ, atau lembaga yang diwakili</p></div></div>
            <div class="field-grid">
                <div class="field field-wide">
                    <label for="institution">Asal sekolah / madrasah / lembaga <span>*</span></label>
                    <input id="institution" name="institution" type="text" value="{{ old('institution') }}" placeholder="Mis. MI Al-Ikhlas / TPQ Nurul Huda" required>
                    @error('institution') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field field-wide">
                    <label for="school_address">Alamat sekolah / lembaga <span>*</span></label>
                    <textarea id="school_address" name="school_address" rows="2" placeholder="Alamat lengkap sekolah/lembaga" required>{{ old('school_address') }}</textarea>
                    @error('school_address') <small class="field-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-divider"></div>
            <div class="form-heading"><span class="step-badge">03</span><div><h3>Pilihan lomba</h3><p>Setiap peserta hanya dapat mengikuti 1 cabang</p></div></div>
            <div class="field-grid">
                <div class="field field-wide">
                    <label for="branch">Cabang perlombaan <span>*</span></label>
                    <select id="branch" name="branch" required>
                        <option value="">Pilih cabang lomba</option>
                        @foreach ($branchConfig as $key => $branch)
                            @if (isset($openCatalogue[$key]))
                                <option value="{{ $key }}" @selected(old('branch') === $key)>{{ $branch['label'] }}</option>
                            @else
                                <option value="{{ $key }}" disabled>{{ $branch['label'] }} (ditutup)</option>
                            @endif
                        @endforeach
                    </select>
                    @error('branch') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field field-wide">
                    <label for="category">Kategori <span>*</span></label>
                    <select id="category" name="category" required data-selected="{{ old('category') }}">
                        <option value="">Pilih cabang lomba terlebih dahulu</option>
                    </select>
                    <small class="field-hint">Pilih kategori sesuai jenjang sekolah/usia peserta. Panitia memverifikasi kesesuaiannya.</small>
                    @error('category') <small class="field-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-divider"></div>
            <div class="form-heading"><span class="step-badge">04</span><div><h3>Dokumen persyaratan</h3><p>Disimpan terenkripsi, hanya dapat dibuka panitia</p></div></div>
            <div class="upload-grid">
                <div class="field">
                    <label class="upload-drop" for="identity_document">
                        <span class="upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 5h18v14H3zm2 2v10h14V7zm2 2h4v4H7zm6 0h4v1.5h-4zm0 3h4v1.5h-4zm-6 3h10v1.5H7z" fill="currentColor"/></svg></span>
                        <span><strong>Identitas diri <span class="required">*</span></strong><small data-file-label>KTP / Kartu Keluarga (bukti domisili Kota Malang), atau Kartu Pelajar / KIA · JPG, PNG, PDF · maks. 5 MB</small></span>
                        <input id="identity_document" name="identity_document" type="file" data-max-mb="5" data-label="Identitas diri" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    </label>
                </div>
                <div class="field">
                    <label class="upload-drop" for="photo">
                        <span class="upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z" fill="currentColor"/></svg></span>
                        <span><strong>Pasfoto terbaru <span class="required">*</span></strong><small data-file-label>JPG, PNG, WEBP · maks. 2 MB</small></span>
                        <input id="photo" name="photo" type="file" data-max-mb="2" data-label="Pasfoto" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                </div>
            </div>
            @if ($errors->any())
                <p class="field-hint">Jika ada isian yang salah, pilih ulang kedua file dokumen sebelum mengirim kembali.</p>
            @endif
            <p class="field-hint"><strong>Bukti pembayaran</strong> diunggah setelah formulir terkirim, karena nominal transfer (dengan kode unik) baru diberikan setelah mendaftar.</p>

            <div class="form-divider"></div>
            <div class="fee-summary">
                <span>Biaya pendaftaran</span>
                <strong><span id="fee-amount">Pilih cabang &amp; kategori</span> <small>+ kode unik 3 digit</small></strong>
            </div>

            <button class="button button-primary submit-button" type="submit">Kirim pendaftaran <span aria-hidden="true">→</span></button>
            <p class="form-footnote">Dengan mengirim formulir ini, Anda menyetujui data di atas digunakan untuk proses pendaftaran MTQ.</p>
        </form>
        @endif
    </section>

    <section class="bottom-cta">
        <div><span class="section-kicker">Sudah mendaftar?</span><h2>Pantau status &amp; unggah bukti pembayaran.</h2></div>
        <a class="button button-light" href="{{ route('status.form') }}">Cek status <span aria-hidden="true">→</span></a>
    </section>

    @if ($period['status'] === 'open')
        <script>
            const branchConfig = {{ Js::from($openCatalogue) }};
            const branchSelect = document.getElementById('branch');
            const categorySelect = document.getElementById('category');
            const feeAmount = document.getElementById('fee-amount');
            const rupiah = (value) => 'Rp ' + value.toLocaleString('id-ID');

            function renderCategories() {
                const categories = branchConfig[branchSelect.value]?.categories ?? {};
                const keys = Object.keys(categories);
                const selected = categorySelect.value || categorySelect.dataset.selected;

                categorySelect.innerHTML = '';
                categorySelect.add(new Option(keys.length ? 'Pilih kategori' : 'Pilih cabang lomba terlebih dahulu', ''));
                keys.forEach((key) => {
                    const category = categories[key];
                    categorySelect.add(new Option(`${category.label} – ${category.description} (${rupiah(category.fee)})`, key));
                });

                categorySelect.value = keys.length === 1 ? keys[0] : (keys.includes(selected) ? selected : '');
                categorySelect.dataset.selected = '';
                renderFee();
            }

            function renderFee() {
                const category = branchConfig[branchSelect.value]?.categories?.[categorySelect.value];
                feeAmount.textContent = category ? rupiah(category.fee) : 'Pilih cabang & kategori';
            }

            branchSelect.addEventListener('change', renderCategories);
            categorySelect.addEventListener('change', renderFee);
            renderCategories();
        </script>
    @endif
@endsection
