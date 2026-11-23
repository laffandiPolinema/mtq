@extends('layouts.app')

@section('title', 'Jadwal acara · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section admin-form-section juknis-admin">
        @include('admin.partials.nav')

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <form class="panel admin-form" action="{{ route('admin.schedule.update') }}" method="POST">
            @csrf
            @method('PUT')
            <span class="section-kicker">Jadwal acara</span>
            <h1 class="display-title">Technical Meeting &amp; pelaksanaan lomba</h1>
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
