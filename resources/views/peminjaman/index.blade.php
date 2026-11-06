<x-layout title="Riwayat Peminjaman">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-del fw-bold mb-0">Riwayat Peminjaman</h3>
        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">&larr; Katalog Buku</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Buku</th>
                        <th>NIM</th>
                        <th>Peminjam</th>
                        <th>Tgl Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $p)
                        <tr>
                            <td>{{ $p->kode_pinjam }}</td>
                            <td>{{ $p->buku->judul }}</td>
                            <td>{{ $p->nim_peminjam }}</td>
                            <td>{{ $p->nama_peminjam }}</td>
                            <td>{{ $p->tanggal_pinjam }}</td>
                            <td>{{ $p->tanggal_jatuh_tempo }}</td>
                            <td>{{ $p->tanggal_kembali ?? '-' }}</td>
                            <td>
                                @if ($p->status === 'dipinjam')
                                    <span class="badge bg-warning text-dark">Dipinjam</span>
                                @else
                                    <span class="badge bg-success">Dikembalikan</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($p->status === 'dipinjam')
                                    <form action="{{ route('peminjaman.kembali', $p->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Kembalikan buku ini?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Kembalikan</button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada data peminjaman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $peminjamans->links() }}
    </div>
</x-layout>