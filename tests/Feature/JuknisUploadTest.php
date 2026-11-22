<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JuknisDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JuknisUploadTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PDF = "%PDF-1.4\n% juknis revisi 2\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
    }

    public function test_the_bundled_juknis_is_served_until_the_committee_uploads_one(): void
    {
        $response = $this->get(route('juknis.show'))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertSame(file_get_contents(JuknisDocument::bundledPath()), $response->getFile()->getContent());
    }

    public function test_committee_can_replace_the_juknis_and_the_landing_page_uses_the_new_file(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.juknis.update'), [
                'juknis' => UploadedFile::fake()->createWithContent('Juknis revisi.pdf', self::NEW_PDF),
            ])
            ->assertRedirect(route('admin.juknis.edit'))
            ->assertSessionHas('success');

        Storage::disk('local')->assertExists(JuknisDocument::PATH);
        $this->assertSame(self::NEW_PDF, $this->get(route('juknis.show'))->getFile()->getContent());

        $download = $this->get(route('juknis.show', ['unduh' => 1]));
        $this->assertStringStartsWith('attachment;', $download->headers->get('Content-Disposition'));
        $this->assertSame(self::NEW_PDF, $download->getFile()->getContent());

        $this->get('/')->assertOk()->assertSee(app(JuknisDocument::class)->url(), false);
    }

    public function test_only_pdf_files_up_to_8_mb_are_accepted(): void
    {
        $admin = User::factory()->create();

        $notReallyPdf = UploadedFile::fake()->createWithContent('juknis.pdf', '<html><script>alert(1)</script></html>');
        $image = UploadedFile::fake()->image('juknis.jpg');
        $tooLarge = UploadedFile::fake()->create('juknis.pdf', 9 * 1024, 'application/pdf');

        foreach ([$notReallyPdf, $image, $tooLarge] as $file) {
            $this->actingAs($admin)
                ->from(route('admin.juknis.edit'))
                ->post(route('admin.juknis.update'), ['juknis' => $file])
                ->assertSessionHasErrors('juknis');
        }

        Storage::disk('local')->assertMissing(JuknisDocument::PATH);
    }

    public function test_committee_can_go_back_to_the_bundled_juknis(): void
    {
        Storage::disk('local')->put(JuknisDocument::PATH, self::NEW_PDF);

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.juknis.destroy'))
            ->assertRedirect(route('admin.juknis.edit'));

        Storage::disk('local')->assertMissing(JuknisDocument::PATH);
        $this->assertSame(file_get_contents(JuknisDocument::bundledPath()), $this->get(route('juknis.show'))->getFile()->getContent());
    }

    public function test_guests_cannot_manage_the_juknis(): void
    {
        $this->get(route('admin.juknis.edit'))->assertRedirect(route('login'));
        $this->post(route('admin.juknis.update'), [
            'juknis' => UploadedFile::fake()->createWithContent('juknis.pdf', self::NEW_PDF),
        ])->assertRedirect(route('login'));
        $this->delete(route('admin.juknis.destroy'))->assertRedirect(route('login'));

        Storage::disk('local')->assertMissing(JuknisDocument::PATH);
    }

    public function test_admin_page_shows_which_juknis_is_active(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.juknis.edit'))
            ->assertOk()
            ->assertSee('Juknis bawaan aplikasi')
            ->assertDontSee('Hapus unggahan');

        Storage::disk('local')->put(JuknisDocument::PATH, self::NEW_PDF);

        $this->actingAs($admin)->get(route('admin.juknis.edit'))
            ->assertOk()
            ->assertSee('Juknis unggahan panitia')
            ->assertSee('Hapus unggahan');
    }
}
