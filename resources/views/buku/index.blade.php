<x-layout title="Daftar Buku">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-del fw-bold mb-0">Daftar Buku</h3>
        <div>
            <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary">Riwayat Peminjaman</a>
            <a href="{{ route('buku.create') }}" class="btn btn-del">+ Tambah Buku</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ISBN</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Pinjam</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td>{{ $buku->isbn }}</td>
                            <td>{{ $buku->judul }}</td>
                            <td>{{ $buku->penulis }}</td>
                            <td><span class="badge bg-secondary">{{ $buku->kategori->nama_kategori }}</span></td>
                            <td>{{ $buku->stok }}</td>
                            <td>
                                <form action="{{ route('buku.pinjam', $buku->id) }}" method="POST" class="d-flex gap-1">
                                    @csrf
                                    <input type="text" name="nim_peminjam" class="form-control form-control-sm"
                                        placeholder="NIM" style="width:110px" required>
                                    <input type="text" name="nama_peminjam" class="form-control form-control-sm"
                                        placeholder="Nama" value="{{ session('last_peminjam') }}" style="width:140px" required>
                                    <button class="btn btn-sm btn-success" {{ $buku->stok < 1 ? 'disabled' : '' }}>
                                        {{ $buku->stok < 1 ? 'Habis' : 'Pinjam' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('buku.show', $buku) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Yakin hapus buku ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data buku.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $bukus->links() }}
    </div>
</x-layout>