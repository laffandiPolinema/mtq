<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureParticipantAccess;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_open_their_registration_receipt(): void
    {
        $participant = Participant::factory()->create(['payment_unique_code' => 47]);

        $this->withSession([EnsureParticipantAccess::SESSION_KEY => [$participant->registration_code]])
            ->get(route('registration.receipt', $participant->registration_code))
            ->assertOk()
            ->assertSee('Bukti Pendaftaran')
            ->assertSee($participant->registration_code)
            ->assertSee($participant->full_name)
            ->assertSee('Rp 75.047')
            ->assertSee('144-00-3024646-3');
    }

    public function test_receipt_requires_code_and_whatsapp_verification(): void
    {
        $participant = Participant::factory()->create();

        $this->get(route('registration.receipt', $participant->registration_code))
            ->assertRedirect(route('status.form', ['kode' => $participant->registration_code]));
    }

    public function test_admin_can_open_any_participant_receipt(): void
    {
        $participant = Participant::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.participants.receipt', $participant))
            ->assertOk()
            ->assertSee($participant->registration_code);
    }

    public function test_admin_report_contains_filtered_participants_with_payment_columns(): void
    {
        $paid = Participant::factory()->create([
            'full_name' => 'Ahmad Lunas',
            'phone' => '081234567890',
            'payment_unique_code' => 123,
            'payment_status' => 'verified',
        ]);
        $unpaid = Participant::factory()->create(['full_name' => 'Budi Belum']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.participants.export', ['payment_status' => 'verified']));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Total Transfer', $csv);
        $this->assertStringContainsString($paid->registration_code, $csv);
        $this->assertStringContainsString('"Ahmad Lunas";', $csv);
        $this->assertStringContainsString(';75123;Lunas;', $csv);
        $this->assertStringContainsString('"=""081234567890"""', $csv);
        $this->assertStringNotContainsString($unpaid->registration_code, $csv);
    }

    public function test_report_neutralises_spreadsheet_formulas_in_participant_input(): void
    {
        Participant::factory()->create(['full_name' => '=HYPERLINK("http://contoh.test")']);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('admin.participants.export'))
            ->streamedContent();

        $this->assertStringContainsString(";\"'=HYPERLINK(\"\"http://contoh.test\"\")\";", $csv);
    }

    public function test_guests_cannot_download_the_report(): void
    {
        $this->get(route('admin.participants.export'))->assertRedirect(route('login'));
    }
}
