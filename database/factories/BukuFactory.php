<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class BukuFactory extends Factory
{
    public function definition(): array
    {
        $awalan = ['Pengantar', 'Dasar-Dasar', 'Panduan Praktis', 'Konsep Dasar', 'Teknik Modern'];   // awalan judul
        $topik  = ['Pemrograman Web', 'Basis Data', 'Jaringan Komputer', 'Kecerdasan Buatan',
                   'Manajemen Proyek', 'Statistika', 'Algoritma', 'Sistem Informasi'];                 // topik judul
        $penerbit = ['Gramedia Pustaka Utama', 'Informatika Bandung', 'Andi Publisher',
                     'Erlangga', 'Deepublish', 'Mizan'];                                               // daftar penerbit

        return [
            'isbn'         => fake()->unique()->isbn13(),                                  // ISBN-13 acak, unik
            'judul'        => fake()->randomElement($awalan) . ' ' . fake()->randomElement($topik),   // gabungan awalan + topik
            'penulis'      => fake()->name(),                                              // nama penulis acak
            'penerbit'     => fake()->randomElement($penerbit),                            // pilih penerbit acak
            'tahun_terbit' => fake()->numberBetween(2005, 2025),                           // tahun antara 2005 dan 2025
            'kategori_id'  => Kategori::inRandomOrder()->value('id'),                      // ambil id kategori acak yang sudah ada
            'stok'         => fake()->numberBetween(0, 25),                                // stok antara 0 dan 25
            'sinopsis'     => fake()->paragraph(3),                                        // sinopsis 3 kalimat acak
        ];
    }
}