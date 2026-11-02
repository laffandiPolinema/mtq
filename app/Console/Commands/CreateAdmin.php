<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('mtq:make-admin {name?} {email?}')]
#[Description('Create or update an account for the MTQ registration administrator')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = $this->argument('name') ?? $this->ask('Nama panitia');
        $email = strtolower($this->argument('email') ?? $this->ask('Email panitia'));

        if (trim($name) === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Nama dan alamat email yang valid wajib diisi.');

            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi (minimal 12 karakter)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('Kata sandi harus terdiri dari minimal 12 karakter.');

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)],
        );

        $this->info("Akun panitia untuk {$email} berhasil disimpan.");

        return self::SUCCESS;
    }
}
