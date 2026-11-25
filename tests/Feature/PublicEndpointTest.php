<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function seedUnits(): void
    {
        Unit::create([
            'nama_unit' => 'Forklift Toyota 2.5T',
            'jenis_unit' => 'forklift',
            'kapasitas' => 2.5,
            'spesifikasi' => 'Diesel 2.5 Ton',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 500_000,
            'status' => 'tersedia',
        ]);

        Unit::create([
            'nama_unit' => 'Forklift Komatsu 5T',
            'jenis_unit' => 'forklift',
            'kapasitas' => 5.0,
            'spesifikasi' => 'Diesel 5 Ton',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 800_000,
            'status' => 'tersedia',
        ]);

        Unit::create([
            'nama_unit' => 'Forklift Perawatan 3T',
            'jenis_unit' => 'forklift',
            'kapasitas' => 3.0,
            'spesifikasi' => 'Sedang servis',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 600_000,
            'status' => 'perawatan',
        ]);

        Unit::create([
            'nama_unit' => 'Crane Tadano 25T',
            'jenis_unit' => 'crane',
            'kapasitas' => 25.0,
            'spesifikasi' => 'Mobile Crane 25 Ton',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 3_500_000,
            'status' => 'tersedia',
        ]);
    }

    public function test_katalog_unit_dapat_diakses_publik_dan_difilter(): void
    {
        $this->seedUnits();

        $response = $this->getJson('/api/units?jenis_unit=forklift');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_detail_unit_mengembalikan_data_atau_404(): void
    {
        $this->seedUnits();
        $unit = Unit::first();

        $this->getJson("/api/units/{$unit->id_unit}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_unit', $unit->id_unit);

        $this->getJson('/api/units/99999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_simulasi_kebutuhan_memberi_rekomendasi_dan_menyimpan_data(): void
    {
        $this->seedUnits();

        $response = $this->postJson('/api/simulasi', [
            'jenis_kebutuhan' => 'Angkat kontainer ringan',
            'kapasitas_beban' => 4,
            'lokasi_proyek' => 'Kawasan Industri Candi, Semarang',
            'periode_penggunaan' => '5 hari',
            'jenis_unit' => 'forklift',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.hasil_rekomendasi')
            ->assertJsonPath('data.hasil_rekomendasi.0.nama_unit', 'Forklift Komatsu 5T');

        $this->assertDatabaseHas('simulasi', [
            'jenis_kebutuhan' => 'Angkat kontainer ringan',
            'id_pelanggan' => null,
        ]);
    }

    public function test_pengajuan_publik_membuat_pelanggan_kode_dan_estimasi_harga(): void
    {
        $this->seedUnits();
        $unit = Unit::where('nama_unit', 'Forklift Toyota 2.5T')->first();

        $mulai = now()->addDays(2)->format('Y-m-d');
        $selesai = now()->addDays(4)->format('Y-m-d'); // 3 hari inklusif

        $response = $this->postJson('/api/pengajuan', [
            'id_unit' => $unit->id_unit,
            'nama' => 'Budi Santoso',
            'nomor_telepon' => '081234567890',
            'email' => 'budi@contoh.co.id',
            'alamat' => 'Jl. Pemuda No. 10, Semarang',
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'lokasi_proyek' => 'Pelabuhan Tanjung Emas',
            'keterangan' => 'Butuh operator juga',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.estimasi_harga', 1_500_000)
            ->assertJsonPath('data.pelanggan.nama', 'Budi Santoso');

        $kode = $response->json('data.kode_pengajuan');
        $this->assertStringStartsWith('GNP-', $kode);
        $this->assertSame(1, Pelanggan::where('nomor_telepon', '081234567890')->count());
    }

    public function test_cek_status_publik_tidak_membocorkan_data_pribadi_pelanggan(): void
    {
        $this->seedUnits();
        $unit = Unit::where('nama_unit', 'Forklift Toyota 2.5T')->first();

        $createRes = $this->postJson('/api/pengajuan', [
            'id_unit' => $unit->id_unit,
            'nama' => 'Siti Rahayu',
            'nomor_telepon' => '089876543210',
            'email' => 'siti@rahasia.co.id',
            'alamat' => 'Jl. Pandanaran No. 99, Semarang',
            'tanggal_mulai' => now()->addDays(5)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(6)->format('Y-m-d'),
            'lokasi_proyek' => 'Semarang Barat',
        ]);

        $kode = $createRes->json('data.kode_pengajuan');

        $statusRes = $this->getJson("/api/pengajuan/status/{$kode}");

        $statusRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kode_pengajuan', $kode)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonMissingPath('data.pelanggan')
            ->assertJsonMissing(['nama' => 'Siti Rahayu'])
            ->assertJsonMissing(['nomor_telepon' => '089876543210'])
            ->assertJsonMissing(['email' => 'siti@rahasia.co.id']);
    }

    public function test_cek_status_kode_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->getJson('/api/pengajuan/status/GNP-TIDAK-ADA')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
