<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_register_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'cliente@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_user_can_verify_email_from_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_is_redirected_from_checkout_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/customer/checkout');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_is_redirected_to_verification_notice_after_login(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'cliente@example.com',
            'password' => bcrypt('Password123!'),
        ]);
        $user->assignRole('customer');

        $response = $this->post('/login', [
            'email' => 'cliente@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('verification.notice'));
    }
}
