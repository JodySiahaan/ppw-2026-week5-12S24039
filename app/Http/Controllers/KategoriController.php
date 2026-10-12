<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::withCount('bukus')->latest()->paginate(10); // hitung buku tanpa N+1
        return view('kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('kategori.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        Kategori::create($validated);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function show(Kategori $kategori)
    {
        $kategori->load('bukus');
        return view('kategori.show', compact('kategori'));
    }

    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate(
            $this->rules($kategori),
            $this->messages(),
            $this->attributes()
        );

        $kategori->update($validated);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete(); // buku di kategori ini ikut terhapus (cascadeOnDelete)

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil dihapus!');
    }

    private function rules(?Kategori $kategori = null): array
    {
        return [
            'kode_kategori' => [
                'required', 'string', 'max:10',
                Rule::unique('kategoris', 'kode_kategori')->ignore($kategori?->id),
            ],
            'nama_kategori' => 'required|string|min:3|max:100',
        ];
    }

    private function messages(): array
    {
        return [
            'required'           => ':attribute wajib diisi.',
            'unique'             => ':attribute sudah terdaftar.',
            'max'                => ':attribute maksimal :max karakter.',
            'nama_kategori.min'  => 'Nama kategori minimal :min karakter.',
        ];
    }

    private function attributes(): array
    {
        return [
            'kode_kategori' => 'Kode kategori',
            'nama_kategori' => 'Nama kategori',
        ];
    }
}