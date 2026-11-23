<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Jadwal Technical Meeting dan pelaksanaan lomba.
 *
 * Panitia mengubahnya di panel admin (tab Jadwal acara). Selama belum diubah, dipakai nilai
 * bawaan dari config/mtq.php. Semua tampilan (halaman utama, juknis, footer, bukti
 * pendaftaran) dan perhitungan batas usia membaca dari sini agar selalu seragam.
 */
class EventSchedule
{
    /**
     * @var array<string, string> kunci form => kunci config
     */
    public const FIELDS = [
        'technical_meeting_date' => 'technical_meeting_date',
        'technical_meeting_note' => 'technical_meeting_note',
        'event_start_date' => 'event_date',
        'event_end_date' => 'event_end_date',
        'event_time' => 'event_time',
        'event_place' => 'event_place',
    ];

    public function value(string $field): string
    {
        return Setting::get('schedule.'.$field) ?? (string) config('mtq.'.self::FIELDS[$field]);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function update(array $values): void
    {
        foreach (array_keys(self::FIELDS) as $field) {
            Setting::put('schedule.'.$field, $values[$field] ?? '');
        }
    }

    public function technicalMeetingDate(): Carbon
    {
        return Carbon::parse($this->value('technical_meeting_date'));
    }

    public function technicalMeetingNote(): string
    {
        return $this->value('technical_meeting_note');
    }

    /**
     * Hari pertama lomba; acuan perhitungan batas usia kategori.
     */
    public function eventStart(): Carbon
    {
        return Carbon::parse($this->value('event_start_date'));
    }

    public function eventEnd(): Carbon
    {
        $end = $this->value('event_end_date');

        return $end === '' ? $this->eventStart() : Carbon::parse($end);
    }

    public function eventTime(): string
    {
        return $this->value('event_time');
    }

    public function eventPlace(): string
    {
        return $this->value('event_place');
    }

    /**
     * Mis. "Senin, 21 Desember 2026".
     */
    public function technicalMeetingLabel(): string
    {
        return $this->technicalMeetingDate()->translatedFormat('l, j F Y');
    }

    /**
     * Jumlah hari Technical Meeting sebelum lomba, mis. "H-5".
     */
    public function technicalMeetingOffset(): string
    {
        $days = (int) $this->technicalMeetingDate()->startOfDay()->diffInDays($this->eventStart()->startOfDay(), false);

        return $days > 0 ? 'H-'.$days : '';
    }

    /**
     * Mis. "Sabtu–Minggu, 26–27 Desember 2026" (atau "26–27 Des 2026" jika $short).
     */
    public function eventDaysLabel(bool $short = false, bool $withDayNames = true): string
    {
        $start = $this->eventStart();
        $end = $this->eventEnd();
        $month = $short ? 'M' : 'F';

        $days = $start->isSameDay($end)
            ? $start->translatedFormat('l')
            : $start->translatedFormat('l').'–'.$end->translatedFormat('l');

        $dates = match (true) {
            $start->isSameDay($end) => $start->translatedFormat("j {$month} Y"),
            $start->isSameMonth($end) => $start->format('j').'–'.$end->translatedFormat("j {$month} Y"),
            $start->isSameYear($end) => $start->translatedFormat("j {$month}").' – '.$end->translatedFormat("j {$month} Y"),
            default => $start->translatedFormat("j {$month} Y").' – '.$end->translatedFormat("j {$month} Y"),
        };

        return $withDayNames ? "{$days}, {$dates}" : $dates;
    }
}
