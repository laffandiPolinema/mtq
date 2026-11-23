<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Isi bagian "Dapatkan hadiah" di halaman utama.
 *
 * Panitia mengubahnya di panel admin (tab Hadiah). Selama belum diubah, dipakai nilai
 * bawaan dari config/mtq.php. Bagian disembunyikan jika daftar hadiah dikosongkan.
 */
class PrizeList
{
    /** Jumlah baris hadiah pada formulir admin. */
    public const MAX_ITEMS = 8;

    /**
     * @var array<string, array{label: string, path: string}>
     */
    public const ICONS = [
        'trophy' => ['label' => 'Tropi / piala', 'path' => 'M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v1a4 4 0 0 0 3.5 4M17 6h3v1a4 4 0 0 1-3.5 4M12 14v4M8 21h8M9.5 18h5'],
        'money' => ['label' => 'Uang', 'path' => 'M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM6 10v4M18 10v4'],
        'certificate' => ['label' => 'Sertifikat / piagam', 'path' => 'M4 4h16v11H4zM8 8h8M8 11h5M15 15v6l2-1.5 2 1.5v-6M17 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4z'],
        'gift' => ['label' => 'Bingkisan / souvenir', 'path' => 'M4 10h16v4H4zM5 14h14v7H5zM12 10v11M12 10c-1.5-3-5-4-5.5-2S9 10 12 10zm0 0c1.5-3 5-4 5.5-2S15 10 12 10z'],
        'medal' => ['label' => 'Medali', 'path' => 'M8 3l4 6 4-6M7 3h3M14 3h3M12 9a6 6 0 1 0 0 12 6 6 0 0 0 0-12zm0 3.5 1 2 2.2.3-1.6 1.5.4 2.2-2-1-2 1 .4-2.2-1.6-1.5 2.2-.3z'],
        'star' => ['label' => 'Bintang', 'path' => 'M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z'],
    ];

    public function heading(): string
    {
        return Setting::get('prizes.heading') ?? (string) config('mtq.prizes.heading');
    }

    public function intro(): string
    {
        return Setting::get('prizes.intro') ?? (string) config('mtq.prizes.intro');
    }

    /**
     * @return list<array{icon: string, title: string, description: string}>
     */
    public function items(): array
    {
        $stored = Setting::get('prizes.items');
        $items = $stored === null ? config('mtq.prizes.items', []) : (json_decode($stored, true) ?: []);

        return array_values(array_map(fn (array $item): array => [
            'icon' => isset(self::ICONS[$item['icon'] ?? '']) ? $item['icon'] : 'trophy',
            'title' => (string) ($item['title'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
        ], $items));
    }

    public function iconPath(string $icon): string
    {
        return (self::ICONS[$icon] ?? self::ICONS['trophy'])['path'];
    }

    /**
     * Baris tanpa nama hadiah diabaikan.
     *
     * @param  array{heading: string, intro?: string|null, items?: array<int, array{icon?: string|null, title?: string|null, description?: string|null}>}  $values
     */
    public function update(array $values): void
    {
        $items = collect($values['items'] ?? [])
            ->filter(fn (array $item): bool => trim((string) ($item['title'] ?? '')) !== '')
            ->map(fn (array $item): array => [
                'icon' => $item['icon'] ?? 'trophy',
                'title' => trim($item['title']),
                'description' => trim((string) ($item['description'] ?? '')),
            ])
            ->values()
            ->all();

        Setting::put('prizes.heading', $values['heading']);
        Setting::put('prizes.intro', $values['intro'] ?? '');
        Setting::put('prizes.items', json_encode($items, JSON_UNESCAPED_UNICODE));
    }
}
