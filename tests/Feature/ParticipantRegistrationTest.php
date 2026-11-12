<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tanggal di dalam masa pendaftaran (9 November – 24 Desember 2026).
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_the_registration_page_lists_the_event_branches(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tilawah Al-Qur’an')
            ->assertSee('MKQ (Khattil Qur’an)')
            ->assertSee('Murottal')
            ->assertSee('26–27 Desember 2026')
            ->assertSee('07.30 WIB')
            ->assertSee('Malang Creative Center');
    }

    public function test_a_participant_can_register_and_check_their_status(): void
    {
        $this->post(route('registration.store'), $this->validRegistration())
            ->assertRedirect();

        $participant = Participant::firstOrFail();

        $this->assertMatchesRegularExpression('/^MTQ26-[A-Z0-9]{10}$/', $participant->registration_code);
        $this->assertSame('pending', $participant->status);
        $this->assertSame('remaja', $participant->category);

        $this->get(route('registration.success', $participant->registration_code))
            ->assertOk()
            ->assertSee($participant->registration_code)
            ->assertSee($participant->full_name);

        $this->get(route('status.show', $participant->registration_code))
            ->assertOk()
            ->assertSee('Menunggu verifikasi');
    }

    public function test_category_is_required_and_must_belong_to_the_chosen_branch(): void
    {
        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'category' => '',
        ])->assertSessionHasErrors('category');

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'branch' => 'mhq',
            'category' => 'remaja',
        ])->assertSessionHasErrors('category');

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'category' => 'lansia',
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_the_fee_follows_the_chosen_branch_and_category(): void
    {
        $cases = [
            ['tilawah', 'anak-anak', 35000],
            ['tilawah', 'remaja', 50000],
            ['tilawah', 'dewasa', 70000],
            ['mhq', 'anak-anak', 35000],
            ['murottal', 'anak-anak', 35000],
        ];

        foreach ($cases as [$branch, $category, $fee]) {
            $this->post(route('registration.store'), [
                ...$this->validRegistration(),
                'full_name' => "Peserta {$branch} {$category}",
                'branch' => $branch,
                'category' => $category,
            ])->assertRedirect();

            $this->assertDatabaseHas('participants', [
                'full_name' => "Peserta {$branch} {$category}",
                'registration_fee' => $fee,
            ]);
        }
    }

    public function test_mkq_cannot_be_chosen_until_its_fee_is_announced(): void
    {
        $this->get('/')
            ->assertSee('MKQ (Khattil Qur’an) (segera dibuka)')
            ->assertSee('Biaya &amp; pendaftaran segera dibuka', false);

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'branch' => 'khot_indah',
            'category' => 'umum',
        ])->assertSessionHasErrors('branch');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_registration_is_only_accepted_between_9_november_and_24_december(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 23:59:00'));
        $this->get('/')->assertSee('Pendaftaran belum dibuka')->assertDontSee('Kirim pendaftaran');
        $this->post(route('registration.store'), $this->validRegistration())->assertSessionHasErrors('full_name');

        $this->travelTo(Carbon::parse('2026-12-25 00:00:01'));
        $this->get('/')->assertSee('Pendaftaran telah ditutup')->assertDontSee('Kirim pendaftaran');
        $this->post(route('registration.store'), $this->validRegistration())->assertSessionHasErrors('full_name');

        $this->assertDatabaseCount('participants', 0);

        $this->travelTo(Carbon::parse('2026-12-24 23:59:00'));
        $this->post(route('registration.store'), $this->validRegistration())->assertRedirect();

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_tilawah_dewasa_is_limited_to_41_years_on_the_event_day(): void
    {
        // Lomba 26 Desember 2026: lahir 26 Desember 1985 = tepat 41 tahun, sehari sebelumnya = 42 tahun.
        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'category' => 'dewasa',
            'birth_date' => '1984-12-25',
        ])->assertSessionHasErrors('birth_date');

        $this->assertDatabaseCount('participants', 0);

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'category' => 'dewasa',
            'birth_date' => '1985-12-26',
        ])->assertRedirect();

        $this->assertDatabaseHas('participants', ['category' => 'dewasa', 'registration_fee' => 70000]);
    }

    public function test_validation_errors_are_shown_in_a_popup(): void
    {
        $this->get('/')->assertDontSee('data-open-on-load', false);

        $this->from('/')
            ->followingRedirects()
            ->post(route('registration.store'), [...$this->validRegistration(), 'nik' => '123'])
            ->assertSee('data-open-on-load', false)
            ->assertSee('Periksa kembali isian Anda')
            ->assertSee('NIK harus terdiri dari 16 digit angka.');
    }

    public function test_participants_who_registered_can_still_upload_proof_after_registration_closes(): void
    {
        Storage::fake('local');
        $participant = Participant::factory()->create();
        $this->travelTo(Carbon::parse('2026-12-25 09:00:00'));

        $this->withSession([EnsureParticipantAccess::SESSION_KEY => [$participant->registration_code]])
            ->post(route('registration.payment.store', $participant->registration_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])->assertRedirect(route('status.show', $participant->registration_code));

        $this->assertSame('waiting', $participant->refresh()->payment_status);
    }

    public function test_only_an_authenticated_admin_can_manage_participants(): void
    {
        $this->get(route('admin.participants.index'))
            ->assertRedirect(route('login'));

        $admin = User::factory()->create([
            'email' => 'panitia@example.test',
            'password' => Hash::make('kata-sandi-yang-kuat'),
        ]);
        $participant = Participant::create([
            ...$this->validRegistration(),
            'registration_code' => 'MTQ26-TESTCODE01',
            'status' => 'pending',
        ]);

        $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'kata-sandi-yang-kuat',
        ])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($admin);

        $this
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee($participant->registration_code);

        $this->patch(route('admin.participants.status', $participant), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'status' => 'approved',
        ]);
    }

    private function validRegistration(): array
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
        ];
    }
}
