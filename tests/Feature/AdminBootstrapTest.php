<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(): string
    {
        $this->app->instance('env', 'production');
        config(['admin.enabled' => true, 'admin.registration_enabled' => false, 'admin.bootstrap_enabled' => true]);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $token = bin2hex(random_bytes(32));
        DB::table('admin_bootstrap_invitations')->insert(['id' => 1, 'email' => 'owner@example.test',
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(30),
            'created_at' => now(), 'updated_at' => now()]);
        return $token;
    }

    private function payload(string $token): array
    {
        return ['name' => 'First Administrator', 'email' => 'owner@example.test',
            'password' => 'StrongPassword12345', 'password_confirmation' => 'StrongPassword12345',
            'provisioning_token' => $token, 'is_admin' => false];
    }

    public function test_valid_invitation_creates_only_one_admin_and_closes_registration(): void
    {
        $token = $this->invitation();
        $this->get('https://localhost/admin/register')->assertOk()->assertSee('Private provisioning token')->assertDontSee($token);
        $previousSession = session()->getId();
        $this->post('https://localhost/admin/register', $this->payload($token))->assertRedirect(route('admin.dashboard'));
        $user = User::sole();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('StrongPassword12345', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSession, session()->getId());
        $this->assertDatabaseHas('admin_bootstrap_invitations', ['id' => 1, 'token_hash' => null]);
        $this->assertNotNull(DB::table('admin_bootstrap_invitations')->value('consumed_at'));
        $this->post('https://localhost/admin/logout')->assertRedirect(route('admin.login'));
        $this->get('https://localhost/admin/register')->assertNotFound();
        $this->post('https://localhost/admin/register', $this->payload($token))->assertNotFound();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_bad_token_and_wrong_email_never_create_accounts_or_flash_token(): void
    {
        $token = $this->invitation();
        $this->post('https://localhost/admin/register', $this->payload(str_repeat('a', 64)))
            ->assertSessionHasErrors('provisioning_token')->assertSessionMissing('_old_input.provisioning_token');
        $this->post('https://localhost/admin/register', array_replace($this->payload($token), ['email' => 'other@example.test']))
            ->assertSessionHasErrors('provisioning_token')->assertSessionMissing('_old_input.provisioning_token');
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(DB::table('admin_bootstrap_invitations')->value('consumed_at'));
    }

    public function test_invalid_data_rolls_back_invitation_consumption(): void
    {
        $token = $this->invitation();
        $this->post('https://localhost/admin/register', array_replace($this->payload($token), ['name' => '', 'password' => 'weak']))
            ->assertSessionHasErrors(['name', 'password'])->assertSessionMissing('_old_input.provisioning_token');
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(DB::table('admin_bootstrap_invitations')->value('consumed_at'));
        $this->post('https://localhost/admin/register', $this->payload($token))->assertRedirect(route('admin.dashboard'));
    }

    public function test_existing_email_is_rejected_without_consuming_invitation(): void
    {
        $token = $this->invitation();
        User::factory()->create(['email' => 'owner@example.test']);
        $this->post('https://localhost/admin/register', $this->payload($token))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertNull(DB::table('admin_bootstrap_invitations')->value('consumed_at'));
    }

    public function test_expired_and_consumed_invitations_and_existing_admin_close_registration(): void
    {
        $token = $this->invitation();
        DB::table('admin_bootstrap_invitations')->update(['expires_at' => now()->subSecond()]);
        $this->get('https://localhost/admin/register')->assertNotFound();
        $this->post('https://localhost/admin/register', $this->payload($token))->assertNotFound();
        DB::table('admin_bootstrap_invitations')->update(['expires_at' => now()->addMinutes(30), 'consumed_at' => now()]);
        $this->get('https://localhost/admin/register')->assertNotFound();
        DB::table('admin_bootstrap_invitations')->update(['consumed_at' => null]);
        User::factory()->create(['is_admin' => true]);
        $this->get('https://localhost/admin/register')->assertNotFound();
    }

    public function test_https_is_required_and_tokens_in_query_strings_are_rejected(): void
    {
        $this->invitation();
        $this->get('http://localhost/admin/register')->assertForbidden();
        // Only a dummy marker is used in the test URL, never an invitation secret.
        $this->get('https://localhost/admin/register?provisioning_token=not-a-secret')->assertStatus(400);
    }

    public function test_bootstrap_is_rate_limited_and_still_requires_csrf(): void
    {
        $token = $this->invitation();
        for ($i = 0; $i < 5; $i++) {
            $this->post('https://localhost/admin/register', $this->payload('wrong'))->assertSessionHasErrors('provisioning_token');
        }
        $this->post('https://localhost/admin/register', $this->payload($token))->assertStatus(429);
        $this->withMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->post('https://localhost/admin/register', $this->payload($token))->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_consumed_bootstrap_cannot_be_reissued_even_if_admin_is_deleted(): void
    {
        $this->invitation();
        DB::table('admin_bootstrap_invitations')->update(['consumed_at' => now(), 'token_hash' => null]);
        $this->artisan('admin:invite-bootstrap', ['email' => 'owner@example.test'])
            ->expectsOutput('Bootstrap is closed or that email is already registered.')->assertFailed();
    }
}
