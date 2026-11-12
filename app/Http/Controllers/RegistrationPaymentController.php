<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\ParticipantDocumentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistrationPaymentController extends Controller
{
    public function store(Request $request, string $code, ParticipantDocumentStore $documents): RedirectResponse
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        if (! $participant->canUploadPaymentProof()) {
            return back()->withErrors([
                'payment_proof' => 'Bukti pembayaran sudah diterima dan sedang/telah diperiksa panitia.',
            ]);
        }

        $request->validate(
            ['payment_proof' => ParticipantDocumentStore::rules('payment_proof')],
            ParticipantDocumentStore::messages(),
        );

        $hash = hash_file('sha256', $request->file('payment_proof')->getRealPath());

        // Satu bukti transfer hanya sah untuk satu pendaftaran (mencegah bukti orang lain dipakai ulang).
        if (Participant::where('payment_proof_hash', $hash)->whereKeyNot($participant->getKey())->exists()) {
            return back()->withErrors([
                'payment_proof' => 'Bukti transfer ini sudah pernah diunggah untuk pendaftaran lain. Unggah bukti transfer milik Anda sendiri.',
            ]);
        }

        $stored = $documents->store($request->file('payment_proof'), 'payment-proofs', 'payment_proof');
        $previousProofPath = $participant->payment_proof_path;

        $participant->update([
            'payment_proof_path' => $stored['path'],
            'payment_proof_hash' => $stored['hash'],
            'payment_status' => 'waiting',
            'payment_uploaded_at' => now(),
            'payment_note' => null,
        ]);

        $documents->delete($previousProofPath);

        return redirect()
            ->route('status.show', $participant->registration_code)
            ->with('success', 'Bukti pembayaran berhasil diunggah. Panitia akan segera memeriksanya.');
    }
}
