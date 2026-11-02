<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'registration_code',
    'full_name',
    'gender',
    'birth_place',
    'birth_date',
    'district',
    'address',
    'institution',
    'phone',
    'email',
    'branch',
    'category',
    'status',
    'registration_fee',
    'payment_unique_code',
    'payment_status',
    'payment_proof_path',
    'payment_uploaded_at',
    'payment_verified_at',
    'payment_note',
])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    public const DISTRICTS = [
        'Blimbing',
        'Kedungkandang',
        'Klojen',
        'Lowokwaru',
        'Sukun',
    ];

    public const BRANCHES = [
        'tilawah' => 'Tilawah Al-Qur’an',
        'khot_indah' => 'Khot Indah Al-Qur’an',
        'mhq' => 'MHQ',
        'murottal' => 'Murottal',
    ];

    public const TILAWAH_CATEGORIES = [
        'anak-anak' => 'Anak-anak',
        'remaja' => 'Remaja',
        'dewasa' => 'Dewasa',
        'lansia' => 'Lansia',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu verifikasi',
        'approved' => 'Terverifikasi',
        'rejected' => 'Perlu perbaikan',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Belum membayar',
        'waiting' => 'Menunggu konfirmasi',
        'verified' => 'Lunas',
        'rejected' => 'Bukti ditolak',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'registration_fee' => 'integer',
            'payment_unique_code' => 'integer',
            'payment_uploaded_at' => 'datetime',
            'payment_verified_at' => 'datetime',
        ];
    }

    public function branchLabel(): string
    {
        return self::BRANCHES[$this->branch];
    }

    public function categoryLabel(): ?string
    {
        return $this->category === null ? null : self::TILAWAH_CATEGORIES[$this->category];
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status];
    }

    /**
     * Total yang harus ditransfer: biaya pendaftaran ditambah kode unik.
     */
    public function paymentAmount(): int
    {
        return $this->registration_fee + (int) $this->payment_unique_code;
    }

    public function formattedPaymentAmount(): string
    {
        return 'Rp '.number_format($this->paymentAmount(), 0, ',', '.');
    }

    public function formattedPaymentUniqueCode(): string
    {
        return str_pad((string) $this->payment_unique_code, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Samakan format nomor (08…, 8…, 62…, +62…) agar pencocokan tidak bergantung pada cara penulisan.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return match (true) {
            str_starts_with($digits, '62') => '0'.substr($digits, 2),
            str_starts_with($digits, '8') => '0'.$digits,
            default => $digits,
        };
    }

    public function phoneMatches(string $phone): bool
    {
        $normalized = self::normalizePhone($phone);

        return $normalized !== '' && hash_equals(self::normalizePhone($this->phone), $normalized);
    }

    public function canUploadPaymentProof(): bool
    {
        return in_array($this->payment_status, ['unpaid', 'rejected'], true);
    }

    public function paymentProofIsPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->payment_proof_path), '.pdf');
    }
}
