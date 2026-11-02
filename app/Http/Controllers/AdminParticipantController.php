<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $participants = $this->filteredParticipants($request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $verifiedPayments = Participant::where('payment_status', 'verified');

        return view('admin.participants.index', [
            'participants' => $participants,
            'branches' => Participant::BRANCHES,
            'statuses' => Participant::STATUSES,
            'paymentStatuses' => Participant::PAYMENT_STATUSES,
            'counts' => [
                'total' => Participant::count(),
                'payment_waiting' => Participant::where('payment_status', 'waiting')->count(),
                'payment_verified' => (clone $verifiedPayments)->count(),
                'approved' => Participant::where('status', 'approved')->count(),
                'verified_amount' => (int) (clone $verifiedPayments)->sum('registration_fee') + (int) (clone $verifiedPayments)->sum('payment_unique_code'),
            ],
        ]);
    }

    /**
     * Unduh laporan peserta (CSV, terbuka di Excel) sesuai filter yang sedang aktif.
     */
    public function export(Request $request): StreamedResponse
    {
        $participants = $this->filteredParticipants($request)->oldest()->lazy();
        $filename = 'laporan-peserta-mtq-2026-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($participants): void {
            $output = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 (nama dengan tanda ’ dll.) dengan benar.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'No', 'Kode Pendaftaran', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
                'Kecamatan', 'Alamat', 'Lembaga', 'WhatsApp', 'Email', 'Cabang', 'Kategori',
                'Biaya Pendaftaran', 'Kode Unik', 'Total Transfer', 'Status Pembayaran',
                'Tanggal Unggah Bukti', 'Tanggal Lunas', 'Catatan Pembayaran', 'Status Data', 'Tanggal Daftar',
            ], ';', escape: '');

            foreach ($participants as $index => $participant) {
                fputcsv($output, [
                    $index + 1,
                    $participant->registration_code,
                    $this->csvText($participant->full_name),
                    $participant->gender === 'male' ? 'Laki-laki' : 'Perempuan',
                    $this->csvText($participant->birth_place),
                    $participant->birth_date->format('d/m/Y'),
                    $participant->district,
                    $this->csvText($participant->address),
                    $this->csvText($participant->institution),
                    // Ditulis sebagai ="08…" agar Excel tidak membuang angka 0 di depan.
                    '="'.preg_replace('/[^\d+]/', '', $participant->phone).'"',
                    $this->csvText($participant->email),
                    $participant->branchLabel(),
                    $participant->categoryLabel(),
                    $participant->registration_fee,
                    $participant->formattedPaymentUniqueCode(),
                    $participant->paymentAmount(),
                    $participant->paymentStatusLabel(),
                    $participant->payment_uploaded_at?->format('d/m/Y H:i'),
                    $participant->payment_verified_at?->format('d/m/Y H:i'),
                    $this->csvText($participant->payment_note),
                    Participant::STATUSES[$participant->status],
                    $participant->created_at->format('d/m/Y H:i'),
                ], ';', escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function receipt(Participant $participant): View
    {
        return view('registration.receipt', [
            'participant' => $participant,
            'backUrl' => route('admin.participants.show', $participant),
        ]);
    }

    public function show(Participant $participant): View
    {
        return view('admin.participants.show', [
            'participant' => $participant,
            'statuses' => Participant::STATUSES,
            'paymentStatuses' => Participant::PAYMENT_STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Participant::STATUSES))],
        ]);

        $participant->update($validated);

        return back()->with('success', 'Status peserta berhasil diperbarui.');
    }

    public function updatePayment(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['verified', 'rejected'])],
            'payment_note' => ['nullable', 'required_if:payment_status,rejected', 'string', 'max:500'],
        ], [
            'payment_note.required_if' => 'Tuliskan alasan penolakan agar peserta dapat memperbaikinya.',
        ]);

        if ($participant->payment_proof_path === null) {
            return back()->withErrors(['payment_status' => 'Peserta belum mengunggah bukti pembayaran.']);
        }

        $participant->update([
            'payment_status' => $validated['payment_status'],
            'payment_note' => $validated['payment_status'] === 'rejected' ? $validated['payment_note'] : null,
            'payment_verified_at' => $validated['payment_status'] === 'verified' ? now() : null,
        ]);

        return back()->with('success', $validated['payment_status'] === 'verified'
            ? 'Pembayaran ditandai lunas.'
            : 'Bukti pembayaran ditolak. Peserta dapat mengunggah ulang.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $participant->delete();

        if ($participant->payment_proof_path !== null) {
            Storage::disk('local')->delete($participant->payment_proof_path);
        }

        return redirect()
            ->route('admin.participants.index')
            ->with('success', "Data peserta {$participant->full_name} ({$participant->registration_code}) berhasil dihapus.");
    }

    public function paymentProof(Participant $participant): StreamedResponse
    {
        abort_if($participant->payment_proof_path === null || ! Storage::disk('local')->exists($participant->payment_proof_path), 404);

        return Storage::disk('local')->response($participant->payment_proof_path);
    }

    /**
     * Cegah isian peserta dibaca Excel sebagai rumus (CSV injection).
     */
    private function csvText(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @return Builder<Participant>
     */
    private function filteredParticipants(Request $request): Builder
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Participant::STATUSES))],
            'payment_status' => ['nullable', Rule::in(array_keys(Participant::PAYMENT_STATUSES))],
            'branch' => ['nullable', Rule::in(array_keys(Participant::BRANCHES))],
        ]);

        return Participant::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('registration_code', 'like', "%{$search}%");

                    // Nominal transfer ("Rp 75.123") atau kode unik ("123") dicocokkan lewat tiga digit terakhir.
                    $digits = preg_replace('/\D/', '', $search);

                    if (preg_match('/^(rp\.?)?[\d.,\s]+$/i', $search) === 1 && strlen($digits) >= 3) {
                        $query->orWhere('payment_unique_code', (int) substr($digits, -3));
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($filters['branch'] ?? null, fn ($query, string $branch) => $query->where('branch', $branch));
    }
}
