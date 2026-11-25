<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_owner_dapat_login_dan_mendapat_token(): void
    {
        $admin = User::factory()->create([
            'nama' => 'Admin GNP',
            'email' => 'admin@gnp.co.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@gnp.co.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id_user', 'nama', 'email', 'role'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                    'user' => [
                        'id_user' => $admin->id_user,
                        'nama' => 'Admin GNP',
                        'email' => 'admin@gnp.co.id',
                        'role' => 'admin',
                    ],
                ],
            ])
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_login_gagal_mengembalikan_401_dengan_format_seragam(): void
    {
        User::factory()->create([
            'email' => 'admin@gnp.co.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@gnp.co.id',
            'password' => 'salah',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_logout_tanpa_token_mengembalikan_401(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_logout_menghapus_token_aktif(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@gnp.co.id',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Logout berhasil.',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_middleware_role_menolak_peran_tidak_sesuai_dengan_403(): void
    {
        Route::middleware(['auth:sanctum', 'role:owner'])->get('/api/_test-owner-only', fn () => response()->json([
            'success' => true,
            'data' => 'ok',
        ]));

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/_test-owner-only')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['success', 'message', 'errors']);

        $owner = User::factory()->owner()->create();
        Sanctum::actingAs($owner);

        $this->getJson('/api/_test-owner-only')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => 'ok',
            ]);
    }
}
