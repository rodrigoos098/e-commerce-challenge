<?php

namespace App\Services;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    use LogsActivity;

    /**
     * Register a new user and return user with token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('api-token')->plainTextToken;

        $this->logActivity('auth', 'User registered', [
            'registered_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Authenticate a user and return user with token.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->logWarning('auth', 'Authentication failed', [
                'email' => $credentials['email'],
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $this->logActivity('auth', 'User authenticated', [
            'authenticated_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Logout the user by revoking the current token.
     * Uses nullsafe operator to handle stateful (cookie/session) auth where
     * currentAccessToken() may return null.
     */
    public function logout(User $user): void
    {
        $request = request();
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $token = PersonalAccessToken::findToken($bearerToken);

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        } elseif ($request->hasSession()) {
            foreach ((array) config('sanctum.guard', ['web']) as $guard) {
                Auth::guard($guard)->logout();
            }

            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        Auth::forgetGuards();

        $this->logActivity('auth', 'User logged out', [
            'logout_user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Register a new user without creating an API token (for session-based auth).
     *
     * @param  array<string, mixed>  $data
     */
    public function registerUser(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('customer');

        $this->logActivity('auth', 'User registered', [
            'registered_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Update a user's profile information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        $this->logActivity('auth', 'Profile updated', [
            'user_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * Update a user's password.
     */
    public function updatePassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);

        $this->logActivity('auth', 'Password changed', [
            'user_id' => $user->id,
        ]);
    }
}
