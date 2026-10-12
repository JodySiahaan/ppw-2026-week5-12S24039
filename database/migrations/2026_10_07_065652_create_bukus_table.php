<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->id();                                      // primary key auto increment
            $table->string('isbn', 17)->unique();              // ISBN, harus unik
            $table->string('judul', 200)->index();             // judul, diberi index agar pencarian cepat
            $table->string('penulis', 150);                    // nama penulis
            $table->string('penerbit', 100);                   // nama penerbit
            $table->unsignedSmallInteger('tahun_terbit');      // tahun terbit
            $table->foreignId('kategori_id')                   // kolom kategori_id
                  ->constrained('kategoris')                   // foreign key ke tabel kategoris
                  ->cascadeOnDelete();                         // kategori dihapus, buku ikut terhapus
            $table->unsignedInteger('stok')->default(0);       // stok, tidak negatif, default 0
            $table->text('sinopsis')->nullable();              // sinopsis, boleh kosong
            $table->timestamps();                              // created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukus');                         // hapus tabel saat rollback
    }
};