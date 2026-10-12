<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KategoriSeeder::class,    // jalankan dulu, karena buku membutuhkan kategori
            BukuSeeder::class,        // jalankan setelah kategori ada
        ]);
    }
}