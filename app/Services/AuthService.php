<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Memverifikasi kredensial user dan membuat token Sanctum baru (User::login()).
     *
     * @return array{token: string, token_type: string, user: User}
     *
     * @throws AuthenticationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Email atau password tidak sesuai.');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    /**
     * Menghapus token aktif saat user logout.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
