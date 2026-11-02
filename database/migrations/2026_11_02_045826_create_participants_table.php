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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('registration_code', 20)->unique();
            $table->string('full_name');
            $table->string('gender', 10);
            $table->string('birth_place');
            $table->date('birth_date');
            $table->string('district', 30)->index();
            $table->text('address');
            $table->string('institution')->nullable();
            $table->string('phone', 25);
            $table->string('email')->nullable();
            $table->string('branch', 30)->index();
            $table->string('category', 30)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
