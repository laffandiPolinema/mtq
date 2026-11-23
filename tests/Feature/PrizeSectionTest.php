<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_prizes_are_shown_on_the_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="hadiah"', false)
            ->assertSeeInOrder(['Dapatkan hadiah', 'Tropi', 'Uang pembinaan', 'Sertifikat', 'Souvenir dari sponsor']);
    }

    public function test_the_committee_can_change_the_prizes_and_rows_without_a_name_are_dropped(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.prizes.edit'))->assertOk()->assertSee('Simpan hadiah');

        $this->actingAs($admin)
            ->put(route('admin.prizes.update'), [
                'heading' => 'Raih juara, bawa pulang hadiah',
                'intro' => 'Total hadiah puluhan juta rupiah.',
                'items' => [
                    ['icon' => 'medal', 'title' => 'Medali emas', 'description' => 'Untuk juara 1'],
                    ['icon' => 'trophy', 'title' => '', 'description' => 'Baris kosong'],
                    ['icon' => 'gift', 'title' => '<b>Umrah</b>', 'description' => ''],
                ],
            ])
            ->assertRedirect(route('admin.prizes.edit'))
            ->assertSessionHas('success');

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Raih juara, bawa pulang hadiah', 'Total hadiah puluhan juta rupiah.', 'Medali emas', 'Untuk juara 1', '&lt;b&gt;Umrah&lt;/b&gt;'], false)
            ->assertDontSee('Baris kosong')
            ->assertDontSee('Uang pembinaan');
    }

    public function test_the_section_is_hidden_when_every_prize_is_removed(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.prizes.update'), ['heading' => 'Hadiah', 'items' => [['icon' => 'trophy', 'title' => '']]])
            ->assertSessionDoesntHaveErrors();

        auth()->logout();

        $this->get('/')->assertOk()->assertDontSee('id="hadiah"', false);
    }

    public function test_heading_is_required_and_icon_must_be_known(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('admin.prizes.edit'))
            ->put(route('admin.prizes.update'), ['heading' => '', 'items' => [['icon' => 'rocket', 'title' => 'Tropi']]])
            ->assertSessionHasErrors([
                'heading' => 'Judul bagian hadiah wajib diisi.',
                'items.0.icon' => 'Ikon hadiah tidak valid.',
            ]);

        $this->get('/')->assertSee('Uang pembinaan');
    }

    public function test_guests_cannot_change_the_prizes(): void
    {
        $this->get(route('admin.prizes.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.prizes.update'), ['heading' => 'Diretas', 'items' => []])->assertRedirect(route('login'));

        $this->get('/')->assertSee('Uang pembinaan')->assertDontSee('Diretas');
    }
}
