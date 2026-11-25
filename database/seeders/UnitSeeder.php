<?php

namespace Database\Seeders;

use App\Models\Rekanan;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Seed katalog unit forklift dan crane milik GNP dan rekanan.
     */
    public function run(): void
    {
        $karyaAlat = Rekanan::where('nama_rekanan', 'PT Karya Alat Nusantara')->first();
        $mitraCrane = Rekanan::where('nama_rekanan', 'CV Mitra Crane Jawa Tengah')->first();

        $units = [
            [
                'id_rekanan' => null,
                'nama_unit' => 'Forklift Toyota 8FD30 (3 Ton)',
                'jenis_unit' => 'forklift',
                'kapasitas' => 3.0,
                'spesifikasi' => 'Diesel, tinggi angkat maksimal 4,5 meter, garpu standar 1,2 meter',
                'lokasi_unit' => 'Pool Kaligawe, Semarang Timur',
                'sumber_unit' => 'internal',
                'harga_sewa' => 1200000,
                'status' => 'tersedia',
            ],
            [
                'id_rekanan' => null,
                'nama_unit' => 'Forklift Mitsubishi FD50 (5 Ton)',
                'jenis_unit' => 'forklift',
                'kapasitas' => 5.0,
                'spesifikasi' => 'Diesel, tinggi angkat maksimal 5 meter, cocok untuk muatan kontainer dan gudang berat',
                'lokasi_unit' => 'Pool Kaligawe, Semarang Timur',
                'sumber_unit' => 'internal',
                'harga_sewa' => 1750000,
                'status' => 'tersedia',
            ],
            [
                'id_rekanan' => $karyaAlat?->id_rekanan,
                'nama_unit' => 'Forklift TCM FD100 (10 Ton)',
                'jenis_unit' => 'forklift',
                'kapasitas' => 10.0,
                'spesifikasi' => 'Heavy-duty diesel forklift, tinggi angkat 4 meter, ban solid',
                'lokasi_unit' => 'Kawasan Industri Candi, Semarang Barat',
                'sumber_unit' => 'rekanan',
                'harga_sewa' => 2800000,
                'status' => 'tersedia',
            ],
            [
                'id_rekanan' => null,
                'nama_unit' => 'Mobile Crane Kato KR-25H (25 Ton)',
                'jenis_unit' => 'crane',
                'kapasitas' => 25.0,
                'spesifikasi' => 'Rough terrain crane, panjang boom utama 30,5 meter + jib',
                'lokasi_unit' => 'Pool Tanjung Emas, Semarang Utara',
                'sumber_unit' => 'internal',
                'harga_sewa' => 6500000,
                'status' => 'tersedia',
            ],
            [
                'id_rekanan' => $mitraCrane?->id_rekanan,
                'nama_unit' => 'Mobile Crane Tadano GR-500 (50 Ton)',
                'jenis_unit' => 'crane',
                'kapasitas' => 50.0,
                'spesifikasi' => 'All terrain crane kapasitas 50 ton, panjang boom 42 meter',
                'lokasi_unit' => 'Kawasan Industri Kendal - Semarang',
                'sumber_unit' => 'rekanan',
                'harga_sewa' => 11000000,
                'status' => 'tersedia',
            ],
            [
                'id_rekanan' => null,
                'nama_unit' => 'Forklift Komatsu FD25 (2.5 Ton)',
                'jenis_unit' => 'forklift',
                'kapasitas' => 2.5,
                'spesifikasi' => 'Diesel 2,5 ton, sedang jadwal servis berkala',
                'lokasi_unit' => 'Workshop Kaligawe, Semarang',
                'sumber_unit' => 'internal',
                'harga_sewa' => 1050000,
                'status' => 'perawatan',
            ],
        ];

        foreach ($units as $data) {
            Unit::updateOrCreate(
                ['nama_unit' => $data['nama_unit']],
                $data
            );
        }
    }
}
