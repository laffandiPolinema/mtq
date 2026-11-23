<?php

namespace App\Http\Controllers;

use App\Services\EventSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminScheduleController extends Controller
{
    public function edit(EventSchedule $schedule): View
    {
        return view('admin.schedule.edit', ['schedule' => $schedule]);
    }

    /**
     * Tanggal Technical Meeting serta tanggal, waktu, dan tempat pelaksanaan lomba.
     */
    public function update(Request $request, EventSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'technical_meeting_date' => ['required', 'date_format:Y-m-d'],
            'technical_meeting_note' => ['nullable', 'string', 'max:100'],
            'event_start_date' => ['required', 'date_format:Y-m-d'],
            'event_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:event_start_date'],
            'event_time' => ['required', 'string', 'max:60'],
            'event_place' => ['required', 'string', 'max:120'],
        ], [
            'technical_meeting_date.required' => 'Tanggal Technical Meeting wajib diisi.',
            'event_start_date.required' => 'Tanggal mulai lomba wajib diisi.',
            'event_end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai lomba.',
            'event_time.required' => 'Waktu pelaksanaan wajib diisi.',
            'event_place.required' => 'Tempat pelaksanaan wajib diisi.',
            'date_format' => 'Format tanggal tidak valid.',
            'max' => 'Isian terlalu panjang.',
        ]);

        $schedule->update($validated);

        return redirect()
            ->route('admin.schedule.edit')
            ->with('success', 'Jadwal Technical Meeting dan pelaksanaan lomba berhasil diperbarui di seluruh halaman.');
    }
}
