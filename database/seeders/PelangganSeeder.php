<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    /**
     * Seed contoh data pelanggan GNP di Semarang.
     */
    public function run(): void
    {
        $pelangganList = [
            [
                'nama' => 'PT Pelabuhan Jaya Semarang',
                'nomor_telepon' => '0811-2233-4455',
                'email' => 'operasional@pelabuhanjaya.co.id',
                'alamat' => 'Jl. Coaster No. 18, Tanjung Emas, Semarang Utara',
            ],
            [
                'nama' => 'CV Bangun Mandiri Konstruksi',
                'nomor_telepon' => '0815-6677-8899',
                'email' => 'proyek@bangunmandiri.id',
                'alamat' => 'Jl. Majapahit No. 120, Pedurungan, Semarang',
            ],
        ];

        foreach ($pelangganList as $data) {
            Pelanggan::updateOrCreate(
                ['nomor_telepon' => $data['nomor_telepon']],
                $data
            );
        }
    }
}
