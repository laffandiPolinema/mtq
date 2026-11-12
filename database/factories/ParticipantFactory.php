<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_code' => 'MTQ26-'.Str::upper(Str::random(10)),
            'full_name' => fake()->name(),
            'nik' => fake()->unique()->numerify('3573############'),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_place' => 'Malang',
            'birth_date' => fake()->dateTimeBetween('-60 years', '-8 years')->format('Y-m-d'),
            'district' => fake()->randomElement(Participant::DISTRICTS),
            'address' => fake()->streetAddress(),
            'institution' => 'SD Negeri '.fake()->numberBetween(1, 50).' Malang',
            'school_address' => fake()->streetAddress(),
            'phone' => '08'.fake()->numerify('##########'),
            'email' => null,
            'branch' => 'mhq',
            'category' => 'anak-anak',
            'status' => 'pending',
            'registration_fee' => 35000,
            'payment_unique_code' => fake()->unique()->numberBetween(1, 999),
            'payment_status' => 'unpaid',
        ];
    }

    public function withPaymentProof(string $status = 'waiting'): static
    {
        return $this->state(fn (): array => [
            'payment_status' => $status,
            'payment_proof_path' => 'payment-proofs/'.Str::random(20).'.jpg',
            'payment_uploaded_at' => now(),
        ]);
    }
}
