<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RegistrationPaymentController extends Controller
{
    public function store(Request $request, string $code): RedirectResponse
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        if (! $participant->canUploadPaymentProof()) {
            return back()->withErrors([
                'payment_proof' => 'Bukti pembayaran sudah diterima dan sedang/telah diperiksa panitia.',
            ]);
        }

        $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'payment_proof.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'payment_proof.mimes' => 'Bukti transfer harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'payment_proof.max' => 'Ukuran file maksimal 5 MB.',
            'payment_proof.uploaded' => 'File gagal diunggah. Pastikan ukurannya tidak lebih dari 5 MB.',
        ]);

        $previousProofPath = $participant->payment_proof_path;

        $participant->update([
            'payment_proof_path' => $request->file('payment_proof')->store('payment-proofs', 'local'),
            'payment_status' => 'waiting',
            'payment_uploaded_at' => now(),
            'payment_note' => null,
        ]);

        if ($previousProofPath !== null) {
            Storage::disk('local')->delete($previousProofPath);
        }

        return redirect()
            ->route('status.show', $participant->registration_code)
            ->with('success', 'Bukti pembayaran berhasil diunggah. Panitia akan segera memeriksanya.');
    }
}
