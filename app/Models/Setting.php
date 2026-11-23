<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Pengaturan yang dapat diubah panitia (key–value), dimuat sekali per siklus aplikasi.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE = 'settings.values';

    protected static function booted(): void
    {
        static::saved(fn () => self::flush());
        static::deleted(fn () => self::flush());
    }

    public static function get(string $key): ?string
    {
        if (! app()->has(self::CACHE)) {
            try {
                app()->instance(self::CACHE, self::query()->pluck('value', 'key')->all());
            } catch (QueryException) {
                // Tabel belum dimigrasi (mis. sesaat setelah deploy): pakai nilai bawaan.
                return null;
            }
        }

        return app(self::CACHE)[$key] ?? null;
    }

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::CACHE);
    }
}
