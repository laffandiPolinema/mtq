<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competition_branches', function (Blueprint $table) {
            $table->id();
            // Kode tetap (dipakai di data peserta); tidak berubah walau nama cabang diganti.
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->boolean('is_open')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_branches');
    }
};
