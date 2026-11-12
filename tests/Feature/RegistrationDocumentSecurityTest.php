<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_nik_and_documents_are_stored_encrypted(): void
    {
        $this->post(route('registration.store'), $this->validRegistration(['nik' => '3573011234567890']))
            ->assertRedirect();

        $participant = Participant::firstOrFail();
        $rawNik = DB::table('participants')->value('nik');

        $this->assertSame('3573011234567890', $participant->nik);
        $this->assertStringNotContainsString('3573011234567890', $rawNik);
        $this->assertSame(Participant::nikHash('3573011234567890'), $participant->nik_hash);

        foreach (['identity_document_path', 'photo_path'] as $column) {
            $stored = Storage::disk('local')->get($participant->{$column});

            $this->assertStringNotContainsString("\xFF\xD8\xFF", $stored, "{$column} tersimpan tanpa enkripsi.");
            $this->assertStringStartsWith("\xFF\xD8\xFF", Crypt::decryptString($stored));
        }
    }

    public function test_one_nik_can_only_join_one_branch_and_leaves_no_orphan_files(): void
    {
        $this->post(route('registration.store'), $this->validRegistration(['nik' => '3573011234567890']))->assertRedirect();
        $filesAfterFirst = Storage::disk('local')->allFiles();

        $this->post(route('registration.store'), $this->validRegistration([
            'nik' => '3573011234567890',
            'branch' => 'mhq',
            'category' => 'anak-anak',
        ]))->assertSessionHasErrors('nik');

        $this->assertDatabaseCount('participants', 1);
        $this->assertSame($filesAfterFirst, Storage::disk('local')->allFiles());
    }

    public function test_required_documents_must_be_present_and_of_an_allowed_type(): void
    {
        $this->post(route('registration.store'), $this->validRegistration(['identity_document' => null, 'photo' => null]))
            ->assertSessionHasErrors(['identity_document', 'photo']);

        $this->post(route('registration.store'), $this->validRegistration([
            'identity_document' => UploadedFile::fake()->create('ktp.exe', 10),
            'photo' => UploadedFile::fake()->create('pasfoto.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['identity_document', 'photo']);

        // File HTML yang menyamar sebagai .jpg gagal saat gambar ditulis ulang, meskipun lolos pemeriksaan jenis file.
        $this->post(route('registration.store'), $this->validRegistration([
            'identity_document' => UploadedFile::fake()->createWithContent('ktp.jpg', '<html><script>alert(1)</script></html>'),
        ]))->assertSessionHasErrors('identity_document');

        // Unggahan sungguhan: jenis file dibaca dari isinya, sehingga HTML bernama .jpg sudah ditolak saat validasi.
        $path = tempnam(sys_get_temp_dir(), 'mtq');
        file_put_contents($path, '<html><script>alert(1)</script></html>');

        $this->post(route('registration.store'), $this->validRegistration([
            'identity_document' => new UploadedFile($path, 'ktp.jpg', null, null, true),
        ]))->assertSessionHasErrors(['identity_document' => 'Identitas diri harus berupa gambar (JPG, PNG, WEBP) atau PDF.']);

        $this->assertDatabaseCount('participants', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_pass_photo_must_be_portrait_3x4(): void
    {
        $this->post(route('registration.store'), $this->validRegistration([
            'photo' => UploadedFile::fake()->image('pemandangan.jpg', 800, 450),
        ]))->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('participants', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_images_are_rewritten_so_hidden_payloads_are_removed(): void
    {
        $image = UploadedFile::fake()->image('ktp.jpg', 800, 500);
        $polyglot = UploadedFile::fake()->createWithContent('ktp.jpg', file_get_contents($image->getRealPath()).'<?php system($_GET["c"]); ?>');

        $this->post(route('registration.store'), $this->validRegistration(['identity_document' => $polyglot]))->assertRedirect();

        $stored = Crypt::decryptString(Storage::disk('local')->get(Participant::firstOrFail()->identity_document_path));

        $this->assertStringStartsWith("\xFF\xD8\xFF", $stored);
        $this->assertStringNotContainsString('<?php', $stored);
    }

    public function test_only_logged_in_committee_can_open_documents_with_safe_headers(): void
    {
        $this->post(route('registration.store'), $this->validRegistration())->assertRedirect();
        $participant = Participant::firstOrFail();
        $url = route('admin.participants.document', [$participant, 'pasfoto']);

        // Peserta sendiri (sesi dengan akses kode) pun tidak dapat membuka route panitia.
        $this->get($url)->assertRedirect(route('login'));

        $response = $this->actingAs(User::factory()->create())->get($url);

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
        $this->assertStringStartsWith("\xFF\xD8\xFF", $response->getContent());
    }

    public function test_a_payment_proof_already_used_by_another_participant_is_rejected(): void
    {
        $proof = UploadedFile::fake()->image('transfer.jpg', 600, 900);
        $first = Participant::factory()->create();
        $second = Participant::factory()->create();

        $this->withSession([EnsureParticipantAccess::SESSION_KEY => [$first->registration_code, $second->registration_code]]);

        $this->post(route('registration.payment.store', $first->registration_code), ['payment_proof' => $proof])->assertRedirect();
        $this->post(route('registration.payment.store', $second->registration_code), ['payment_proof' => $proof])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame('waiting', $first->refresh()->payment_status);
        $this->assertSame('unpaid', $second->refresh()->payment_status);
    }

    public function test_verifying_a_payment_records_which_committee_member_did_it(): void
    {
        $admin = User::factory()->create(['name' => 'Bendahara MTQ']);
        $participant = Participant::factory()->withPaymentProof()->create();

        $this->actingAs($admin)
            ->patch(route('admin.participants.payment', $participant), ['payment_status' => 'verified'])
            ->assertRedirect();

        $this->assertSame($admin->id, $participant->refresh()->payment_verified_by);

        $this->actingAs($admin)
            ->get(route('admin.participants.show', $participant))
            ->assertSee('Ditandai lunas oleh')
            ->assertSee('Bendahara MTQ');
    }

    public function test_participant_can_replace_documents_only_after_committee_requests_a_correction(): void
    {
        $admin = User::factory()->create();
        $this->post(route('registration.store'), $this->validRegistration())->assertRedirect();
        $participant = Participant::firstOrFail();
        $oldPhoto = $participant->photo_path;

        $this->post(route('registration.documents.update', $participant->registration_code), [
            'photo' => UploadedFile::fake()->image('baru.jpg', 300, 400),
        ])->assertSessionHasErrors('documents');

        $this->actingAs($admin)
            ->patch(route('admin.participants.status', $participant), ['status' => 'rejected'])
            ->assertSessionHasErrors('status_note');

        $this->actingAs($admin)
            ->patch(route('admin.participants.status', $participant), ['status' => 'rejected', 'status_note' => 'Pasfoto buram'])
            ->assertRedirect();

        $this->get(route('status.show', $participant->registration_code))->assertSee('Pasfoto buram');

        $this->post(route('registration.documents.update', $participant->registration_code), [
            'photo' => UploadedFile::fake()->image('baru.jpg', 300, 400),
        ])->assertRedirect(route('status.show', $participant->registration_code));

        $participant->refresh();

        $this->assertSame('pending', $participant->status);
        $this->assertNotSame($oldPhoto, $participant->photo_path);
        Storage::disk('local')->assertMissing($oldPhoto);
        Storage::disk('local')->assertExists($participant->photo_path);
    }

    public function test_admin_can_find_a_participant_by_full_nik(): void
    {
        $match = Participant::factory()->create(['nik' => '3573019876543210']);
        $other = Participant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.participants.index', ['search' => '3573019876543210']))
            ->assertSee($match->registration_code)
            ->assertDontSee($other->registration_code);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRegistration(array $overrides = []): array
    {
        return [
            'full_name' => 'Siti Aminah',
            'nik' => fake()->unique()->numerify('3573############'),
            'birth_date' => '2008-05-10',
            'gender' => 'female',
            'district' => 'Blimbing',
            'address' => 'Jl. Ahmad Yani, Malang',
            'phone' => '081234567890',
            'institution' => 'SMP Negeri 5 Malang',
            'school_address' => 'Jl. Pahlawan Trip, Malang',
            'branch' => 'tilawah',
            'category' => 'remaja',
            'identity_document' => UploadedFile::fake()->image('kartu-pelajar.jpg', 800, 500),
            'photo' => UploadedFile::fake()->image('pasfoto.jpg', 300, 400),
            ...$overrides,
        ];
    }
}
