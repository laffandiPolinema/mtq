<?php

namespace App\Http\Controllers;

use App\Services\EventSchedule;
use App\Services\JuknisDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminJuknisController extends Controller
{
    public function edit(JuknisDocument $juknis, EventSchedule $schedule): View
    {
        return view('admin.juknis.edit', ['juknis' => $juknis, 'schedule' => $schedule]);
    }

    /**
     * Tanggal Technical Meeting serta tanggal, waktu, dan tempat pelaksanaan lomba.
     */
    public function updateSchedule(Request $request, EventSchedule $schedule): RedirectResponse
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
            ->route('admin.juknis.edit')
            ->with('success', 'Jadwal Technical Meeting dan pelaksanaan lomba berhasil diperbarui di seluruh halaman.');
    }

    public function update(Request $request, JuknisDocument $juknis): RedirectResponse
    {
        $request->validate(['juknis' => JuknisDocument::rules()], JuknisDocument::messages());

        $juknis->replace($request->file('juknis'));

        return redirect()
            ->route('admin.juknis.edit')
            ->with('success', 'Juknis berhasil diganti. Halaman utama sekarang memakai file yang baru.');
    }

    public function destroy(JuknisDocument $juknis): RedirectResponse
    {
        $juknis->deleteUpload();

        return redirect()
            ->route('admin.juknis.edit')
            ->with('success', 'File unggahan dihapus. Halaman utama kembali memakai juknis bawaan.');
    }
}
