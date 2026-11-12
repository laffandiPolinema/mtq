<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyimpanan dokumen peserta (identitas, pasfoto, bukti transfer).
 *
 * - Gambar ditulis ulang menjadi JPEG baru: metadata (termasuk lokasi GPS)
 *   terhapus dan file "polyglot" yang menyisipkan kode di dalam gambar ikut netral.
 * - Isi file dienkripsi dengan APP_KEY sebelum ditulis ke disk privat, sehingga
 *   salinan folder storage atau backup tidak bisa dibuka tanpa kunci aplikasi.
 * - File hanya dikirim kembali lewat route yang sudah diotorisasi, dengan header
 *   yang mencegah browser menjalankan isinya.
 */
class ParticipantDocumentStore
{
    private const DISK = 'local';

    /** Batas piksel untuk mencegah "decompression bomb" saat gambar diproses. */
    private const MAX_PIXELS = 40_000_000;

    /**
     * Aturan validasi file. Jenis file dicek dari isinya (bukan hanya nama/ekstensi).
     *
     * @return array<int, mixed>
     */
    public static function rules(string $document): array
    {
        $images = ['jpg', 'jpeg', 'png', 'webp'];
        $imagesOrPdf = [...$images, 'pdf'];

        return match ($document) {
            'photo' => ['required', File::types($images)->extensions($images)->max(2 * 1024)],
            default => ['required', File::types($imagesOrPdf)->extensions($imagesOrPdf)->max(5 * 1024)],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'identity_document.required' => 'Unggah scan/foto identitas diri.',
            'identity_document.mimes' => 'Identitas diri harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'identity_document.extensions' => 'Identitas diri harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'identity_document.max' => 'Ukuran file identitas maksimal 5 MB.',
            'photo.required' => 'Unggah pasfoto terbaru ukuran 3x4.',
            'photo.mimes' => 'Pasfoto harus berupa gambar JPG, PNG, atau WEBP.',
            'photo.extensions' => 'Pasfoto harus berupa gambar JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran pasfoto maksimal 2 MB.',
            'payment_proof.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'payment_proof.mimes' => 'Bukti transfer harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'payment_proof.extensions' => 'Bukti transfer harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'payment_proof.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
            'uploaded' => 'File gagal diunggah. Pastikan ukurannya sesuai batas.',
        ];
    }

    /**
     * @return array{path: string, hash: string}
     */
    public function store(UploadedFile $file, string $directory, string $field, bool $passPhoto = false): array
    {
        $original = (string) file_get_contents($file->getRealPath());
        $isPdf = $file->getMimeType() === 'application/pdf';

        $contents = $isPdf ? $this->ensurePdf($original, $field) : $this->reencodeImage($file, $original, $field, $passPhoto);
        $path = $directory.'/'.Str::random(40).($isPdf ? '.pdf' : '.jpg').'.enc';

        Storage::disk(self::DISK)->put($path, Crypt::encryptString($contents));

        return ['path' => $path, 'hash' => hash('sha256', $original)];
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function response(string $path, string $downloadName): Response
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        $stored = (string) Storage::disk(self::DISK)->get($path);

        try {
            $contents = Crypt::decryptString($stored);
        } catch (DecryptException) {
            // File lama yang diunggah sebelum enkripsi diterapkan.
            $contents = $stored;
        }

        $isPdf = str_contains($path, '.pdf');

        return response($contents, 200, [
            'Content-Type' => $isPdf ? 'application/pdf' : 'image/jpeg',
            'Content-Disposition' => ($isPdf ? 'attachment' : 'inline').'; filename="'.$downloadName.($isPdf ? '.pdf' : '.jpg').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store, max-age=0',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function ensurePdf(string $contents, string $field): string
    {
        if (! str_starts_with($contents, '%PDF-')) {
            throw ValidationException::withMessages([$field => 'File PDF tidak valid atau rusak.']);
        }

        return $contents;
    }

    private function reencodeImage(UploadedFile $file, string $contents, string $field, bool $passPhoto): string
    {
        $size = @getimagesizefromstring($contents);

        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages([$field => 'Gambar tidak valid atau resolusinya terlalu besar.']);
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat dibaca. Coba simpan ulang sebagai JPG atau PNG.']);
        }

        $image = $this->applyExifOrientation($image, $file);

        if ($passPhoto) {
            $this->ensurePassPhoto($image, $field);
        }

        // Latar putih untuk PNG/WEBP transparan agar hasil JPEG tidak hitam.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 88);

        return (string) ob_get_clean();
    }

    /**
     * Pasfoto 3x4: posisi potret dengan rasio lebar:tinggi sekitar 3:4 (toleransi 0,6–0,9).
     */
    private function ensurePassPhoto(\GdImage $image, string $field): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $width / max($height, 1);

        if ($width < 200 || $height < 260) {
            throw ValidationException::withMessages([$field => 'Resolusi pasfoto terlalu kecil (minimal 200 × 260 piksel).']);
        }

        if ($ratio < 0.6 || $ratio > 0.9) {
            throw ValidationException::withMessages([$field => 'Pasfoto harus berukuran 3x4 (posisi tegak/potret). Potong foto terlebih dahulu.']);
        }
    }

    /**
     * Foto dari kamera HP sering tersimpan miring dengan penanda orientasi EXIF.
     * Karena metadata dibuang saat ditulis ulang, rotasinya diterapkan dulu ke piksel.
     */
    private function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
