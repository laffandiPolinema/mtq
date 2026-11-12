<?php

namespace App\Models;

use Database\Factories\CompetitionBranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'is_open', 'sort_order'])]
class CompetitionBranch extends Model
{
    /** @use HasFactory<CompetitionBranchFactory> */
    use HasFactory;

    /**
     * @var array<string, array{label: string, open: bool, categories: array<string, array{label: string, description: ?string, fee: int, max_age: ?int, open: bool}>}>|null
     */
    private static ?array $catalogue = null;

    protected static function booted(): void
    {
        static::creating(function (CompetitionBranch $branch): void {
            $branch->code ??= self::uniqueCode($branch->name);
        });

        static::saved(fn () => self::flushCatalogue());
        static::deleted(fn () => self::flushCatalogue());
    }

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<CompetitionCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CompetitionCategory::class)->orderBy('sort_order')->orderBy('id');
    }

    public function participantsCount(): int
    {
        return Participant::where('branch', $this->code)->count();
    }

    /**
     * Seluruh cabang dan kategori (urut sesuai tampilan), dimuat sekali per request.
     *
     * @return array<string, array{label: string, open: bool, categories: array<string, array{label: string, description: ?string, fee: int, max_age: ?int, open: bool}>}>
     */
    public static function catalogue(): array
    {
        return self::$catalogue ??= self::query()
            ->with('categories')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (CompetitionBranch $branch): array => [$branch->code => [
                'label' => $branch->name,
                'open' => $branch->is_open,
                'categories' => $branch->categories->mapWithKeys(fn (CompetitionCategory $category): array => [$category->code => [
                    'label' => $category->name,
                    'description' => $category->description,
                    'fee' => $category->fee,
                    'max_age' => $category->max_age,
                    'open' => $category->is_open,
                ]])->all(),
            ]])
            ->all();
    }

    /**
     * Cabang dan kategori yang dapat dipilih di formulir pendaftaran.
     *
     * @return array<string, array{label: string, open: bool, categories: array<string, array{label: string, description: ?string, fee: int, max_age: ?int, open: bool}>}>
     */
    public static function openCatalogue(): array
    {
        return array_filter(
            array_map(function (array $branch): array {
                $branch['categories'] = array_filter($branch['categories'], fn (array $category): bool => $category['open']);

                return $branch;
            }, self::catalogue()),
            fn (array $branch): bool => $branch['open'] && $branch['categories'] !== [],
        );
    }

    public static function flushCatalogue(): void
    {
        self::$catalogue = null;
    }

    private static function uniqueCode(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 30, '') ?: 'cabang';
        $code = $base;

        for ($suffix = 2; self::where('code', $code)->exists(); $suffix++) {
            $code = "{$base}_{$suffix}";
        }

        return $code;
    }
}
