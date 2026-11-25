<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\Pelanggan;
use App\Models\Pengajuan;
use App\Models\Rekanan;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOwnerEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->owner()->create();
    }

    private function buatUnit(array $overrides = []): Unit
    {
        return Unit::create(array_merge([
            'nama_unit' => 'Forklift Toyota 3T',
            'jenis_unit' => 'forklift',
            'kapasitas' => 3.0,
            'spesifikasi' => 'Diesel 3 Ton',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 600_000,
            'status' => 'tersedia',
        ], $overrides));
    }

    private function buatPengajuan(Unit $unit, string $mulai = '2026-12-10', string $selesai = '2026-12-12'): Pengajuan
    {
        $pelanggan = Pelanggan::create([
            'nama' => 'PT Maju Jaya',
            'nomor_telepon' => '08111222333',
            'email' => 'maju@contoh.co.id',
            'alamat' => 'Semarang',
        ]);

        return Pengajuan::create([
            'kode_pengajuan' => 'GNP-TEST-'.uniqid(),
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_unit' => $unit->id_unit,
            'tanggal_pengajuan' => now(),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'lokasi_proyek' => 'Kawasan Industri Terboyo',
            'estimasi_harga' => 1_800_000,
            'status' => 'menunggu',
        ]);
    }

    public function test_pembatasan_akses_401_dan_403(): void
    {
        // Belum login -> 401
        $this->getJson('/api/pengajuan')->assertStatus(401);
        $this->postJson('/api/units', [])->assertStatus(401);

        // Owner mencoba endpoint Admin -> 403
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/units', [])->assertStatus(403);
        $this->getJson('/api/pelanggan')->assertStatus(403);
        $this->getJson('/api/rekanan')->assertStatus(403);
        $this->getJson('/api/jadwal')->assertStatus(403);

        // Admin mencoba endpoint Owner -> 403
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/pengajuan/1/pemeriksaan')->assertStatus(403);
        $this->postJson('/api/pengajuan/1/approval', [])->assertStatus(403);
    }

    public function test_admin_crud_unit_dan_proteksi_hapus_409(): void
    {
        Sanctum::actingAs($this->admin);

        // Create
        $createRes = $this->postJson('/api/units', [
            'nama_unit' => 'Crane Kato 25T',
            'jenis_unit' => 'crane',
            'kapasitas' => 25,
            'spesifikasi' => 'Mobile Crane 25T',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 3_000_000,
        ]);

        $createRes->assertStatus(201)->assertJsonPath('data.nama_unit', 'Crane Kato 25T');
        $idUnit = $createRes->json('data.id_unit');

        // Update
        $this->putJson("/api/units/{$idUnit}", ['harga_sewa' => 3_250_000])
            ->assertOk()
            ->assertJsonPath('data.harga_sewa', 3_250_000);

        // Delete saat masih kosong -> berhasil
        $this->deleteJson("/api/units/{$idUnit}")->assertOk();

        // Delete saat masih punya pengajuan -> 409
        $unitTerpakai = $this->buatUnit();
        $this->buatPengajuan($unitTerpakai);

        $this->deleteJson("/api/units/{$unitTerpakai->id_unit}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_admin_crud_pelanggan_rekanan_dan_jadwal(): void
    {
        Sanctum::actingAs($this->admin);

        // Pelanggan
        $pelRes = $this->postJson('/api/pelanggan', [
            'nama' => 'CV Sinar Abadi',
            'nomor_telepon' => '081299988877',
            'alamat' => 'Semarang',
        ])->assertStatus(201);
        $idPelanggan = $pelRes->json('data.id_pelanggan');

        $this->getJson('/api/pelanggan')->assertOk()->assertJsonPath('data.meta.total', 1);
        $this->putJson("/api/pelanggan/{$idPelanggan}", ['nama' => 'CV Sinar Abadi Jaya'])->assertOk();
        $this->deleteJson("/api/pelanggan/{$idPelanggan}")->assertOk();

        // Rekanan
        $rekRes = $this->postJson('/api/rekanan', [
            'nama_rekanan' => 'PT Mitra Alat',
            'kontak' => '024-7654321',
        ])->assertStatus(201);
        $idRekanan = $rekRes->json('data.id_rekanan');

        $this->getJson('/api/rekanan')->assertOk()->assertJsonPath('data.meta.total', 1);
        $this->deleteJson("/api/rekanan/{$idRekanan}")->assertOk();

        // Jadwal + bentrok -> 422
        $unit = $this->buatUnit();
        $this->postJson('/api/jadwal', [
            'id_unit' => $unit->id_unit,
            'tanggal_mulai' => '2026-12-10',
            'tanggal_selesai' => '2026-12-12',
        ])->assertStatus(201);

        $this->postJson('/api/jadwal', [
            'id_unit' => $unit->id_unit,
            'tanggal_mulai' => '2026-12-12',
            'tanggal_selesai' => '2026-12-15',
        ])->assertStatus(422);
    }

    public function test_admin_dapat_melihat_dan_memperbarui_status_pengajuan(): void
    {
        $unit = $this->buatUnit();
        $pengajuan = $this->buatPengajuan($unit);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/pengajuan')->assertOk()->assertJsonPath('data.meta.total', 1);
        $this->getJson("/api/pengajuan/{$pengajuan->id_pengajuan}")->assertOk();

        // menunggu -> diproses (valid)
        $this->patchJson("/api/pengajuan/{$pengajuan->id_pengajuan}/status", [
            'status' => 'diproses',
        ])->assertOk()->assertJsonPath('data.status', 'diproses');

        // admin tidak boleh mengubah ke disetujui (422 dari validasi request)
        $this->patchJson("/api/pengajuan/{$pengajuan->id_pengajuan}/status", [
            'status' => 'disetujui',
        ])->assertStatus(422);
    }

    public function test_owner_pemeriksaan_dan_approval_membuat_jadwal_dalam_satu_transaksi(): void
    {
        $unit = $this->buatUnit();
        $pengajuan = $this->buatPengajuan($unit, '2026-12-20', '2026-12-22');

        Sanctum::actingAs($this->owner);

        // Pemeriksaan
        $this->getJson("/api/pengajuan/{$pengajuan->id_pengajuan}/pemeriksaan")
            ->assertOk()
            ->assertJsonPath('data.ketersediaan', true)
            ->assertJsonPath('data.jumlah_hari', 3)
            ->assertJsonPath('data.estimasi_harga', 1_800_000);

        // Approval setuju
        $this->postJson("/api/pengajuan/{$pengajuan->id_pengajuan}/approval", [
            'keputusan' => 'disetujui',
            'catatan' => 'Unit siap kirim',
        ])->assertStatus(201)->assertJsonPath('data.keputusan', 'disetujui');

        $this->assertDatabaseHas('pengajuan', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status' => 'disetujui',
        ]);
        $this->assertDatabaseHas('jadwal', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'id_unit' => $unit->id_unit,
            'status' => 'terjadwal',
        ]);

        // Approval kedua kalinya -> 409 Conflict
        $this->postJson("/api/pengajuan/{$pengajuan->id_pengajuan}/approval", [
            'keputusan' => 'ditolak',
        ])->assertStatus(409);
    }

    public function test_owner_approval_gagal_422_dan_rollback_jika_jadwal_bentrok(): void
    {
        $unit = $this->buatUnit();
        Jadwal::create([
            'id_unit' => $unit->id_unit,
            'tanggal_mulai' => '2026-12-20',
            'tanggal_selesai' => '2026-12-22',
            'status' => 'terjadwal',
        ]);

        $pengajuan = $this->buatPengajuan($unit, '2026-12-21', '2026-12-23');

        Sanctum::actingAs($this->owner);

        $this->postJson("/api/pengajuan/{$pengajuan->id_pengajuan}/approval", [
            'keputusan' => 'disetujui',
        ])->assertStatus(422);

        // Pastikan transaksi di-rollback: status tetap menunggu & tidak ada approval
        $this->assertDatabaseHas('pengajuan', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseMissing('approval', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
        ]);
    }
}
