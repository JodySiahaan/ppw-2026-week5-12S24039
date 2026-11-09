<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Buat akun pustakawan untuk pengujian Login Bearer Token REST API Minggu 9
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name'     => 'Pustakawan SIPUS-Del',
                'password' => bcrypt('password123'),
            ]
        );

        $this->call([
            KategoriSeeder::class,
            BukuSeeder::class,
        ]);
    }
}