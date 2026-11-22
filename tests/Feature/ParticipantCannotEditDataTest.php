<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Peserta hanya boleh mendaftar sekali, mengunggah bukti transfer, dan mengganti dokumen
 * jika diminta panitia. Data pendaftaran, status, dan pembayaran tidak boleh bisa diubah.
 */
class ParticipantCannotEditDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Field yang dicoba disusupkan oleh peserta nakal.
     *
     * @var array<string, mixed>
     */
    private const TAMPERED_FIELDS = [
        'full_name' => 'Nama Diganti',
        'nik' => '3573999999999999',
        'birth_date' => '2015-01-01',
        'gender' => 'male',
        'district' => 'Sukun',
        'address' => 'Alamat diganti',
        'phone' => '089999999999',
        'institution' => 'Sekolah diganti',
        'school_address' => 'Alamat sekolah diganti',
        'branch' => 'tilawah',
        'category' => 'dewasa',
        'registration_code' => 'MTQ26-HACKED0000',
        'registration_fee' => 1000,
        'payment_unique_code' => 1,
        'status' => 'approved',
        'status_note' => 'disetujui sendiri',
        'payment_status' => 'verified',
        'payment_verified_at' => '2026-11-20 10:00:00',
        'payment_verified_by' => 1,
        'payment_note' => 'lunas',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_extra_fields_sent_during_registration_cannot_set_status_fee_or_code(): void
    {
        $this->post(route('registration.store'), [
            ...self::TAMPERED_FIELDS,
            'full_name' => 'Siti Aminah',
            'nik' => '3573011111111111',
            'birth_date' => '2008-05-10',
            'phone' => '081234567890',
            'branch' => 'tilawah',
            'category' => 'remaja',
            'identity_document' => UploadedFile::fake()->image('kartu.jpg'),
            'photo' => UploadedFile::fake()->image('pasfoto.jpg'),
        ])->assertRedirect();

        $participant = Participant::sole();

        $this->assertSame('pending', $participant->status);
        $this->assertNull($participant->status_note);
        $this->assertSame('unpaid', $participant->payment_status);
        $this->assertNull($participant->payment_verified_at);
        $this->assertNull($participant->payment_verified_by);
        $this->assertSame(50000, $participant->registration_fee);
        $this->assertNotSame('MTQ26-HACKED0000', $participant->registration_code);
    }

    public function test_registering_again_with_the_same_nik_does_not_overwrite_existing_data(): void
    {
        $participant = Participant::factory()->create(['nik' => '3573011111111111']);
        $before = $this->snapshot($participant);

        $this->post(route('registration.store'), [
            ...self::TAMPERED_FIELDS,
            'nik' => '3573011111111111',
            'birth_date' => '2008-05-10',
            'category' => 'remaja',
            'identity_document' => UploadedFile::fake()->image('kartu.jpg'),
            'photo' => UploadedFile::fake()->image('pasfoto.jpg'),
        ])->assertSessionHasErrors('nik');

        $this->assertSame(1, Participant::count());
        $this->assertSame($before, $this->snapshot($participant));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function editAttempts(): array
    {
        return [
            'PUT status' => ['put', '/status/{code}'],
            'PATCH status' => ['patch', '/status/{code}'],
            'DELETE status' => ['delete', '/status/{code}'],
            'POST status' => ['post', '/status/{code}'],
            'PUT pendaftaran' => ['put', '/pendaftaran'],
            'PATCH pendaftaran' => ['patch', '/pendaftaran'],
            'PATCH pembayaran' => ['patch', '/status/{code}/pembayaran'],
            'PUT dokumen' => ['put', '/status/{code}/dokumen'],
            'DELETE dokumen' => ['delete', '/status/{code}/dokumen'],
        ];
    }

    #[DataProvider('editAttempts')]
    public function test_there_is_no_route_for_participants_to_edit_their_data(string $method, string $uri): void
    {
        $participant = Participant::factory()->create();
        $before = $this->snapshot($participant);

        $this->asOwner($participant)
            ->{$method}(str_replace('{code}', $participant->registration_code, $uri), self::TAMPERED_FIELDS)
            ->assertStatus(405);

        $this->assertSame($before, $this->snapshot($participant));
    }

    public function test_uploading_payment_proof_only_changes_the_proof_not_data_or_verification(): void
    {
        $participant = Participant::factory()->create();
        $before = $this->snapshot($participant);

        $this->asOwner($participant)
            ->post(route('registration.payment.store', $participant->registration_code), [
                ...self::TAMPERED_FIELDS,
                'payment_proof' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertRedirect(route('status.show', $participant->registration_code));

        $participant->refresh();

        $this->assertSame('waiting', $participant->payment_status);
        $this->assertNull($participant->payment_verified_at);
        $this->assertNull($participant->payment_verified_by);
        $this->assertSame(
            $this->withoutKeys($before, ['payment_proof_path', 'payment_proof_hash', 'payment_status', 'payment_uploaded_at']),
            $this->withoutKeys($this->snapshot($participant), ['payment_proof_path', 'payment_proof_hash', 'payment_status', 'payment_uploaded_at']),
        );
    }

    public function test_replacing_documents_after_a_correction_request_does_not_change_data_or_approve_itself(): void
    {
        $participant = Participant::factory()->create(['status' => 'rejected', 'status_note' => 'Pasfoto buram']);
        $before = $this->snapshot($participant);

        $this->asOwner($participant)
            ->post(route('registration.documents.update', $participant->registration_code), [
                ...self::TAMPERED_FIELDS,
                'photo' => UploadedFile::fake()->image('pasfoto-baru.jpg'),
            ])
            ->assertRedirect(route('status.show', $participant->registration_code));

        $participant->refresh();

        $this->assertSame('pending', $participant->status);
        $this->assertSame(
            $this->withoutKeys($before, ['photo_path', 'status']),
            $this->withoutKeys($this->snapshot($participant), ['photo_path', 'status']),
        );
    }

    public function test_documents_cannot_be_replaced_unless_the_committee_asked_for_a_correction(): void
    {
        foreach (['pending', 'approved'] as $status) {
            $participant = Participant::factory()->create(['status' => $status]);
            $before = $this->snapshot($participant);

            $this->asOwner($participant)
                ->post(route('registration.documents.update', $participant->registration_code), [
                    'identity_document' => UploadedFile::fake()->image('identitas-lain.jpg'),
                    'photo' => UploadedFile::fake()->image('foto-lain.jpg'),
                ])
                ->assertSessionHasErrors('documents');

            $this->assertSame($before, $this->snapshot($participant));
        }
    }

    public function test_a_participant_session_cannot_upload_to_or_change_another_participants_registration(): void
    {
        $owner = Participant::factory()->create();
        $victim = Participant::factory()->create(['status' => 'rejected']);
        $before = $this->snapshot($victim);

        $this->asOwner($owner)
            ->post(route('registration.payment.store', $victim->registration_code), [
                'payment_proof' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertRedirect(route('status.form', ['kode' => $victim->registration_code]));

        $this->asOwner($owner)
            ->post(route('registration.documents.update', $victim->registration_code), [
                'photo' => UploadedFile::fake()->image('foto.jpg'),
            ])
            ->assertRedirect(route('status.form', ['kode' => $victim->registration_code]));

        $this->assertSame($before, $this->snapshot($victim));
    }

    public function test_a_participant_session_cannot_use_the_committee_endpoints(): void
    {
        $participant = Participant::factory()->withPaymentProof()->create();
        $before = $this->snapshot($participant);
        $code = $participant->registration_code;

        $this->asOwner($participant)->patch("/admin/peserta/{$participant->id}/status", ['status' => 'approved'])->assertRedirect(route('login'));
        $this->asOwner($participant)->patch("/admin/peserta/{$participant->id}/pembayaran", ['payment_status' => 'verified'])->assertRedirect(route('login'));
        $this->asOwner($participant)->delete("/admin/peserta/{$participant->id}")->assertRedirect(route('login'));
        $this->asOwner($participant)->get("/admin/peserta/{$participant->id}")->assertRedirect(route('login'));
        $this->asOwner($participant)->post('/admin/cabang/mhq/kategori', ['name' => 'Gratis', 'fee' => 1000])->assertRedirect(route('login'));

        $this->assertSame($before, $this->snapshot($participant));
        $this->assertSame($code, $participant->fresh()->registration_code);
    }

    private function asOwner(Participant $participant): static
    {
        return $this->withSession([EnsureParticipantAccess::SESSION_KEY => [$participant->registration_code]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Participant $participant): array
    {
        return $this->withoutKeys($participant->fresh()->getAttributes(), ['updated_at']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function withoutKeys(array $attributes, array $keys): array
    {
        return array_diff_key($attributes, array_flip($keys));
    }
}
