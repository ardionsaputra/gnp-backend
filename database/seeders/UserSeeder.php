<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed akun Admin dan Owner awal untuk keperluan pengujian dan operasional.
     */
    public function run(): void
    {
        $users = [
            [
                'nama' => 'Admin GNP',
                'email' => 'admin@gnp.co.id',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'nama' => 'Owner GNP',
                'email' => 'owner@gnp.co.id',
                'password' => Hash::make('password123'),
                'role' => 'owner',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
