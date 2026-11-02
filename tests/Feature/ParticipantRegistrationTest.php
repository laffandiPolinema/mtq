<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_registration_page_lists_the_event_branches(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tilawah Al-Qur’an')
            ->assertSee('Khot Indah Al-Qur’an')
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

    public function test_tilawah_category_is_required_and_other_branches_do_not_store_one(): void
    {
        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'category' => '',
        ])->assertSessionHasErrors('category');

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'branch' => 'mhq',
            'category' => 'remaja',
        ])->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'branch' => 'mhq',
            'category' => null,
        ]);
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
