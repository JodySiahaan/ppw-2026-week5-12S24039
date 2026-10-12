<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_kategori' => strtoupper(fake()->unique()->lexify('???')),   // kode acak 3 huruf kapital, unik
            'nama_kategori' => fake()->words(2, true),                        // nama kategori acak 2 kata
        ];
    }
}