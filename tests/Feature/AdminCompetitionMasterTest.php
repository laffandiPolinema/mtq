<?php

namespace Tests\Feature;

use App\Models\CompetitionBranch;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCompetitionMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_guests_cannot_manage_competition_master_data(): void
    {
        $category = CompetitionCategory::first();

        $this->get(route('admin.branches.index'))->assertRedirect(route('login'));
        $this->post(route('admin.branches.store'), ['name' => 'Cabang Liar', 'sort_order' => 1])->assertRedirect(route('login'));
        $this->put(route('admin.branches.categories.update', $category), ['name' => 'X', 'fee' => 1000, 'sort_order' => 1])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('competition_branches', ['name' => 'Cabang Liar']);
    }

    public function test_committee_can_add_a_branch_and_category_that_participants_can_register_for(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.branches.store'), ['name' => 'Pildacil', 'sort_order' => 9, 'is_open' => '1'])
            ->assertRedirect();

        $branch = CompetitionBranch::where('name', 'Pildacil')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.branches.categories.store', $branch), [
                'name' => 'Anak',
                'description' => 'SD/MI',
                'fee' => 40000,
                'sort_order' => 1,
                'is_open' => '1',
            ])->assertRedirect(route('admin.branches.index'));

        $category = $branch->categories()->firstOrFail();

        $this->get('/')->assertSee('Pildacil')->assertSee('Rp 40.000');

        $this->post(route('registration.store'), [
            ...$this->validRegistration(),
            'branch' => $branch->code,
            'category' => $category->code,
        ])->assertRedirect();

        $this->assertDatabaseHas('participants', ['branch' => $branch->code, 'registration_fee' => 40000]);
    }

    public function test_changing_a_fee_only_affects_new_registrations(): void
    {
        $admin = User::factory()->create();
        $existing = Participant::factory()->create(['branch' => 'mhq', 'category' => 'anak-anak', 'registration_fee' => 35000]);
        $category = CompetitionCategory::whereHas('branch', fn ($query) => $query->where('code', 'mhq'))->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.branches.categories.update', $category), [
                'name' => 'Juz 30',
                'description' => 'SD/MI',
                'fee' => 45000,
                'sort_order' => 1,
                'is_open' => '1',
            ])->assertRedirect(route('admin.branches.index'));

        $this->post(route('registration.store'), [...$this->validRegistration(), 'branch' => 'mhq', 'category' => 'anak-anak'])
            ->assertRedirect();

        $this->assertSame(35000, $existing->refresh()->registration_fee);
        $this->assertDatabaseHas('participants', ['full_name' => 'Peserta Baru', 'registration_fee' => 45000]);
    }

    public function test_fee_must_be_a_multiple_of_1000_so_the_unique_code_stays_readable(): void
    {
        $category = CompetitionCategory::firstOrFail();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.branches.categories.update', $category), [
                'name' => $category->name,
                'fee' => 35500,
                'sort_order' => 1,
            ])->assertSessionHasErrors('fee');

        $this->assertNotSame(35500, $category->refresh()->fee);
    }

    public function test_closing_a_branch_or_category_removes_it_from_the_registration_form(): void
    {
        $admin = User::factory()->create();
        $mkq = CompetitionBranch::where('code', 'khot_indah')->firstOrFail();
        $remaja = CompetitionCategory::where('code', 'remaja')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.branches.update', $mkq), ['name' => $mkq->name, 'sort_order' => 3]);
        $this->actingAs($admin)->put(route('admin.branches.categories.update', $remaja), [
            'name' => $remaja->name,
            'fee' => $remaja->fee,
            'sort_order' => 2,
        ]);

        $this->get('/')->assertSee($mkq->name.' (ditutup)');

        $this->post(route('registration.store'), [...$this->validRegistration(), 'branch' => 'khot_indah', 'category' => 'umum'])
            ->assertSessionHasErrors('branch');
        $this->post(route('registration.store'), [...$this->validRegistration(), 'branch' => 'tilawah', 'category' => 'remaja'])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_branches_and_categories_with_participants_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        Participant::factory()->create(['branch' => 'mhq', 'category' => 'anak-anak']);
        $mhq = CompetitionBranch::where('code', 'mhq')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.branches.destroy', $mhq))->assertSessionHasErrors('branch');
        $this->actingAs($admin)->delete(route('admin.branches.categories.destroy', $mhq->categories()->first()))->assertSessionHasErrors('category');

        $this->assertModelExists($mhq);

        $empty = CompetitionCategory::factory()->create();
        $this->actingAs($admin)->delete(route('admin.branches.categories.destroy', $empty))->assertRedirect(route('admin.branches.index'));
        $this->assertModelMissing($empty);
    }

    public function test_renaming_a_branch_keeps_existing_participants_linked(): void
    {
        $participant = Participant::factory()->create(['branch' => 'mhq', 'category' => 'anak-anak']);
        $mhq = CompetitionBranch::where('code', 'mhq')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.branches.update', $mhq), ['name' => 'Musabaqah Hifdzil Qur’an', 'sort_order' => 2, 'is_open' => '1']);

        $this->assertSame('mhq', $mhq->refresh()->code);
        $this->assertSame('Musabaqah Hifdzil Qur’an', $participant->refresh()->branchLabel());
    }

    /**
     * @return array<string, mixed>
     */
    private function validRegistration(): array
    {
        return [
            'full_name' => 'Peserta Baru',
            'nik' => fake()->unique()->numerify('3573############'),
            'birth_date' => '2012-05-10',
            'gender' => 'female',
            'district' => 'Blimbing',
            'address' => 'Jl. Ahmad Yani, Malang',
            'phone' => '081234567890',
            'institution' => 'SD Negeri 1 Malang',
            'school_address' => 'Jl. Pahlawan, Malang',
            'branch' => 'tilawah',
            'category' => 'anak-anak',
            'identity_document' => UploadedFile::fake()->image('kk.jpg', 800, 500),
            'photo' => UploadedFile::fake()->image('pasfoto.jpg', 300, 400),
        ];
    }
}
