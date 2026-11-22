@php
    $juknisDocument = app(\App\Services\JuknisDocument::class);
    $juknisPdf = $juknisDocument->url();
    $juknisDownload = route('juknis.show', ['v' => $juknisDocument->updatedAt()?->timestamp, 'unduh' => 1]);

    $generalRules = [
        'Peserta diizinkan mengikuti perlombaan setelah mengisi registrasi ulang yang disediakan panitia.',
        'Hadir 15 menit sebelum lomba dimulai.',
        'Mengambil nomor urut pada saat Technical Meeting dan memakainya di dada sebelah kiri.',
        'Dipanggil tiga kali berturut-turut tidak hadir: dipindah ke urutan terakhir, lalu didiskualifikasi jika tetap tidak hadir (kecuali sudah berkoordinasi dengan panitia).',
        'Tampil tanpa salam dan tanpa memperkenalkan diri.',
        'Berbusana muslim/muslimah, rapi, dan sopan.',
        'Keputusan dewan juri bersifat mutlak dan tidak dapat diganggu gugat.',
    ];

    $lights = [
        ['kuning', 'Lampu kuning pertama', 'Persiapan memulai'],
        ['hijau', 'Lampu hijau', 'Dimulai'],
        ['kuning', 'Lampu kuning kedua', '1 menit persiapan mengakhiri'],
        ['merah', 'Lampu merah', 'Mengakhiri penampilan'],
    ];

    $domicile = 'Berasal dari lembaga formal/nonformal di wilayah Kota Malang atau berdomisili di Kota Malang, dibuktikan dengan fotokopi KTP/Kartu Keluarga.';

    $branches = [
        'mtq' => [
            'tab' => 'MTQ',
            'title' => 'Musabaqah Tilawatil Qur’an (MTQ)',
            'categories' => [
                ['Tilawah Anak', 'SD/MI – kelas 8 SMP/MTs', '5–6 menit', 'Maqra’ dipublikasikan Senin, 14 Des 2026'],
                ['Tilawah Remaja', 'Kelas 9 SMP/MTs – 12 SMA/MA', '7–8 menit', 'Maqra’ diberikan saat TM, Senin, 21 Des 2026'],
                ['Tilawah Dewasa', 'Mahasiswa – usia maks. 41 tahun', '8–10 menit', 'Maqra’ diberikan saat TM, Senin, 21 Des 2026'],
            ],
            'participants' => [
                $domicile.' Santri pondok pesantren dapat menyertakan surat pernyataan dari pondok.',
                'Usia peserta menyesuaikan kategori lomba yang dipilih.',
                'Melengkapi persyaratan administrasi melalui web registrasi ini.',
                'Setiap peserta hanya boleh mengikuti 1 cabang lomba.',
            ],
            'rules' => [
                'Membaca ayat sesuai maqra’ yang diserahkan saat Technical Meeting. Peserta yang tidak hadir TM, maqra’-nya ditentukan panitia.',
                'Boleh membawa Al-Qur’an sendiri atau memakai Al-Qur’an dari panitia.',
                'Jika nilai sama, juara ditentukan dari nilai Tajwid tertinggi.',
            ],
            'lights' => true,
            'scores' => [
                ['Tajwid', 30, 'Makharijul huruf, sifatul huruf, ahkamul huruf, ahkamul mad wal qashr'],
                ['Fashahah', 30, 'Al-waqf wal ibtida’, mura’atul huruf wal harakat, mura’atul kalimat wal ayat'],
                ['Suara & lagu', 40, 'Vokal, kejernihan, kehalusan, kenyaringan, napas, lagu pembuka & penutup, jumlah lagu, peralihan & tempo, irama & gaya, variasi'],
            ],
        ],
        'mhq' => [
            'tab' => 'MHQ',
            'title' => 'Musabaqah Hifdzil Qur’an (MHQ)',
            'categories' => [
                ['Juz 30', 'Siswa SD/MI Kota Malang', null, 'Soal dari Juz 30, maqra’ wajib dan pilihan'],
            ],
            'participants' => [
                $domicile,
                'Peserta merupakan siswa tingkat SD/MI wilayah Kota Malang.',
                'Melengkapi persyaratan administrasi melalui web registrasi ini.',
                'Setiap peserta hanya boleh mengikuti 1 cabang lomba.',
            ],
            'rules' => [
                'Peserta mengambil amplop soal tertutup berisi 4 soal: menyampaikan hafalan surat, sambung ayat dalam satu surat, sambung ayat lanjut surat, dan menyebutkan nama surat.',
                'Juri membacakan soal maksimal 2 kali.',
                'Isyarat ketukan: 2× mulai · 1× bacaan salah · 3× pindah soal · 4× selesai.',
                'Diawali ta’awudz dan basmalah, ditutup dengan tashdiq (Shadaqallahul ‘Azhim).',
            ],
            'lights' => false,
            'scores' => [
                ['Tahfidz', 50, null],
                ['Tajwid', 25, null],
                ['Makharijul huruf', 25, null],
            ],
        ],
        'mkq' => [
            'tab' => 'MKQ',
            'title' => 'Musabaqah Khattil Qur’an (MKQ)',
            'categories' => [
                ['Khat Naskhi', 'SD/MI', null, 'Ayat/surah pendek Juz ‘Amma, ditentukan panitia saat lomba'],
            ],
            'participants' => [
                $domicile,
                'Melengkapi persyaratan administrasi melalui web registrasi ini.',
                'Setiap peserta hanya boleh mengikuti 1 cabang lomba.',
            ],
            'rules' => [
                'Lomba perorangan (putra/putri). Karya dibuat langsung di tempat lomba, tidak boleh membawa karya jadi atau setengah jadi.',
                'Kertas gambar/karton putih A3 (29,7 × 42 cm) berstempel resmi disediakan panitia.',
                'Alat tulis disiapkan sendiri. Tulisan ayat wajib bertinta hitam dengan pena/spidol kaligrafi/handam mata pena 1,5–2 mm.',
                'Bingkai/ornamen hiasan boleh memakai alat warna kering maupun basah (spidol, pensil warna, krayon, cat air).',
                'Setiap peserta menempati meja sesuai nomor urut. Istirahat dilakukan serentak sesuai arahan panitia.',
            ],
            'lights' => false,
            'scores' => [
                ['Kaidah khat', 50, null],
                ['Kerapian', 20, null],
                ['Paduan warna', 20, null],
                ['Kreativitas', 10, null],
            ],
        ],
        'murottal' => [
            'tab' => 'Murottal',
            'title' => 'Musabaqah Murottal Qur’an (MMQ)',
            'categories' => [
                ['Tartil Anak', 'SD/MI – kelas 8 SMP/MTs', null, 'Maqra’ diambil saat Technical Meeting'],
            ],
            'participants' => [
                $domicile,
                'Peserta merupakan siswa tingkat SD s.d. kelas 8 SMP wilayah Kota Malang.',
                'Melengkapi persyaratan administrasi melalui web registrasi ini.',
                'Setiap peserta hanya boleh mengikuti 1 cabang lomba.',
            ],
            'rules' => [
                'Membaca ayat sesuai maqra’ yang diambil saat Technical Meeting. Peserta yang tidak hadir TM, maqra’-nya ditentukan panitia.',
                'Boleh membawa Al-Qur’an sendiri atau memakai Al-Qur’an dari panitia.',
                'Jika nilai sama, juara ditentukan dari nilai Tajwid tertinggi.',
            ],
            'lights' => true,
            'scores' => [
                ['Tajwid', 40, 'Makharijul huruf, sifatul huruf, ahkamul huruf, ahkamul mad wal qashr'],
                ['Fashahah', 30, 'Al-waqf wal ibtida’, mura’atul huruf wal harakat, mura’atul kalimat wal ayat'],
                ['Irama', 30, 'Suara, irama & variasi lagu, tempo bacaan & pengaturan napas'],
            ],
        ],
    ];
@endphp

<section class="content-section juknis-section" id="juknis">
    <div class="juknis-head">
        <div class="juknis-intro">
            <span class="section-kicker">Petunjuk teknis</span>
            <h2 class="display-title">Juknis<br>perlombaan</h2>
            <p>Baca ketentuan peserta, aturan lomba, dan kriteria penilaian tiap cabang sebelum mendaftar. Dokumen lengkap tersedia dalam format PDF.</p>
        </div>

        <div class="juknis-doc">
            <span class="juknis-doc-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5zM8 12h8v1.6H8zm0 3.2h8v1.6H8zm0 3.2h5v1.6H8z" fill="currentColor"/></svg>
            </span>
            <div class="juknis-doc-text">
                <strong>Juknis Perlombaan MTQ Kota Malang 2026</strong>
                <small>PDF · {{ $juknisDocument->sizeLabel() }} · diperbarui {{ $juknisDocument->updatedAt()?->translatedFormat('j F Y') }}</small>
            </div>
            <div class="juknis-doc-actions">
                <a class="button button-primary button-compact" href="{{ $juknisPdf }}" target="_blank" rel="noopener">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" fill="currentColor"/></svg>
                    Baca juknis
                </a>
                <a class="button button-outline button-compact" href="{{ $juknisDownload }}">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h2v9.2l3.3-3.3 1.4 1.4L12 16 6.3 10.3l1.4-1.4 3.3 3.3zM5 18h14v2H5z" fill="currentColor"/></svg>
                    Unduh PDF
                </a>
            </div>
        </div>
    </div>

    <ol class="juknis-timeline">
        <li><small>Pendaftaran online</small><strong>Mulai 9 November 2026</strong><span>s.d. kuota terpenuhi</span></li>
        <li><small>Sekretariat (bantuan daftar)</small><strong>Pukul 08.00–12.00</strong><span>konfirmasi dahulu ke CP</span></li>
        <li><small>Technical Meeting & pengesahan</small><strong>Senin, 21 Desember 2026</strong><span>ambil nomor urut & maqra’</span></li>
        <li><small>Pelaksanaan lomba</small><strong>Sabtu–Minggu, 26–27 Des 2026</strong><span>Malang Creative Center Lt. 2</span></li>
    </ol>

    <div class="juknis-tabs" role="tablist" aria-label="Juknis per cabang">
        <button class="juknis-tab" type="button" role="tab" id="tab-umum" aria-controls="juknis-umum" aria-selected="true" data-juknis-tab>Ketentuan umum</button>
        @foreach ($branches as $key => $branch)
            <button class="juknis-tab" type="button" role="tab" id="tab-{{ $key }}" aria-controls="juknis-{{ $key }}" aria-selected="false" data-juknis-tab>{{ $branch['tab'] }}</button>
        @endforeach
    </div>

    <div class="juknis-panel" role="tabpanel" id="juknis-umum" aria-labelledby="tab-umum">
        <div class="juknis-grid">
            <article class="juknis-card">
                <h3>Berlaku untuk semua cabang</h3>
                <ul class="juknis-list">
                    @foreach ($generalRules as $rule)
                        <li>{{ $rule }}</li>
                    @endforeach
                </ul>
            </article>
            <article class="juknis-card">
                <h3>Pengesahan & penetapan juara</h3>
                <ul class="juknis-list">
                    <li>Pengesahan peserta ditetapkan panitia saat Technical Meeting dan dapat dibatalkan jika ketentuan tidak terpenuhi.</li>
                    <li>Peserta yang berhalangan hadir saat TM diwakilkan panitia; info disampaikan melalui grup lomba.</li>
                    <li>Peserta wajib registrasi ulang sesuai jadwal; terlambat atau tidak sesuai ketentuan dapat didiskualifikasi.</li>
                    <li>Diambil juara 1, 2, 3, dan harapan 1 (putra & putri) pada masing-masing cabang. Hasil diumumkan saat penutupan.</li>
                    <li>Sanksi: pelanggaran ringan berupa teguran; pelanggaran berat berupa pengurangan nilai atau diskualifikasi.</li>
                </ul>
            </article>
        </div>
    </div>

    @foreach ($branches as $key => $branch)
        <div class="juknis-panel" role="tabpanel" id="juknis-{{ $key }}" aria-labelledby="tab-{{ $key }}">
            <h3 class="juknis-panel-title">{{ $branch['title'] }}</h3>

            <div class="juknis-categories">
                @foreach ($branch['categories'] as [$name, $level, $duration, $note])
                    <div class="juknis-category">
                        <strong>{{ $name }}</strong>
                        <span>{{ $level }}</span>
                        @if ($duration)<b>⏱ {{ $duration }}</b>@endif
                        <small>{{ $note }}</small>
                    </div>
                @endforeach
            </div>

            <div class="juknis-grid juknis-grid-3">
                <article class="juknis-card">
                    <h3>Ketentuan peserta</h3>
                    <ul class="juknis-list">
                        @foreach ($branch['participants'] as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="juknis-card">
                    <h3>Ketentuan lomba</h3>
                    <ul class="juknis-list">
                        @foreach ($branch['rules'] as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                        <li>Mengikuti <button class="text-link juknis-inline-link" type="button" data-juknis-open="umum">ketentuan umum</button> yang berlaku untuk semua cabang.</li>
                    </ul>
                    @if ($branch['lights'])
                        <ul class="juknis-lights" aria-label="Isyarat lampu">
                            @foreach ($lights as [$color, $label, $meaning])
                                <li><span class="light light-{{ $color }}" aria-hidden="true"></span><strong>{{ $label }}</strong><small>{{ $meaning }}</small></li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article class="juknis-card">
                    <h3>Kriteria penilaian</h3>
                    <ul class="juknis-scores">
                        @foreach ($branch['scores'] as [$aspect, $weight, $detail])
                            <li>
                                <div><strong>{{ $aspect }}</strong><b>{{ $weight }}</b></div>
                                <span class="score-bar" aria-hidden="true"><span style="width: {{ $weight }}%"></span></span>
                                @if ($detail)<small>{{ $detail }}</small>@endif
                            </li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </div>
    @endforeach

    <div class="juknis-footer">
        <p>Sudah membaca juknis dan memenuhi ketentuan?</p>
        <div class="juknis-footer-actions">
            @if ($period['status'] === 'open')
                <a class="button button-primary" href="#daftar">Daftar sekarang <span aria-hidden="true">→</span></a>
            @endif
            <a class="button button-outline" href="{{ $juknisDownload }}">Unduh juknis (PDF)</a>
        </div>
    </div>
</section>
