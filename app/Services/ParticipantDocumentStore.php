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

    /**
     * Gambar hingga batas ini ditulis ulang. Gambar yang lebih besar (mis. kamera HP 48 MP)
     * disimpan apa adanya agar tidak ditolak hanya karena resolusinya dan tidak menghabiskan memori.
     */
    private const MAX_REENCODE_PIXELS = 24_000_000;

    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

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
    public function store(UploadedFile $file, string $directory, string $field): array
    {
        $original = (string) file_get_contents($file->getRealPath());

        [$contents, $extension] = $file->getMimeType() === 'application/pdf'
            ? [$this->ensurePdf($original, $field), 'pdf']
            : $this->prepareImage($file, $original, $field);

        $path = $directory.'/'.Str::random(40).'.'.$extension.'.enc';

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

        $extension = strtolower(pathinfo(Str::beforeLast($path, '.enc'), PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $mime = self::MIME_TYPES[$extension] ?? 'application/octet-stream';
        $disposition = str_starts_with($mime, 'image/') ? 'inline' : 'attachment';

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$downloadName.'.'.$extension.'"',
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

    /**
     * @return array{0: string, 1: string} isi file dan ekstensinya
     */
    private function prepareImage(UploadedFile $file, string $contents, string $field): array
    {
        $size = @getimagesizefromstring($contents);
        $extension = match ($size[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if ($extension === null) {
            throw ValidationException::withMessages([$field => 'File gambar tidak valid atau rusak. Gunakan JPG, PNG, atau WEBP.']);
        }

        if ($size[0] * $size[1] > self::MAX_REENCODE_PIXELS) {
            return [$contents, $extension];
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat dibaca. Coba simpan ulang sebagai JPG atau PNG.']);
        }

        $image = $this->applyExifOrientation($image, $file);

        if ($extension !== 'jpg') {
            // Latar putih untuk PNG/WEBP transparan agar hasil JPEG tidak hitam.
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $image = $canvas;
        }

        ob_start();
        imagejpeg($image, null, 88);

        return [(string) ob_get_clean(), 'jpg'];
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
