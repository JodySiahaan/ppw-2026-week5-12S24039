<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    // Riwayat peminjaman (with('buku') mencegah N+1)
    public function index()
    {
        $peminjamans = Peminjaman::with('buku')->latest()->paginate(10);

        return view('peminjaman.index', compact('peminjamans'));
    }

    // Peminjaman buku: ACID + lockForUpdate
    public function pinjam(Request $request, int $id)
    {
        $validated = $request->validate([
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));

        try {
            DB::transaction(function () use ($validated, $nim, $id) {
                // Kunci baris buku agar request lain mengantre
                $buku = Buku::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($buku->stok < 1) {
                    throw new \RuntimeException("Stok buku \"{$buku->judul}\" habis!");
                }

                $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($masihDipinjam) {
                    throw new \RuntimeException("NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
                }

                $buku->decrement('stok');

                Peminjaman::create([
                    'kode_pinjam'         => 'PJM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'buku_id'             => $buku->id,
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => $validated['nama_peminjam'],
                    'tanggal_pinjam'      => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
                    'status'              => 'dipinjam',
                ]);
            }, 3);

            session(['last_peminjam' => $validated['nama_peminjam']]);

            return back()->with('success', 'Peminjaman berhasil dicatat secara atomik!');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION][PINJAM]: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Terjadi kesalahan basis data saat meminjam.');
        }
    }

    // Pengembalian buku: ACID + lockForUpdate
    public function kembali(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $pinjam = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($pinjam->status === 'dikembalikan') {
                    throw new \RuntimeException('Buku ini sudah dikembalikan sebelumnya.');
                }

                $buku = Buku::where('id', $pinjam->buku_id)->lockForUpdate()->firstOrFail();

                $pinjam->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                $buku->increment('stok');
            }, 3);

            return back()->with('success', 'Buku berhasil dikembalikan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION][KEMBALI]: ' . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan basis data saat pengembalian.');
        }
    }
}