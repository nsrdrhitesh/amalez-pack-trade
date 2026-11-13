<?php

namespace Tests\Feature;

use App\Models\DemoActivityLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function enableLocalRegistration(): void
    {
        $this->app->instance('env', 'local');
        config(['admin.registration_enabled' => true]);
        // These requests exercise validation/authentication independently of CSRF.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_public_home_and_login_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Protect the product.');
        $this->get('/admin/login')->assertOk()->assertSee('Good to see you.');
        $this->assertGuest();
    }

    public function test_all_admin_pages_require_authentication(): void
    {
        foreach (['dashboard', 'users', 'content', 'analytics', 'settings'] as $page) {
            $this->get('/admin/'.$page)->assertRedirect('/admin/login');
        }
    }

    public function test_local_registration_creates_a_hashed_administrator_and_rotates_session(): void
    {
        $this->enableLocalRegistration();
        $this->get('/admin/register')->assertOk();
        $previous = session()->getId();
        $this->post('/admin/register', [
            'name' => 'Alex Morgan', 'email' => 'alex@example.test',
            'password' => 'NewPassword1234', 'password_confirmation' => 'NewPassword1234',
        ])->assertRedirect('/admin/dashboard');
        $user = User::whereEmail('alex@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('NewPassword1234', $user->password));
        $this->assertNotSame('NewPassword1234', $user->password);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previous, session()->getId());
    }

    public function test_registration_rejects_duplicate_email_weak_password_and_mismatch(): void
    {
        $this->enableLocalRegistration();
        User::factory()->create(['email' => 'used@example.test']);
        $this->post('/admin/register', [
            'name' => '', 'email' => 'used@example.test',
            'password' => 'weak', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_is_disabled_in_production_even_when_flag_is_enabled(): void
    {
        $this->app->instance('env', 'production');
        config(['admin.registration_enabled' => true]);
        $this->get('/admin/register')->assertNotFound();
        $this->withSession(['_token' => 'production-token'])->post('/admin/register', [
            '_token' => 'production-token', 'name' => 'Intruder', 'email' => 'intruder@example.test',
            'password' => 'NewPassword1234', 'password_confirmation' => 'NewPassword1234',
        ])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_local_registration_also_requires_explicit_flag(): void
    {
        $this->app->instance('env', 'local');
        config(['admin.registration_enabled' => false]);
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_admin_login_remembers_user_and_rotates_session(): void
    {
        $user = $this->administrator();
        $this->get('/admin/login');
        $previous = session()->getId();
        $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1']);
        $response->assertRedirect('/admin/dashboard');
        $response->assertCookie(auth()->guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previous, session()->getId());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_invalid_credentials_are_rejected_and_rate_limited(): void
    {
        $email = 'invalid@example.test';
        RateLimiter::clear('admin-login:'.$email.'|127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/admin/login', ['email' => $email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertMatchesRegularExpression('/^Too many attempts\. Try again in [1-6][0-9]? seconds\.$/', session('errors')->first('email'));
        $this->assertTrue(RateLimiter::tooManyAttempts('admin-login:'.$email.'|127.0.0.1', 5));
        $this->assertGuest();
    }

    public function test_non_admin_cannot_login_or_access_any_admin_page(): void
    {
        $user = User::factory()->create();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user);
        foreach (['dashboard', 'users', 'content', 'analytics', 'settings'] as $page) {
            $this->get('/admin/'.$page)->assertForbidden();
        }
    }

    public function test_admin_can_view_dashboard_and_all_placeholder_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->administrator())->get('/admin/dashboard')->assertOk()->assertSee('Recent activity')->assertSee('Olivia Rhye');
        foreach (['users', 'content', 'analytics', 'settings'] as $page) {
            $this->get('/admin/'.$page)->assertOk()->assertSee('Planned module');
        }
        $this->get('/admin/dashboard?page=2')->assertOk()->assertSee('Andi Lane');
    }

    public function test_dashboard_has_a_useful_empty_state(): void
    {
        $this->actingAs($this->administrator())->get('/admin/dashboard')->assertOk()->assertSee('A fresh page.');
    }

    public function test_authenticated_admin_is_redirected_away_from_auth_forms(): void
    {
        $this->actingAs($this->administrator());
        $this->get('/admin/login')->assertRedirect('/admin/dashboard');
        $this->get('/admin/register')->assertRedirect('/admin/dashboard');
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $this->actingAs($this->administrator());
        $this->get('/admin/dashboard');
        $this->withSession(['private-value' => 'secret']);
        $previousToken = session()->token();
        $previousSession = session()->getId();
        $this->post('/admin/logout')->assertRedirect('/admin/login')->assertSessionMissing('private-value');
        $this->assertGuest();
        $this->assertNotSame($previousToken, session()->token());
        $this->assertNotSame($previousSession, session()->getId());
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_csrf_protects_login_registration_and_logout(): void
    {
        // Laravel bypasses CSRF in the testing environment. Switching environment
        // enables the real middleware for these requests.
        $this->app->instance('env', 'local');
        config(['admin.registration_enabled' => true]);
        $this->post('/admin/login', ['email' => 'a@example.test', 'password' => 'x'])->assertStatus(419);
        $this->post('/admin/register', [])->assertStatus(419);
        $this->actingAs($this->administrator())->post('/admin/logout')->assertStatus(419);
    }

    public function test_admin_role_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'Ordinary User', 'email' => 'user@example.test', 'password' => 'secret', 'is_admin' => true]);
        $user->save();
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_provisioning_command_creates_admin_without_public_registration(): void
    {
        $this->artisan('admin:create')->expectsQuestion('Full name', 'Production Admin')
            ->expectsQuestion('Email address', 'owner@example.test')
            ->expectsQuestion('Password (12+ characters, upper/lowercase and a number)', 'ProductionSecret123')
            ->expectsQuestion('Confirm password', 'ProductionSecret123')
            ->expectsOutput('Administrator created. Sign in at /admin/login.')->assertSuccessful();
        $user = User::whereEmail('owner@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('ProductionSecret123', $user->password));
    }

    public function test_demo_seeder_is_idempotent_and_does_not_create_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(7, DemoActivityLog::count());
        $this->assertDatabaseCount('users', 0);
    }
}
