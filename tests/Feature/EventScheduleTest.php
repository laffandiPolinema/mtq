<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Services\EventSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_defaults_from_config_are_shown_until_the_committee_changes_them(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sabtu–Minggu, 26–27 Des 2026')
            ->assertSee('07.30 WIB s.d. selesai')
            ->assertSee('Malang Creative Center (MCC) Lt. 2')
            ->assertSee('Technical meeting (H-5)')
            ->assertSee('Senin, 21 Desember 2026')
            ->assertSee('ambil nomor urut &amp; maqra’', false);
    }

    public function test_changing_the_schedule_updates_every_place_it_is_shown(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.juknis.schedule'), $this->newSchedule())
            ->assertRedirect(route('admin.juknis.edit'))
            ->assertSessionHas('success');

        auth()->logout();

        $landing = $this->get('/')->assertOk();
        $landing->assertSee('Sabtu–Minggu, 2–3 Jan 2027')
            ->assertSee('08.00 WIB s.d. selesai')
            ->assertSee('Aula Balai Kota Lt. 3')
            ->assertSee('Technical meeting (H-7)')
            ->assertSee('Sabtu, 26 Desember 2026')
            ->assertSee('di Masjid Jami’')
            ->assertDontSee('Malang Creative Center')
            ->assertDontSee('Senin, 21 Desember 2026')
            ->assertDontSee('07.30 WIB');

        $participant = Participant::factory()->create();

        $this->withSession(['participant_access' => [$participant->registration_code]])
            ->get(route('registration.receipt', $participant->registration_code))
            ->assertOk()
            ->assertSee('Sabtu–Minggu, 2–3 Januari 2027')
            ->assertSee('Pukul 08.00 WIB s.d. selesai')
            ->assertSee('Aula Balai Kota Lt. 3')
            ->assertSee('(H-7) pada', false)
            ->assertSee('Sabtu, 26 Desember 2026');
    }

    public function test_the_category_age_limit_follows_the_new_first_day_of_the_event(): void
    {
        $this->actingAs(User::factory()->create())->put(route('admin.juknis.schedule'), $this->newSchedule());
        auth()->logout();

        // Berumur 42 tahun pada 1 Januari 2027: di bawah tanggal lama masih 41 tahun, di tanggal baru sudah 42.
        $this->post(route('registration.store'), [
            'full_name' => 'Ahmad Dewasa',
            'nik' => '3573011111111111',
            'birth_date' => '1985-01-01',
            'gender' => 'male',
            'district' => 'Blimbing',
            'address' => 'Jl. Ahmad Yani, Malang',
            'phone' => '081234567890',
            'institution' => 'Universitas Brawijaya',
            'school_address' => 'Jl. Veteran, Malang',
            'branch' => 'tilawah',
            'category' => 'dewasa',
            'identity_document' => UploadedFile::fake()->image('ktp.jpg'),
            'photo' => UploadedFile::fake()->image('pasfoto.jpg'),
        ])->assertSessionHasErrors(['birth_date' => 'Usia maksimal kategori Tilawah Dewasa adalah 41 tahun pada hari lomba (02 Januari 2027). Usia peserta saat itu 42 tahun.']);
    }

    public function test_a_single_day_event_and_invalid_dates_are_handled(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.juknis.edit'))
            ->put(route('admin.juknis.schedule'), [...$this->newSchedule(), 'event_end_date' => '2027-01-01'])
            ->assertSessionHasErrors('event_end_date');

        $this->actingAs($admin)
            ->put(route('admin.juknis.schedule'), [...$this->newSchedule(), 'event_end_date' => ''])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Sabtu, 2 Januari 2027', app(EventSchedule::class)->eventDaysLabel());
    }

    public function test_guests_cannot_change_the_schedule(): void
    {
        $this->put(route('admin.juknis.schedule'), $this->newSchedule())->assertRedirect(route('login'));

        $this->get('/')->assertSee('Senin, 21 Desember 2026')->assertDontSee('Aula Balai Kota');
    }

    /**
     * @return array<string, string>
     */
    private function newSchedule(): array
    {
        return [
            'technical_meeting_date' => '2026-12-26',
            'technical_meeting_note' => 'di Masjid Jami’',
            'event_start_date' => '2027-01-02',
            'event_end_date' => '2027-01-03',
            'event_time' => '08.00 WIB s.d. selesai',
            'event_place' => 'Aula Balai Kota Lt. 3',
        ];
    }
}
