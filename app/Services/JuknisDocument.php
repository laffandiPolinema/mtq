<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * File PDF juknis perlombaan yang dapat diganti panitia.
 *
 * Unggahan panitia disimpan di storage (bukan di folder public), sehingga ikut ter-backup
 * dan tidak hilang saat aplikasi di-deploy ulang. Selama panitia belum mengunggah,
 * yang dipakai adalah file bawaan di public/files.
 */
class JuknisDocument
{
    public const DISK = 'local';

    public const PATH = 'juknis/juknis.pdf';

    public const MAX_KILOBYTES = 8 * 1024;

    public const DOWNLOAD_NAME = 'Juknis MTQ Kota Malang 2026.pdf';

    /**
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return ['required', File::types(['pdf'])->extensions(['pdf'])->max(self::MAX_KILOBYTES)];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'juknis.required' => 'Pilih file PDF juknis terlebih dahulu.',
            'juknis.mimes' => 'Juknis harus berupa file PDF.',
            'juknis.extensions' => 'Juknis harus berupa file PDF.',
            'juknis.max' => 'Ukuran file juknis maksimal 8 MB.',
            'juknis.uploaded' => 'File juknis gagal diunggah. Pastikan ukurannya maksimal 8 MB.',
        ];
    }

    public static function bundledPath(): string
    {
        return public_path('files/juknis-mtq-kota-malang-2026.pdf');
    }

    public function isUploaded(): bool
    {
        return Storage::disk(self::DISK)->exists(self::PATH);
    }

    public function exists(): bool
    {
        return $this->isUploaded() || is_file(self::bundledPath());
    }

    public function absolutePath(): string
    {
        return $this->isUploaded() ? Storage::disk(self::DISK)->path(self::PATH) : self::bundledPath();
    }

    public function size(): int
    {
        return $this->exists() ? (int) filesize($this->absolutePath()) : 0;
    }

    /**
     * Ukuran file yang mudah dibaca, mis. "750 KB" atau "1,2 MB".
     */
    public function sizeLabel(): string
    {
        $bytes = $this->size();

        return $bytes < 1024 * 1024
            ? max(1, (int) round($bytes / 1024)).' KB'
            : number_format($bytes / 1024 / 1024, 1, ',', '.').' MB';
    }

    public function updatedAt(): ?Carbon
    {
        return $this->exists() ? Carbon::createFromTimestamp((int) filemtime($this->absolutePath())) : null;
    }

    /**
     * Alamat publik juknis. Versi (waktu ubah) ikut di URL agar browser tidak menampilkan file lama.
     */
    public function url(): string
    {
        return route('juknis.show', ['v' => $this->updatedAt()?->timestamp]);
    }

    public function replace(UploadedFile $file): void
    {
        $contents = (string) file_get_contents($file->getRealPath());

        if (! str_starts_with($contents, '%PDF-')) {
            throw ValidationException::withMessages(['juknis' => 'File PDF tidak valid atau rusak.']);
        }

        Storage::disk(self::DISK)->put(self::PATH, $contents);
    }

    public function deleteUpload(): void
    {
        Storage::disk(self::DISK)->delete(self::PATH);
    }

    public function response(bool $download = false): BinaryFileResponse
    {
        abort_unless($this->exists(), 404);

        $response = response()->file($this->absolutePath(), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);

        $response->setContentDisposition($download ? 'attachment' : 'inline', self::DOWNLOAD_NAME);

        return $response;
    }
}
