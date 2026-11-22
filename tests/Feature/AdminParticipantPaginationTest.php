<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipantPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_links_are_relative_keep_filters_and_open_the_next_page(): void
    {
        Participant::factory()->count(20)->create(['district' => 'Klojen']);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.participants.index', ['district' => 'Klojen']))
            ->assertOk()
            ->assertSee('Menampilkan')
            ->assertSee('href="?district=Klojen&amp;page=2"', false)
            ->assertDontSee('pagination.previous');

        $secondPage = $this->actingAs($admin)
            ->get('/admin/peserta?page=2')
            ->assertOk()
            ->assertSee('<span class="pager-link is-current" aria-current="page">2</span>', false);

        $firstPageCodes = Participant::latest()->latest('id')->take(15)->pluck('registration_code');
        $lastCode = Participant::oldest()->oldest('id')->value('registration_code');

        $secondPage->assertSee($lastCode);
        $firstPageCodes->each(fn (string $code) => $secondPage->assertDontSee($code));
    }
}
