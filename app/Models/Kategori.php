<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;                                            // aktifkan fitur Factory untuk data dummy

    protected $fillable = ['kode_kategori', 'nama_kategori'];  // kolom yang boleh diisi massal (proteksi mass assignment)

    public function bukus(): HasMany                           // relasi: satu kategori punya banyak buku
    {
        return $this->hasMany(Buku::class);                    // dihubungkan lewat kolom kategori_id di tabel bukus
    }
}