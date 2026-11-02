<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantStatusAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_pages_require_proving_ownership_of_the_code(): void
    {
        Storage::fake('local');
        $participant = Participant::factory()->create();
        $code = $participant->registration_code;

        $this->get(route('status.show', $code))
            ->assertRedirect(route('status.form', ['kode' => $code]));

        $this->get(route('registration.success', $code))
            ->assertRedirect(route('status.form', ['kode' => $code]));

        $this->post(route('registration.payment.store', $code), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('status.form', ['kode' => $code]));

        $this->assertSame('unpaid', $participant->refresh()->payment_status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_code_with_the_registered_whatsapp_number_opens_the_status_page(): void
    {
        $participant = Participant::factory()->create(['phone' => '0812-3456-7890']);

        $this->post(route('status.lookup'), [
            'registration_code' => strtolower($participant->registration_code),
            'phone' => '+62 812 3456 7890',
        ])->assertRedirect(route('status.show', $participant->registration_code));

        $this->get(route('status.show', $participant->registration_code))
            ->assertOk()
            ->assertSee($participant->full_name);
    }

    public function test_a_wrong_whatsapp_number_does_not_reveal_the_participant(): void
    {
        $participant = Participant::factory()->create(['phone' => '081234567890']);

        $this->post(route('status.lookup'), [
            'registration_code' => $participant->registration_code,
            'phone' => '081299999999',
        ])->assertSessionHasErrors([
            'registration_code' => 'Kode pendaftaran atau nomor WhatsApp tidak sesuai.',
        ]);

        $this->post(route('status.lookup'), [
            'registration_code' => 'MTQ26-TIDAKADA00',
            'phone' => '081234567890',
        ])->assertSessionHasErrors([
            'registration_code' => 'Kode pendaftaran atau nomor WhatsApp tidak sesuai.',
        ]);

        $this->get(route('status.show', $participant->registration_code))->assertRedirect();
    }

    public function test_the_whatsapp_number_is_required_for_lookup(): void
    {
        $participant = Participant::factory()->create();

        $this->post(route('status.lookup'), [
            'registration_code' => $participant->registration_code,
        ])->assertSessionHasErrors('phone');
    }

    public function test_access_granted_for_one_code_does_not_open_another_participant(): void
    {
        $own = Participant::factory()->create(['phone' => '081234567890']);
        $other = Participant::factory()->create();

        $this->post(route('status.lookup'), [
            'registration_code' => $own->registration_code,
            'phone' => '081234567890',
        ]);

        $this->get(route('status.show', $other->registration_code))
            ->assertRedirect(route('status.form', ['kode' => $other->registration_code]));
    }
}
