<?php

namespace Database\Seeders;

use App\Models\Buku;
use Illuminate\Database\Seeder;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        Buku::factory()->count(20)->create();    // buat 20 buku memakai BukuFactory
    }
}