<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorsAndRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_cors_mengizinkan_origin_terdaftar_dan_menolak_origin_asing(): void
    {
        // Origin terdaftar di FRONTEND_URL (.env)
        $allowed = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/units');

        $allowed->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

        // Origin asing tidak boleh mendapat header Access-Control-Allow-Origin
        $disallowed = $this->withHeaders([
            'Origin' => 'http://evil.example.com',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/units');

        $this->assertFalse($disallowed->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_rate_limit_login_memblokir_percobaan_ke_6_dengan_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'salah@gnp.co.id',
                'password' => 'salah123',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'salah@gnp.co.id',
            'password' => 'salah123',
        ])->assertStatus(429);
    }

    public function test_rate_limit_simulasi_memblokir_request_ke_11_dengan_429(): void
    {
        $payload = [
            'jenis_kebutuhan' => 'Angkat barang',
            'kapasitas_beban' => 2.5,
            'lokasi_proyek' => 'Semarang',
            'periode_penggunaan' => '3 hari',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/simulasi', $payload)->assertStatus(201);
        }

        $this->postJson('/api/simulasi', $payload)->assertStatus(429);
    }
}
