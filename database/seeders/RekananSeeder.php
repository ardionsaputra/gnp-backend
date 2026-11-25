<?php

namespace Database\Seeders;

use App\Models\Rekanan;
use Illuminate\Database\Seeder;

class RekananSeeder extends Seeder
{
    /**
     * Seed data rekanan penyedia alat berat mitra GNP.
     */
    public function run(): void
    {
        $rekananList = [
            [
                'nama_rekanan' => 'PT Karya Alat Nusantara',
                'kontak' => '0812-3456-7890',
            ],
            [
                'nama_rekanan' => 'CV Mitra Crane Jawa Tengah',
                'kontak' => '0813-9876-5432',
            ],
        ];

        foreach ($rekananList as $data) {
            Rekanan::updateOrCreate(
                ['nama_rekanan' => $data['nama_rekanan']],
                $data
            );
        }
    }
}
