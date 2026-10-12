<x-layout title="Detail Buku">
    <h3 class="text-del fw-bold mb-3">Detail Buku</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <tr><th style="width: 180px">ISBN</th><td>{{ $buku->isbn }}</td></tr>
                <tr><th>Judul</th><td>{{ $buku->judul }}</td></tr>
                <tr><th>Penulis</th><td>{{ $buku->penulis }}</td></tr>
                <tr><th>Penerbit</th><td>{{ $buku->penerbit }}</td></tr>
                <tr><th>Tahun Terbit</th><td>{{ $buku->tahun_terbit }}</td></tr>
                <tr><th>Kategori</th><td>{{ $buku->kategori->nama_kategori }}</td></tr>
                <tr><th>Stok</th><td>{{ $buku->stok }}</td></tr>
                <tr><th>Sinopsis</th><td>{{ $buku->sinopsis ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-del">Edit</a>
        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</x-layout>