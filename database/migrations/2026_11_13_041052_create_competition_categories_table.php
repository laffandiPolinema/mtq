<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_branch_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('fee');
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->boolean('is_open')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['competition_branch_id', 'code']);
        });

        $this->seedJuknisDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_categories');
    }

    /**
     * Data awal sesuai Juknis Perlombaan MTQ Kota Malang 2026. Kode cabang/kategori
     * sama dengan yang sudah tersimpan di data peserta sebelumnya.
     */
    private function seedJuknisDefaults(): void
    {
        $now = now();
        $branches = [
            ['tilawah', 'MTQ (Tilawah Al-Qur’an)', [
                ['anak-anak', 'Tilawah Anak', 'SD/MI s.d. kelas 8 SMP/MTs', 35000, null],
                ['remaja', 'Tilawah Remaja', 'Kelas 9 SMP/MTs s.d. kelas 12 SMA/MA', 50000, null],
                ['dewasa', 'Tilawah Dewasa', 'Mahasiswa s.d. usia maks. 41 tahun', 70000, 41],
            ]],
            ['mhq', 'MHQ (Hifdzil Qur’an)', [
                ['anak-anak', 'Juz 30', 'SD/MI', 35000, null],
            ]],
            ['khot_indah', 'MKQ (Khattil Qur’an)', [
                ['umum', 'Umum', 'Kaligrafi mushaf, khat wajib Naskhi', 35000, null],
            ]],
            ['murottal', 'Murottal', [
                ['anak-anak', 'Tartil Anak', 'SD/MI s.d. kelas 8 SMP/MTs', 35000, null],
            ]],
        ];

        foreach ($branches as $branchOrder => [$code, $name, $categories]) {
            $branchId = DB::table('competition_branches')->insertGetId([
                'code' => $code,
                'name' => $name,
                'is_open' => true,
                'sort_order' => $branchOrder + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($categories as $categoryOrder => [$categoryCode, $categoryName, $description, $fee, $maxAge]) {
                DB::table('competition_categories')->insert([
                    'competition_branch_id' => $branchId,
                    'code' => $categoryCode,
                    'name' => $categoryName,
                    'description' => $description,
                    'fee' => $fee,
                    'max_age' => $maxAge,
                    'is_open' => true,
                    'sort_order' => $categoryOrder + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
