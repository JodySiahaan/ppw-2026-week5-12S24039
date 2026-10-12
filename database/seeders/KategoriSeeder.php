<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode_kategori' => 'TEK',  'nama_kategori' => 'Teknologi Informasi'],  
            ['kode_kategori' => 'MAT',  'nama_kategori' => 'Matematika & Statistika'], 
            ['kode_kategori' => 'MNJ',  'nama_kategori' => 'Manajemen & Bisnis'],    
            ['kode_kategori' => 'SAS',  'nama_kategori' => 'Sastra & Bahasa'],       
            ['kode_kategori' => 'SNS',  'nama_kategori' => 'Sains & Teknik'],        
        ];

        foreach ($data as $item) {
            Kategori::create($item);                                                 
        }
    }
}