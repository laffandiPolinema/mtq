<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'registration_code',
    'full_name',
    'nik',
    'gender',
    'birth_place',
    'birth_date',
    'district',
    'address',
    'institution',
    'school_address',
    'phone',
    'email',
    'branch',
    'category',
    'status',
    'status_note',
    'identity_document_path',
    'photo_path',
    'registration_fee',
    'payment_unique_code',
    'payment_status',
    'payment_proof_path',
    'payment_proof_hash',
    'payment_uploaded_at',
    'payment_verified_at',
    'payment_verified_by',
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

    /**
     * Dokumen persyaratan yang diunggah peserta: kolom path => label.
     */
    public const DOCUMENTS = [
        'identity_document_path' => 'Identitas diri (KTP/Kartu Pelajar/KIA/Akta)',
        'photo_path' => 'Pasfoto 3x4',
        'payment_proof_path' => 'Bukti pembayaran',
    ];

    protected static function booted(): void
    {
        static::saving(function (Participant $participant): void {
            if ($participant->isDirty('nik')) {
                $participant->nik_hash = $participant->nik === null ? null : self::nikHash($participant->nik);
            }
        });
    }

    /**
     * Sidik NIK (HMAC dengan APP_KEY) untuk pencarian dan keunikan tanpa menyimpan NIK apa adanya.
     */
    public static function nikHash(string $nik): string
    {
        return hash_hmac('sha256', preg_replace('/\D/', '', $nik), (string) config('app.key'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function maskedNik(): ?string
    {
        return $this->nik === null ? null : substr($this->nik, 0, 4).str_repeat('•', 8).substr($this->nik, -4);
    }

    public function canReplaceDocuments(): bool
    {
        return $this->status === 'rejected';
    }

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
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

    public function documentIsPdf(string $column): bool
    {
        return str_contains(strtolower((string) $this->{$column}), '.pdf');
    }
}
