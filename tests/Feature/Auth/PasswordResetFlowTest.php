<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $user->assignRole('customer');

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => bcrypt('SenhaAntiga123!'),
        ]);
        $user->assignRole('customer');
        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue(Hash::check('NovaSenha123!', $user->password));
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $user->assignRole('customer');

        $response = $this->from('/reset-password/token-invalido?email='.$user->email)
            ->post('/reset-password', [
                'token' => 'token-invalido',
                'email' => $user->email,
                'password' => 'NovaSenha123!',
                'password_confirmation' => 'NovaSenha123!',
            ]);

        $response->assertRedirect('/reset-password/token-invalido?email='.$user->email);
        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_reset_password_with_expired_token(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $user->assignRole('customer');
        $token = Password::broker()->createToken($user);

        $this->travel(61)->minutes();

        $response = $this->from('/reset-password/'.$token.'?email='.$user->email)
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NovaSenha123!',
                'password_confirmation' => 'NovaSenha123!',
            ]);

        $response->assertRedirect('/reset-password/'.$token.'?email='.$user->email);
        $response->assertSessionHasErrors('email');
    }
}
