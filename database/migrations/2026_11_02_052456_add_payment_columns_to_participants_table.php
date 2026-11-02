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
            $table->unsignedInteger('registration_fee')->default(75000)->after('status');
            $table->unsignedSmallInteger('payment_unique_code')->nullable()->unique()->after('registration_fee');
            $table->string('payment_status', 20)->default('unpaid')->index()->after('payment_unique_code');
            $table->string('payment_proof_path')->nullable()->after('payment_status');
            $table->timestamp('payment_uploaded_at')->nullable()->after('payment_proof_path');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_uploaded_at');
            $table->string('payment_note', 500)->nullable()->after('payment_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['payment_unique_code']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'registration_fee',
                'payment_unique_code',
                'payment_status',
                'payment_proof_path',
                'payment_uploaded_at',
                'payment_verified_at',
                'payment_note',
            ]);
        });
    }
};
