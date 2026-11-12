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

    /**
     * Semua cabang lomba (termasuk yang belum dibuka), sesuai config/mtq.php.
     *
     * @return array<string, string>
     */
    public static function branches(): array
    {
        return array_map(fn (array $branch): string => $branch['label'], config('mtq.branches'));
    }

    /**
     * Cabang yang sudah dapat dipilih di formulir pendaftaran.
     *
     * @return array<string, string>
     */
    public static function openBranches(): array
    {
        return array_intersect_key(
            self::branches(),
            array_filter(config('mtq.branches'), fn (array $branch): bool => $branch['open']),
        );
    }

    /**
     * @return array<string, array{label: string, description: string, fee: int}>
     */
    public static function categoriesFor(string $branch): array
    {
        return config("mtq.branches.{$branch}.categories", []);
    }

    public static function feeFor(string $branch, string $category): ?int
    {
        return self::categoriesFor($branch)[$category]['fee'] ?? null;
    }

    public function branchLabel(): string
    {
        return self::branches()[$this->branch] ?? $this->branch;
    }

    public function categoryLabel(): ?string
    {
        if ($this->category === null) {
            return null;
        }

        $category = self::categoriesFor($this->branch)[$this->category] ?? null;

        // Kategori lama yang sudah tidak ada di konfigurasi (mis. "lansia") tetap ditampilkan apa adanya.
        return $category === null
            ? ucfirst($this->category)
            : "{$category['label']} ({$category['description']})";
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
