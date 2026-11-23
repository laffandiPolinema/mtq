@extends('layouts.app')

@section('title', 'Juknis · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section admin-form-section juknis-admin">
        @include('admin.partials.nav')

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <div class="panel admin-form">
            <span class="section-kicker">Petunjuk teknis</span>
            <h1 class="display-title">File juknis</h1>
            <p class="field-hint">File ini dipakai oleh tombol <strong>Baca juknis</strong> dan <strong>Unduh PDF</strong> di halaman utama.</p>

            <div class="juknis-current">
                <span class="juknis-doc-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5zM8 12h8v1.6H8zm0 3.2h8v1.6H8zm0 3.2h5v1.6H8z" fill="currentColor"/></svg>
                </span>
                <div>
                    @if ($juknis->exists())
                        <strong>{{ $juknis->isUploaded() ? 'Juknis unggahan panitia' : 'Juknis bawaan aplikasi' }}</strong>
                        <small>PDF · {{ $juknis->sizeLabel() }} · diperbarui {{ $juknis->updatedAt()?->translatedFormat('j F Y, H.i') }} WIB</small>
                    @else
                        <strong>Belum ada file juknis</strong>
                        <small>Tombol juknis di halaman utama belum dapat dibuka.</small>
                    @endif
                </div>
                @if ($juknis->exists())
                    <a class="button button-outline button-compact" href="{{ $juknis->url() }}" target="_blank" rel="noopener">Lihat PDF</a>
                @endif
            </div>

            <form class="juknis-upload-form" action="{{ route('admin.juknis.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label class="upload-drop" for="juknis">
                        <span class="upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3l5 5h-3v6h-4V8H7l5-5zM5 17h14v3H5z" fill="currentColor"/></svg></span>
                        <span><strong>Unggah juknis baru</strong><small data-file-label>File PDF · maks. 8 MB</small></span>
                        <input id="juknis" name="juknis" type="file" data-max-mb="8" data-label="Juknis" accept="application/pdf" required>
                    </label>
                </div>
                <button class="button button-primary" type="submit">{{ $juknis->isUploaded() ? 'Ganti juknis' : 'Unggah juknis' }}</button>
            </form>

            <div class="notice notice-info">
                File lama langsung diganti dan peserta otomatis mendapat versi terbaru. Ketentuan per cabang di halaman utama (tab MTQ, MHQ, dst.) tidak ikut berubah. Jadwal Technical Meeting dan pelaksanaan lomba diatur di formulir di bawah.
            </div>

            @if ($juknis->isUploaded())
                <form action="{{ route('admin.juknis.destroy') }}" method="POST" onsubmit="return confirm('Hapus file unggahan dan kembali memakai juknis bawaan?')">
                    @csrf
                    @method('DELETE')
                    <button class="button button-outline button-compact" type="submit">Hapus unggahan, pakai juknis bawaan</button>
                </form>
            @endif
        </div>

        <form class="panel admin-form" action="{{ route('admin.juknis.schedule') }}" method="POST">
            @csrf
            @method('PUT')
            <span class="section-kicker">Jadwal acara</span>
            <h2 class="display-title">Technical Meeting &amp; pelaksanaan lomba</h2>
            <p class="field-hint">Dipakai di semua tempat sekaligus: kartu di bagian atas halaman utama, kartu jadwal di bagian Juknis, footer, dan bukti pendaftaran. Tanggal hari pertama lomba juga menjadi acuan perhitungan batas usia kategori.</p>

            <fieldset class="schedule-fields">
                <legend>Technical Meeting</legend>
                <div class="field-grid">
                    <div class="field">
                        <label for="technical_meeting_date">Tanggal <span>*</span></label>
                        <input id="technical_meeting_date" name="technical_meeting_date" type="date" value="{{ old('technical_meeting_date', $schedule->technicalMeetingDate()->toDateString()) }}" required>
                    </div>
                    <div class="field">
                        <label for="technical_meeting_note">Keterangan</label>
                        <input id="technical_meeting_note" name="technical_meeting_note" value="{{ old('technical_meeting_note', $schedule->technicalMeetingNote()) }}" maxlength="100" placeholder="Mis. ambil nomor urut & maqra’">
                    </div>
                </div>
            </fieldset>

            <fieldset class="schedule-fields">
                <legend>Pelaksanaan lomba</legend>
                <div class="field-grid">
                    <div class="field">
                        <label for="event_start_date">Tanggal mulai <span>*</span></label>
                        <input id="event_start_date" name="event_start_date" type="date" value="{{ old('event_start_date', $schedule->eventStart()->toDateString()) }}" required>
                    </div>
                    <div class="field">
                        <label for="event_end_date">Tanggal selesai</label>
                        <input id="event_end_date" name="event_end_date" type="date" value="{{ old('event_end_date', $schedule->eventEnd()->toDateString()) }}">
                        <small class="field-hint">Kosongkan jika lomba hanya satu hari.</small>
                    </div>
                </div>
                <div class="field-grid">
                    <div class="field">
                        <label for="event_time">Waktu <span>*</span></label>
                        <input id="event_time" name="event_time" value="{{ old('event_time', $schedule->eventTime()) }}" maxlength="60" placeholder="Mis. 07.30 WIB s.d. selesai" required>
                    </div>
                    <div class="field">
                        <label for="event_place">Tempat <span>*</span></label>
                        <input id="event_place" name="event_place" value="{{ old('event_place', $schedule->eventPlace()) }}" maxlength="120" placeholder="Mis. Malang Creative Center (MCC) Lt. 2" required>
                    </div>
                </div>
            </fieldset>

            <p class="field-hint">Tampil sebagai: <strong>Technical meeting{{ $schedule->technicalMeetingOffset() ? ' ('.$schedule->technicalMeetingOffset().')' : '' }}: {{ $schedule->technicalMeetingLabel() }}</strong> · <strong>Lomba: {{ $schedule->eventDaysLabel() }}, {{ $schedule->eventTime() }}, {{ $schedule->eventPlace() }}</strong></p>
            <div>
                <button class="button button-primary" type="submit">Simpan jadwal</button>
            </div>
        </form>
    </section>
@endsection
