<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\CompetitionBranch;
use App\Models\Participant;
use App\Services\ParticipantDocumentStore;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('registration.create', [
            'branchConfig' => CompetitionBranch::catalogue(),
            'openCatalogue' => CompetitionBranch::openCatalogue(),
            'districts' => Participant::DISTRICTS,
            'period' => $this->registrationPeriod(),
        ]);
    }

    public function store(Request $request, ParticipantDocumentStore $documents): RedirectResponse
    {
        $period = $this->registrationPeriod();

        if ($period['status'] !== 'open') {
            throw ValidationException::withMessages([
                'full_name' => $period['status'] === 'upcoming'
                    ? 'Pendaftaran baru dibuka pada '.$period['opens_at']->translatedFormat('d F Y').'.'
                    : 'Pendaftaran sudah ditutup pada '.$period['closes_at']->translatedFormat('d F Y').'.',
            ]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'district' => ['required', Rule::in(Participant::DISTRICTS)],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'regex:/^(\+?62|0)8[\d\s-]{7,15}$/'],
            'institution' => ['required', 'string', 'max:255'],
            'school_address' => ['required', 'string', 'max:2000'],
            'branch' => ['required', Rule::in(array_keys(Participant::openBranches()))],
            'category' => [
                'required',
                Rule::in(array_keys(Participant::openCategoriesFor((string) $request->input('branch')))),
            ],
            'identity_document' => ParticipantDocumentStore::rules('identity_document'),
            'photo' => ParticipantDocumentStore::rules('photo'),
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'phone.regex' => 'Masukkan nomor WhatsApp yang valid, contoh 081234567890.',
            'date' => 'Format tanggal lahir tidak valid.',
            'before_or_equal' => ':attribute tidak boleh melebihi hari ini.',
            'in' => 'Pilihan :attribute tidak valid.',
            'max.string' => ':attribute maksimal :max karakter.',
            ...ParticipantDocumentStore::messages(),
        ], [
            'full_name' => 'nama lengkap',
            'nik' => 'NIK',
            'birth_date' => 'tanggal lahir',
            'gender' => 'jenis kelamin',
            'district' => 'kecamatan',
            'address' => 'alamat domisili',
            'phone' => 'nomor WhatsApp',
            'institution' => 'asal sekolah/madrasah/lembaga',
            'school_address' => 'alamat sekolah',
            'branch' => 'cabang lomba',
            'category' => 'kategori',
        ]);

        $this->ensureNikNotRegistered($validated['nik']);
        $this->ensureAgeFitsCategory($validated);

        $identity = $documents->store($request->file('identity_document'), 'identity-documents', 'identity_document');

        try {
            $photo = $documents->store($request->file('photo'), 'photos', 'photo');
        } catch (\Throwable $exception) {
            $documents->delete($identity['path']);

            throw $exception;
        }

        unset($validated['identity_document'], $validated['photo']);
        $validated['identity_document_path'] = $identity['path'];
        $validated['photo_path'] = $photo['path'];

        try {
            $participant = $this->createParticipant($validated);
        } catch (\Throwable $exception) {
            // Pendaftaran gagal: jangan tinggalkan dokumen tanpa pemilik di storage.
            $documents->delete($identity['path']);
            $documents->delete($photo['path']);
            $this->ensureNikNotRegistered($validated['nik']);

            throw $exception;
        }

        EnsureParticipantAccess::grant($request, $participant->registration_code);

        return redirect()->route('registration.success', $participant->registration_code);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createParticipant(array $attributes): Participant
    {
        for ($attempt = 1; ; $attempt++) {
            $uniqueCode = $this->availablePaymentUniqueCode();

            if ($uniqueCode === null) {
                throw ValidationException::withMessages([
                    'full_name' => 'Kuota pendaftaran online sudah penuh. Silakan hubungi panitia.',
                ]);
            }

            try {
                return Participant::create([
                    ...$attributes,
                    'registration_code' => $this->newRegistrationCode(),
                    'registration_fee' => Participant::feeFor($attributes['branch'], $attributes['category']),
                    'payment_unique_code' => $uniqueCode,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                // Kode unik/kode pendaftaran bentrok dengan pendaftar lain yang bersamaan: coba lagi.
                if ($attempt >= 5 || Participant::where('nik_hash', Participant::nikHash($attributes['nik']))->exists()) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Batas usia kategori (mis. Tilawah Dewasa maks. 41 tahun) dihitung pada hari lomba.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureAgeFitsCategory(array $validated): void
    {
        $category = Participant::categoriesFor($validated['branch'])[$validated['category']];
        $maxAge = $category['max_age'] ?? null;
        $eventDate = Carbon::parse(config('mtq.event_date'));
        $age = (int) Carbon::parse($validated['birth_date'])->diffInYears($eventDate);

        if ($maxAge !== null && $age > $maxAge) {
            throw ValidationException::withMessages([
                'birth_date' => "Usia maksimal kategori {$category['label']} adalah {$maxAge} tahun pada hari lomba ({$eventDate->translatedFormat('d F Y')}). Usia peserta saat itu {$age} tahun.",
            ]);
        }
    }

    /**
     * Setiap peserta (NIK) hanya boleh mengikuti satu cabang lomba.
     */
    private function ensureNikNotRegistered(string $nik): void
    {
        if (Participant::where('nik_hash', Participant::nikHash($nik))->exists()) {
            throw ValidationException::withMessages([
                'nik' => 'NIK ini sudah terdaftar. Setiap peserta hanya dapat mengikuti satu cabang lomba. Hubungi panitia jika perlu mengganti cabang.',
            ]);
        }
    }

    /**
     * @return array{status: 'upcoming'|'open'|'closed', opens_at: Carbon, closes_at: Carbon}
     */
    private function registrationPeriod(): array
    {
        $opensAt = Carbon::parse(config('mtq.registration_opens_at'));
        $closesAt = Carbon::parse(config('mtq.registration_closes_at'));

        return [
            'status' => match (true) {
                now()->lt($opensAt) => 'upcoming',
                now()->gt($closesAt) => 'closed',
                default => 'open',
            },
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
        ];
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
