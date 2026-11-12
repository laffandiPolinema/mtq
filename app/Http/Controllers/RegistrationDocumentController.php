<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\ParticipantDocumentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistrationDocumentController extends Controller
{
    /**
     * Peserta mengganti identitas dan/atau pasfoto setelah panitia menandai data "Perlu perbaikan".
     */
    public function update(Request $request, string $code, ParticipantDocumentStore $documents): RedirectResponse
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        if (! $participant->canReplaceDocuments()) {
            return back()->withErrors([
                'documents' => 'Dokumen hanya dapat diganti jika panitia meminta perbaikan data.',
            ]);
        }

        $request->validate([
            'identity_document' => ['nullable', ...array_slice(ParticipantDocumentStore::rules('identity_document'), 1)],
            'photo' => ['nullable', ...array_slice(ParticipantDocumentStore::rules('photo'), 1)],
        ], ParticipantDocumentStore::messages());

        if (! $request->hasFile('identity_document') && ! $request->hasFile('photo')) {
            return back()->withErrors(['documents' => 'Pilih minimal satu dokumen yang akan diganti.']);
        }

        $replaced = [];

        foreach (['identity_document' => ['identity_document_path', 'identity-documents'], 'photo' => ['photo_path', 'photos']] as $field => [$column, $directory]) {
            if ($request->hasFile($field)) {
                $stored = $documents->store($request->file($field), $directory, $field, passPhoto: $field === 'photo');
                $documents->delete($participant->{$column});
                $participant->{$column} = $stored['path'];
                $replaced[] = $field;
            }
        }

        // Kembali ke antrean verifikasi panitia.
        $participant->status = 'pending';
        $participant->save();

        return redirect()
            ->route('status.show', $participant->registration_code)
            ->with('success', 'Dokumen berhasil diperbarui dan akan diperiksa kembali oleh panitia.');
    }
}
