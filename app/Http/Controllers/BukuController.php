<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
    public function index()
    {
        $bukus = Buku::with('kategori')->latest()->paginate(10); // eager loading
        return view('buku.index', compact('bukus'));
    }

    public function create()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        Buku::create($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil ditambahkan!');
    }

    public function show(Buku $buku)
    {
        $buku->load('kategori');
        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.edit', compact('buku', 'kategoris'));
    }

    public function update(Request $request, Buku $buku)
    {
        $validated = $request->validate(
            $this->rules($buku),
            $this->messages(),
            $this->attributes()
        );

        $buku->update($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil diperbarui!');
    }

    public function destroy(Buku $buku)
    {
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil dihapus!');
    }

    // Aturan validasi dipakai bersama oleh store dan update
    private function rules(?Buku $buku = null): array
    {
        return [
            'isbn' => [
                'required', 'string', 'max:17',
                Rule::unique('bukus', 'isbn')->ignore($buku?->id), // saat update, abaikan data sendiri
            ],
            'judul'        => 'required|string|min:5|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:100',
            'tahun_terbit' => 'required|integer|between:1900,' . date('Y'),
            'kategori_id'  => 'required|exists:kategoris,id',
            'stok'         => 'required|integer|min:0',
            'sinopsis'     => 'nullable|string',
        ];
    }

    private function messages(): array
    {
        return [
            'required'             => ':attribute wajib diisi.',
            'unique'               => ':attribute sudah terdaftar.',
            'integer'              => ':attribute harus berupa angka bulat.',
            'exists'               => ':attribute yang dipilih tidak valid.',
            'judul.min'            => 'Judul minimal :min karakter.',
            'stok.min'             => 'Stok minimal 0.',
            'tahun_terbit.between' => 'Tahun terbit harus antara :min dan :max.',
            'max'                  => ':attribute maksimal :max karakter.',
        ];
    }

    private function attributes(): array
    {
        return [
            'isbn'         => 'ISBN',
            'judul'        => 'Judul',
            'penulis'      => 'Penulis',
            'penerbit'     => 'Penerbit',
            'tahun_terbit' => 'Tahun terbit',
            'kategori_id'  => 'Kategori',
            'stok'         => 'Stok',
            'sinopsis'     => 'Sinopsis',
        ];
    }
}