<x-layout title="Detail Kategori">
    <h3 class="text-del fw-bold mb-3">Detail Kategori</h3>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <tr><th style="width: 180px">Kode</th><td>{{ $kategori->kode_kategori }}</td></tr>
                <tr><th>Nama Kategori</th><td>{{ $kategori->nama_kategori }}</td></tr>
                <tr><th>Jumlah Buku</th><td>{{ $kategori->bukus->count() }}</td></tr>
            </table>
        </div>
    </div>

    <h5 class="text-del fw-bold">Buku dalam kategori ini</h5>
    <div class="card shadow-sm">
        <ul class="list-group list-group-flush">
            @forelse($kategori->bukus as $buku)
                <li class="list-group-item">
                    <a href="{{ route('buku.show', $buku) }}" class="text-decoration-none">{{ $buku->judul }}</a>
                    <span class="text-muted">— {{ $buku->penulis }}</span>
                </li>
            @empty
                <li class="list-group-item text-center text-muted">Belum ada buku di kategori ini.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-3">
        <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-del">Edit</a>
        <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</x-layout> 