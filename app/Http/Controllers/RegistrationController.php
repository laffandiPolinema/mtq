<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('registration.create', [
            'branches' => Participant::BRANCHES,
            'categories' => Participant::TILAWAH_CATEGORIES,
            'districts' => Participant::DISTRICTS,
            'registrationFee' => config('mtq.registration_fee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'district' => ['required', Rule::in(Participant::DISTRICTS)],
            'address' => ['required', 'string', 'max:2000'],
            'institution' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch' => ['required', Rule::in(array_keys(Participant::BRANCHES))],
            'category' => [
                Rule::requiredIf($request->input('branch') === 'tilawah'),
                'nullable',
                Rule::in(array_keys(Participant::TILAWAH_CATEGORIES)),
            ],
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'email' => 'Masukkan alamat email yang valid.',
            'date' => 'Format tanggal lahir tidak valid.',
            'before_or_equal' => ':attribute tidak boleh melebihi hari ini.',
            'in' => 'Pilihan :attribute tidak valid.',
            'max.string' => ':attribute maksimal :max karakter.',
        ], [
            'full_name' => 'nama lengkap',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'district' => 'kecamatan',
            'address' => 'alamat',
            'institution' => 'lembaga',
            'phone' => 'nomor WhatsApp',
            'branch' => 'cabang lomba',
            'category' => 'kategori tilawah',
        ]);

        if ($validated['branch'] !== 'tilawah') {
            $validated['category'] = null;
        }

        $participant = null;

        for ($attempt = 1; $participant === null; $attempt++) {
            $uniqueCode = $this->availablePaymentUniqueCode();

            if ($uniqueCode === null) {
                throw ValidationException::withMessages([
                    'full_name' => 'Kuota pendaftaran online sudah penuh. Silakan hubungi panitia.',
                ]);
            }

            try {
                $participant = Participant::create([
                    ...$validated,
                    'registration_code' => $this->newRegistrationCode(),
                    'registration_fee' => config('mtq.registration_fee'),
                    'payment_unique_code' => $uniqueCode,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 5) {
                    throw $exception;
                }
            }
        }

        EnsureParticipantAccess::grant($request, $participant->registration_code);

        return redirect()->route('registration.success', $participant->registration_code);
    }

    private function newRegistrationCode(): string
    {
        do {
            $code = 'MTQ26-'.Str::upper(Str::random(10));
        } while (Participant::where('registration_code', $code)->exists());

        return $code;
    }

    /**
     * Pilih kode unik 1–999 yang belum dipakai pendaftar lain, sehingga setiap
     * nominal transfer (biaya + kode unik) hanya cocok dengan satu peserta.
     */
    private function availablePaymentUniqueCode(): ?int
    {
        $usedCodes = Participant::whereNotNull('payment_unique_code')->pluck('payment_unique_code')->all();
        $availableCodes = array_values(array_diff(range(1, 999), $usedCodes));

        return $availableCodes === [] ? null : Arr::random($availableCodes);
    }

    public function success(string $code): View
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        return view('registration.success', compact('participant'));
    }

    public function receipt(string $code): View
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        return view('registration.receipt', [
            'participant' => $participant,
            'backUrl' => route('status.show', $participant->registration_code),
        ]);
    }

    public function statusForm(): View
    {
        return view('registration.status-form');
    }

    public function lookupStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:25'],
        ], [
            'registration_code.required' => 'Kode pendaftaran wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $participant = Participant::where('registration_code', Str::upper(trim($validated['registration_code'])))->first();

        if ($participant === null || ! $participant->phoneMatches($validated['phone'])) {
            return back()->withErrors([
                'registration_code' => 'Kode pendaftaran atau nomor WhatsApp tidak sesuai.',
            ])->onlyInput('registration_code');
        }

        $request->session()->regenerate();
        EnsureParticipantAccess::grant($request, $participant->registration_code);

        return redirect()->route('status.show', $participant->registration_code);
    }

    public function status(string $code): View
    {
        $participant = Participant::where('registration_code', $code)->firstOrFail();

        return view('registration.status', compact('participant'));
    }
}
