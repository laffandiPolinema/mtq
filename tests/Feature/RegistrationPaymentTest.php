<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tanggal di dalam masa pendaftaran (7 November – 24 Desember 2026).
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_registration_assigns_the_fee_plus_a_unique_code_and_shows_payment_instructions(): void
    {
        $this->post(route('registration.store'), $this->validRegistration())->assertRedirect();

        $participant = Participant::firstOrFail();

        $this->assertSame(50000, $participant->registration_fee);
        $this->assertGreaterThanOrEqual(1, $participant->payment_unique_code);
        $this->assertLessThanOrEqual(999, $participant->payment_unique_code);
        $this->assertSame(50000 + $participant->payment_unique_code, $participant->paymentAmount());
        $this->assertSame('unpaid', $participant->payment_status);

        $this->get(route('registration.success', $participant->registration_code))
            ->assertOk()
            ->assertSee($participant->formattedPaymentUniqueCode())
            ->assertSee('144-00-3024646-3')
            ->assertSee('Unggah bukti transfer');
    }

    public function test_each_registration_receives_a_unique_code_that_no_other_participant_holds(): void
    {
        $this->occupyUniqueCodesExcept(417);

        $this->post(route('registration.store'), $this->validRegistration())->assertRedirect();

        $this->assertDatabaseHas('participants', ['full_name' => 'Siti Aminah', 'payment_unique_code' => 417]);
    }

    public function test_registration_is_refused_when_every_unique_code_is_taken(): void
    {
        $this->occupyUniqueCodesExcept(null);

        $this->post(route('registration.store'), $this->validRegistration())
            ->assertSessionHasErrors('full_name');

        $this->assertDatabaseMissing('participants', ['full_name' => 'Siti Aminah']);
    }

    public function test_a_participant_can_upload_payment_proof_with_their_registration_code(): void
    {
        Storage::fake('local');
        $participant = Participant::factory()->create();

        $this->withParticipantAccess($participant)->post(route('registration.payment.store', $participant->registration_code), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('status.show', $participant->registration_code));

        $participant->refresh();

        $this->assertSame('waiting', $participant->payment_status);
        $this->assertNotNull($participant->payment_uploaded_at);
        Storage::disk('local')->assertExists($participant->payment_proof_path);

        $this->get(route('status.show', $participant->registration_code))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi')
            ->assertDontSee('Unggah bukti transfer');
    }

    public function test_payment_proof_must_be_an_image_or_pdf(): void
    {
        Storage::fake('local');
        $participant = Participant::factory()->create();

        $this->withParticipantAccess($participant)->post(route('registration.payment.store', $participant->registration_code), [
            'payment_proof' => UploadedFile::fake()->create('bukti.exe', 10),
        ])->assertSessionHasErrors('payment_proof');

        $this->assertSame('unpaid', $participant->refresh()->payment_status);
    }

    public function test_proof_cannot_be_replaced_while_waiting_or_after_verification(): void
    {
        Storage::fake('local');

        foreach (['waiting', 'verified'] as $status) {
            $participant = Participant::factory()->withPaymentProof($status)->create();
            $originalPath = $participant->payment_proof_path;

            $this->withParticipantAccess($participant)->post(route('registration.payment.store', $participant->registration_code), [
                'payment_proof' => UploadedFile::fake()->image('baru.jpg'),
            ])->assertSessionHasErrors('payment_proof');

            $this->assertSame($originalPath, $participant->refresh()->payment_proof_path);
            $this->assertSame($status, $participant->payment_status);
        }
    }

    public function test_a_rejected_proof_can_be_replaced_and_the_old_file_is_removed(): void
    {
        Storage::fake('local');
        $oldPath = UploadedFile::fake()->image('lama.jpg')->store('payment-proofs', 'local');
        $participant = Participant::factory()->create([
            'payment_status' => 'rejected',
            'payment_proof_path' => $oldPath,
            'payment_note' => 'Nominal tidak sesuai',
        ]);

        $this->withParticipantAccess($participant)->post(route('registration.payment.store', $participant->registration_code), [
            'payment_proof' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect();

        $participant->refresh();

        $this->assertSame('waiting', $participant->payment_status);
        $this->assertNull($participant->payment_note);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($participant->payment_proof_path);
    }

    public function test_guests_cannot_view_proofs_or_change_payment_status(): void
    {
        $participant = Participant::factory()->withPaymentProof()->create();

        $this->get(route('admin.participants.payment-proof', $participant))->assertRedirect(route('login'));
        $this->get(route('admin.participants.show', $participant))->assertRedirect(route('login'));
        $this->patch(route('admin.participants.payment', $participant), ['payment_status' => 'verified'])
            ->assertRedirect(route('login'));

        $this->assertSame('waiting', $participant->refresh()->payment_status);
    }

    public function test_admin_can_view_proof_and_verify_payment(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $path = UploadedFile::fake()->image('bukti.jpg')->store('payment-proofs', 'local');
        $participant = Participant::factory()->create([
            'payment_status' => 'waiting',
            'payment_proof_path' => $path,
            'payment_uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.participants.show', $participant))
            ->assertOk()
            ->assertSee($participant->formattedPaymentUniqueCode());

        $this->actingAs($admin)
            ->get(route('admin.participants.payment-proof', $participant))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.participants.payment', $participant), ['payment_status' => 'verified'])
            ->assertRedirect();

        $participant->refresh();

        $this->assertSame('verified', $participant->payment_status);
        $this->assertNotNull($participant->payment_verified_at);
    }

    public function test_rejecting_a_payment_requires_a_reason(): void
    {
        $admin = User::factory()->create();
        $participant = Participant::factory()->withPaymentProof()->create();

        $this->actingAs($admin)
            ->patch(route('admin.participants.payment', $participant), ['payment_status' => 'rejected'])
            ->assertSessionHasErrors('payment_note');

        $this->assertSame('waiting', $participant->refresh()->payment_status);

        $this->actingAs($admin)
            ->patch(route('admin.participants.payment', $participant), [
                'payment_status' => 'rejected',
                'payment_note' => 'Nominal tidak sesuai kode unik',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'payment_status' => 'rejected',
            'payment_note' => 'Nominal tidak sesuai kode unik',
        ]);
    }

    public function test_payment_cannot_be_verified_before_a_proof_is_uploaded(): void
    {
        $admin = User::factory()->create();
        $participant = Participant::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.participants.payment', $participant), ['payment_status' => 'verified'])
            ->assertSessionHasErrors('payment_status');

        $this->assertSame('unpaid', $participant->refresh()->payment_status);
    }

    public function test_admin_can_delete_a_participant_together_with_their_payment_proof(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $path = UploadedFile::fake()->image('bukti.jpg')->store('payment-proofs', 'local');
        $participant = Participant::factory()->create(['payment_status' => 'waiting', 'payment_proof_path' => $path]);

        $this->actingAs($admin)
            ->delete(route('admin.participants.destroy', $participant))
            ->assertRedirect(route('admin.participants.index'));

        $this->assertModelMissing($participant);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_guests_cannot_delete_participants(): void
    {
        $participant = Participant::factory()->create();

        $this->delete(route('admin.participants.destroy', $participant))->assertRedirect(route('login'));

        $this->assertModelExists($participant);
    }

    public function test_admin_can_find_a_participant_by_transfer_amount(): void
    {
        $admin = User::factory()->create();
        $match = Participant::factory()->create(['payment_unique_code' => 123]);
        $other = Participant::factory()->create(['payment_unique_code' => 456]);

        $this->actingAs($admin)
            ->get(route('admin.participants.index', ['search' => 'Rp 75.123']))
            ->assertOk()
            ->assertSee($match->registration_code)
            ->assertDontSee($other->registration_code);
    }

    private function withParticipantAccess(Participant $participant): static
    {
        return $this->withSession([EnsureParticipantAccess::SESSION_KEY => [$participant->registration_code]]);
    }

    /**
     * Isi kode unik 1–999 untuk peserta lain, kecuali satu kode yang dibiarkan kosong.
     */
    private function occupyUniqueCodesExcept(?int $freeCode): void
    {
        $now = now();
        $rows = collect(range(1, 999))
            ->reject(fn (int $code): bool => $code === $freeCode)
            ->map(fn (int $code): array => [
                ...Participant::factory()->raw(['payment_unique_code' => $code]),
                'registration_code' => sprintf('MTQ26-FILL%06d', $code),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        foreach ($rows->chunk(200) as $chunk) {
            DB::table('participants')->insert($chunk->values()->all());
        }
    }

    private function validRegistration(): array
    {
        return [
            'full_name' => 'Siti Aminah',
            'gender' => 'female',
            'birth_place' => 'Malang',
            'birth_date' => '2008-05-10',
            'district' => 'Blimbing',
            'address' => 'Jl. Ahmad Yani, Malang',
            'institution' => 'SMP Negeri',
            'phone' => '081234567890',
            'email' => 'siti@example.test',
            'branch' => 'tilawah',
            'category' => 'remaja',
        ];
    }
}
