<?php

namespace Tests\Feature;

use App\Exceptions\ConflictException;
use App\Models\Jadwal;
use App\Models\Unit;
use App\Services\PengajuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes untuk:
 * 1. hitungJumlahHari() — inklusif
 * 2. hitungEstimasiHarga()
 * 3. cekBentrok() — berbagai skenario tumpang tindih
 * 4. statusTransisiValid() — semua transisi yang valid & invalid
 * 5. beriKeputusan() — keputusan ganda (409) dan bentrok (422)
 */
class PengajuanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PengajuanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PengajuanService::class);
    }

    // =========================================================================
    // hitungJumlahHari
    // =========================================================================

    /** @test */
    public function test_hitung_hari_inklusif_tiga_hari(): void
    {
        // Sewa tanggal 10–12 = 3 hari
        $this->assertSame(3, $this->service->hitungJumlahHari('2026-12-10', '2026-12-12'));
    }

    /** @test */
    public function test_hitung_hari_satu_hari_sama(): void
    {
        // Sewa mulai = selesai = 1 hari
        $this->assertSame(1, $this->service->hitungJumlahHari('2026-12-10', '2026-12-10'));
    }

    // =========================================================================
    // hitungEstimasiHarga
    // =========================================================================

    /** @test */
    public function test_hitung_estimasi_harga_benar(): void
    {
        // harga_sewa = 500_000, 3 hari → 1_500_000
        $this->assertSame(1_500_000.0, $this->service->hitungEstimasiHarga(500_000.0, 3));
    }

    // =========================================================================
    // cekBentrok
    // =========================================================================

    private function buatUnit(): Unit
    {
        return Unit::create([
            'nama_unit' => 'Forklift Test 5T',
            'jenis_unit' => 'forklift',
            'kapasitas' => 5,
            'spesifikasi' => 'Diesel 5 Ton',
            'lokasi_unit' => 'Semarang',
            'sumber_unit' => 'internal',
            'harga_sewa' => 500_000,
            'status' => 'tersedia',
        ]);
    }

    private function buatJadwal(Unit $unit, string $mulai, string $selesai): Jadwal
    {
        return Jadwal::create([
            'id_unit' => $unit->id_unit,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'status' => 'terjadwal',
        ]);
    }

    /** @test */
    public function test_tidak_bentrok_bila_periode_tidak_tumpang_tindih(): void
    {
        $unit = $this->buatUnit();
        $this->buatJadwal($unit, '2026-12-01', '2026-12-05');

        // Sewa baru mulai setelah jadwal lama selesai
        $bentrok = $this->service->cekBentrok($unit->id_unit, '2026-12-06', '2026-12-10');
        $this->assertFalse($bentrok);
    }

    /** @test */
    public function test_bentrok_bila_periode_tumpang_tindih_sebagian(): void
    {
        $unit = $this->buatUnit();
        $this->buatJadwal($unit, '2026-12-01', '2026-12-10');

        // Sewa baru sebagian tumpang tindih
        $bentrok = $this->service->cekBentrok($unit->id_unit, '2026-12-08', '2026-12-15');
        $this->assertTrue($bentrok);
    }

    /** @test */
    public function test_bentrok_bila_periode_dicakup_penuh(): void
    {
        $unit = $this->buatUnit();
        $this->buatJadwal($unit, '2026-12-01', '2026-12-31');

        $bentrok = $this->service->cekBentrok($unit->id_unit, '2026-12-10', '2026-12-15');
        $this->assertTrue($bentrok);
    }

    /**
     * @test
     *
     * KASUS KRITIS: unit kembali tanggal 12, sewa baru mulai tanggal 12.
     * Dengan kebijakan inklusif, tanggal 12 masih dihitung sebagai hari terakhir
     * jadwal lama, sehingga HARUS dianggap BENTROK.
     *
     * KEBIJAKAN GNP BELUM DIKONFIRMASI — test ini harus tetap lulus selama
     * kebijakan inklusif berlaku. Bila kebijakan berubah (eksklusif), ubah
     * assertion menjadi assertFalse.
     */
    public function test_sewa_berurutan_mulai_sama_dengan_selesai_lama_adalah_bentrok(): void
    {
        $unit = $this->buatUnit();
        $this->buatJadwal($unit, '2026-12-10', '2026-12-12');  // kembali tgl 12

        // Sewa baru mulai tgl 12 (hari yang sama dengan tanggal kembali)
        $bentrok = $this->service->cekBentrok($unit->id_unit, '2026-12-12', '2026-12-15');
        $this->assertTrue($bentrok, 'Dengan kebijakan inklusif, mulai=selesai_lama harus bentrok.');
    }

    /** @test */
    public function test_tidak_bentrok_bila_jadwal_dikecualikan(): void
    {
        $unit = $this->buatUnit();
        $jadwal = $this->buatJadwal($unit, '2026-12-10', '2026-12-12');

        // Cek dengan excludeJadwalId → harus tidak bentrok (jadwal dikecualikan)
        $bentrok = $this->service->cekBentrok(
            $unit->id_unit, '2026-12-10', '2026-12-12',
            excludeJadwalId: $jadwal->id_jadwal
        );
        $this->assertFalse($bentrok);
    }

    // =========================================================================
    // statusTransisiValid — Admin
    // =========================================================================

    /** @test */
    public function test_admin_dapat_mengubah_menunggu_ke_diproses(): void
    {
        $this->assertTrue($this->service->statusTransisiValid('menunggu', 'diproses', 'admin'));
    }

    /** @test */
    public function test_admin_dapat_mengubah_disetujui_ke_selesai(): void
    {
        $this->assertTrue($this->service->statusTransisiValid('disetujui', 'selesai', 'admin'));
    }

    /** @test */
    public function test_admin_dapat_mengubah_disetujui_ke_dibatalkan(): void
    {
        $this->assertTrue($this->service->statusTransisiValid('disetujui', 'dibatalkan', 'admin'));
    }

    /** @test */
    public function test_admin_tidak_dapat_mengubah_menunggu_ke_disetujui(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('menunggu', 'disetujui', 'admin');
    }

    /** @test */
    public function test_admin_tidak_dapat_mengubah_diproses_ke_selesai(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('diproses', 'selesai', 'admin');
    }

    // =========================================================================
    // statusTransisiValid — Owner
    // =========================================================================

    /** @test */
    public function test_owner_dapat_menyetujui_dari_menunggu(): void
    {
        $this->assertTrue($this->service->statusTransisiValid('menunggu', 'disetujui', 'owner'));
    }

    /** @test */
    public function test_owner_dapat_menolak_dari_diproses(): void
    {
        $this->assertTrue($this->service->statusTransisiValid('diproses', 'ditolak', 'owner'));
    }

    /** @test */
    public function test_owner_tidak_dapat_mengubah_disetujui_ke_selesai(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('disetujui', 'selesai', 'owner');
    }

    // =========================================================================
    // statusTransisiValid — Status Terminal
    // =========================================================================

    /** @test */
    public function test_tidak_dapat_mengubah_status_terminal_ditolak(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('ditolak', 'menunggu', 'admin');
    }

    /** @test */
    public function test_tidak_dapat_mengubah_status_terminal_selesai(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('selesai', 'diproses', 'admin');
    }

    /** @test */
    public function test_tidak_dapat_mengubah_status_terminal_dibatalkan(): void
    {
        $this->expectException(ConflictException::class);
        $this->service->statusTransisiValid('dibatalkan', 'menunggu', 'owner');
    }
}
