<?php

namespace App\Models;

use Database\Factories\CompetitionCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['competition_branch_id', 'code', 'name', 'description', 'fee', 'max_age', 'is_open', 'sort_order'])]
class CompetitionCategory extends Model
{
    /** @use HasFactory<CompetitionCategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (CompetitionCategory $category): void {
            $category->code ??= self::uniqueCode($category->competition_branch_id, $category->name);
        });

        static::saved(fn () => CompetitionBranch::flushCatalogue());
        static::deleted(fn () => CompetitionBranch::flushCatalogue());
    }

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'max_age' => 'integer',
            'is_open' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CompetitionBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompetitionBranch::class, 'competition_branch_id');
    }

    public function participantsCount(): int
    {
        return Participant::where('branch', $this->branch->code)->where('category', $this->code)->count();
    }

    private static function uniqueCode(int $branchId, string $name): string
    {
        $base = Str::limit(Str::slug($name), 30, '') ?: 'kategori';
        $code = $base;

        for ($suffix = 2; self::where('competition_branch_id', $branchId)->where('code', $code)->exists(); $suffix++) {
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }
}
