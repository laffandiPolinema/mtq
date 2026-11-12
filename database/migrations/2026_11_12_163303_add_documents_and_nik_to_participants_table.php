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
        Schema::table('participants', function (Blueprint $table) {
            // NIK disimpan terenkripsi; nik_hash (HMAC) dipakai untuk memastikan satu NIK hanya satu lomba.
            $table->text('nik')->nullable()->after('full_name');
            $table->char('nik_hash', 64)->nullable()->unique()->after('nik');
            $table->text('school_address')->nullable()->after('institution');
            $table->string('identity_document_path')->nullable()->after('status');
            $table->string('photo_path')->nullable()->after('identity_document_path');
            $table->string('status_note', 500)->nullable()->after('status');
            $table->char('payment_proof_hash', 64)->nullable()->index()->after('payment_proof_path');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_verified_at')->constrained('users')->nullOnDelete();
            $table->string('birth_place')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropUnique(['nik_hash']);
            $table->dropIndex(['payment_proof_hash']);
            $table->dropColumn([
                'nik',
                'nik_hash',
                'school_address',
                'identity_document_path',
                'photo_path',
                'status_note',
                'payment_proof_hash',
            ]);
        });
    }
};
