<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Jadwal;
use App\Models\Pelanggan;
use App\Models\Pengajuan;
use App\Models\Simulasi;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTransaksiSeeder extends Seeder
{
    /**
     * Seed contoh simulasi, pengajuan menunggu, pengajuan disetujui, approval, dan jadwal.
     */
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $pelanggan1 = Pelanggan::where('nomor_telepon', '0811-2233-4455')->first();
        $pelanggan2 = Pelanggan::where('nomor_telepon', '0815-6677-8899')->first();
        $forklift5Ton = Unit::where('nama_unit', 'Forklift Mitsubishi FD50 (5 Ton)')->first();
        $crane25Ton = Unit::where('nama_unit', 'Mobile Crane Kato KR-25H (25 Ton)')->first();

        if (! $owner || ! $pelanggan1 || ! $pelanggan2 || ! $forklift5Ton || ! $crane25Ton) {
            return;
        }

        Simulasi::firstOrCreate(
            [
                'jenis_kebutuhan' => 'Angkat material baja ringan proyek gudang',
                'lokasi_proyek' => 'Kawasan Industri Terboyo, Semarang',
            ],
            [
                'id_pelanggan' => null,
                'kapasitas_beban' => 4.5,
                'periode_penggunaan' => '3 hari',
                'hasil_rekomendasi' => [
                    [
                        'id_unit' => $forklift5Ton->id_unit,
                        'nama_unit' => $forklift5Ton->nama_unit,
                        'jenis_unit' => $forklift5Ton->jenis_unit,
                        'kapasitas' => $forklift5Ton->kapasitas,
                        'harga_sewa' => $forklift5Ton->harga_sewa,
                    ],
                ],
                'tanggal' => now(),
            ]
        );

        Pengajuan::updateOrCreate(
            ['kode_pengajuan' => 'GNP-20261125-DEMO01'],
            [
                'id_pelanggan' => $pelanggan1->id_pelanggan,
                'id_unit' => $forklift5Ton->id_unit,
                'tanggal_pengajuan' => now(),
                'tanggal_mulai' => '2026-12-10',
                'tanggal_selesai' => '2026-12-12',
                'lokasi_proyek' => 'Pelabuhan Tanjung Emas, Semarang',
                'keterangan' => 'Bongkar muat peti kemas',
                'estimasi_harga' => 3 * $forklift5Ton->harga_sewa,
                'status' => 'menunggu',
            ]
        );

        $pengajuanDisetujui = Pengajuan::updateOrCreate(
            ['kode_pengajuan' => 'GNP-20261125-DEMO02'],
            [
                'id_pelanggan' => $pelanggan2->id_pelanggan,
                'id_unit' => $crane25Ton->id_unit,
                'tanggal_pengajuan' => now()->subDay(),
                'tanggal_mulai' => '2026-12-01',
                'tanggal_selesai' => '2026-12-03',
                'lokasi_proyek' => 'Proyek Jembatan Pedurungan, Semarang',
                'keterangan' => 'Pemasangan rangka baja jembatan',
                'estimasi_harga' => 3 * $crane25Ton->harga_sewa,
                'status' => 'disetujui',
            ]
        );

        Approval::updateOrCreate(
            ['id_pengajuan' => $pengajuanDisetujui->id_pengajuan],
            [
                'id_user' => $owner->id_user,
                'tanggal' => now()->subHours(12),
                'keputusan' => 'disetujui',
                'catatan' => 'Unit tersedia dan lokasi proyek aman dijangkau crane 25 ton.',
            ]
        );

        Jadwal::updateOrCreate(
            ['id_pengajuan' => $pengajuanDisetujui->id_pengajuan],
            [
                'id_unit' => $crane25Ton->id_unit,
                'tanggal_mulai' => '2026-12-01',
                'tanggal_selesai' => '2026-12-03',
                'status' => 'terjadwal',
            ]
        );
    }
}
