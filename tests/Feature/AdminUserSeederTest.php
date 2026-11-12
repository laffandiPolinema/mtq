<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_log_in_and_reseeding_updates_the_same_account(): void
    {
        config([
            'mtq.admin.email' => 'Panitia@Example.test',
            'mtq.admin.password' => 'kata-sandi-panitia-lama',
        ]);
        $this->seed(AdminUserSeeder::class);

        config(['mtq.admin.password' => 'kata-sandi-panitia-baru']);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::count());

        $this->post(route('admin.login'), [
            'email' => 'panitia@example.test',
            'password' => 'kata-sandi-panitia-baru',
        ])->assertRedirect(route('admin.participants.index'));

        $this->assertAuthenticated();
    }

    public function test_default_admin_account_is_seeded_without_environment_overrides(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post(route('admin.login'), [
            'email' => 'adminmtq2026@gmail.com',
            'password' => 'punyaadminmtq2026',
        ])->assertRedirect(route('admin.participants.index'));
    }

    public function test_no_admin_is_created_when_credentials_are_emptied_or_too_short(): void
    {
        config(['mtq.admin.email' => null, 'mtq.admin.password' => null]);
        $this->seed(AdminUserSeeder::class);

        config(['mtq.admin.email' => 'panitia@example.test', 'mtq.admin.password' => 'pendek']);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::count());
    }
}
