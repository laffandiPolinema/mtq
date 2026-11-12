<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat atau perbarui akun admin dari MTQ_ADMIN_EMAIL dan MTQ_ADMIN_PASSWORD di .env.
     */
    public function run(): void
    {
        $email = strtolower(trim((string) config('mtq.admin.email')));
        $password = (string) config('mtq.admin.password');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            $this->command?->warn('Akun admin dilewati: isi MTQ_ADMIN_EMAIL dan MTQ_ADMIN_PASSWORD (minimal 12 karakter) di .env.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => config('mtq.admin.name'), 'password' => Hash::make($password)],
        );

        $this->command?->info("Akun admin {$email} siap dipakai.");
    }
}
