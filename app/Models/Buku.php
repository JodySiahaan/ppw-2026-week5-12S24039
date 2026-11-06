<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buku extends Model
{
    use HasFactory;                                                    // aktifkan fitur Factory untuk data dummy

    protected $fillable = [                                            // kolom yang boleh diisi massal
        'isbn',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'kategori_id',
        'stok',
        'sinopsis',
    ];

    public function kategori(): BelongsTo                              // relasi: satu buku milik satu kategori
    {
        return $this->belongsTo(Kategori::class);                      // dihubungkan lewat kolom kategori_id
    }

    public function peminjamans(): HasMany                             // relasi: satu buku punya banyak peminjaman
    {
        return $this->hasMany(Peminjaman::class);                      // dihubungkan lewat kolom buku_id
    }
}