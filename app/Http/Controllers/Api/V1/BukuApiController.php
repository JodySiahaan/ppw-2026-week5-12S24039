<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class BukuApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $allowedSorts = ['isbn', 'judul', 'penulis', 'stok', 'tahun_terbit', 'created_at'];
        $sortBy  = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'judul';
        $sortDir = strtolower($request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);

        $paginator = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($request->query('kategori_id'), fn ($q, $katId) => $q->where('kategori_id', (int) $katId))
            ->when($request->boolean('tersedia'), fn ($q) => $q->where('stok', '>', 0))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: BukuResource::collection($paginator->items()),
            message: 'Daftar katalog buku SIPUS-Del berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
                'total_pages'  => $paginator->lastPage(),
                'links'        => [
                    'first' => $paginator->url(1),
                    'prev'  => $paginator->previousPageUrl(),
                    'next'  => $paginator->nextPageUrl(),
                    'last'  => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    public function show(string $id): JsonResponse
    {
        $buku = Buku::with(['kategori', 'peminjamans'])
            ->withCount('peminjamans')
            ->where('id', $id)
            ->orWhere('isbn', $id)
            ->first();

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID atau ISBN '{$id}' tidak ditemukan di basis data.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.'
        );
    }

    public function referensiEksternal(string $id): JsonResponse
    {
        $buku = Buku::where('id', $id)->orWhere('isbn', $id)->first();

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku lokal dengan ID/ISBN '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', (string) $buku->isbn);
        $bibKey = "ISBN:{$cleanIsbn}";

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(8)
                ->get('https://openlibrary.org/api/books', [
                    'bibkeys' => $bibKey,
                    'format'  => 'json',
                    'jscmd'   => 'data',
                ]);

            if ($response->failed()) {
                return $this->problemResponse(
                    title: 'Bad Gateway - External Service Error',
                    detail: 'Gagal menghubungi layanan eksternal OpenLibrary REST API.',
                    status: 502,
                    errorSlug: 'openlibrary-upstream-error'
                );
            }

            $payload = $response->json();
            $extData = $payload[$bibKey] ?? null;

            return $this->successResponse(
                data: [
                    'local_book' => [
                        'id'      => $buku->id,
                        'isbn'    => $buku->isbn,
                        'judul'   => $buku->judul,
                        'penulis' => $buku->penulis,
                    ],
                    'external_source'   => 'OpenLibrary REST API (https://openlibrary.org)',
                    'metadata_found'    => !empty($extData),
                    'external_metadata' => $extData ? [
                        'title'          => $extData['title'] ?? $buku->judul,
                        'number_of_pages'=> $extData['number_of_pages'] ?? null,
                        'publish_date'   => $extData['publish_date'] ?? null,
                        'publishers'     => collect($extData['publishers'] ?? [])->pluck('name')->all(),
                        'authors'        => collect($extData['authors'] ?? [])->pluck('name')->all(),
                        'info_url'       => $extData['url'] ?? "https://openlibrary.org/isbn/{$cleanIsbn}",
                        'cover_url'      => $extData['cover']['medium'] ?? null,
                    ] : [
                        'note'           => "Metadata ISBN '{$cleanIsbn}' belum terdaftar di katalog publik OpenLibrary.",
                        'lookup_url'     => "https://openlibrary.org/isbn/{$cleanIsbn}",
                    ],
                ],
                message: 'Pengayaan metadata buku eksternal berhasil diproses melalui Laravel HTTP Client.'
            );
        } catch (\Throwable $e) {
            return $this->problemResponse(
                title: 'External API Timeout / Connection Error',
                detail: 'Koneksi menuju OpenLibrary API melewati batas waktu (timeout 8 detik) atau jaringan terputus.',
                status: 504,
                errorSlug: 'openlibrary-timeout'
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id'  => 'required|integer|exists:kategoris,id',
            'isbn'         => 'required|string|max:17|unique:bukus,isbn',
            'judul'        => 'required|string|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:100',
            'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'stok'         => 'required|integer|min:0|max:1000',
            'sinopsis'     => 'nullable|string|max:2000',
        ]);

        $columns = Schema::getColumnListing('bukus');
        $dataToSave = array_intersect_key($validated, array_flip($columns));

        $buku = Buku::create($dataToSave);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil ditambahkan ke katalog SIPUS-Del.'
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $isPut = $request->isMethod('put');

        if ($isPut) {
            $validated = $request->validate([
                'kategori_id'  => 'required|integer|exists:kategoris,id',
                'isbn'         => "required|string|max:17|unique:bukus,isbn,{$buku->id}",
                'judul'        => 'required|string|max:200',
                'penulis'      => 'required|string|max:150',
                'penerbit'     => 'required|string|max:100',
                'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'stok'         => 'required|integer|min:0|max:1000',
                'sinopsis'     => 'nullable|string|max:2000',
            ]);
            $validated['sinopsis'] = $request->input('sinopsis', null);
        } else {
            $validated = $request->validate([
                'kategori_id'  => 'sometimes|required|integer|exists:kategoris,id',
                'isbn'         => "sometimes|required|string|max:17|unique:bukus,isbn,{$buku->id}",
                'judul'        => 'sometimes|required|string|max:200',
                'penulis'      => 'sometimes|required|string|max:150',
                'penerbit'     => 'sometimes|required|string|max:100',
                'tahun_terbit' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
                'stok'         => 'sometimes|required|integer|min:0|max:1000',
                'sinopsis'     => 'sometimes|nullable|string|max:2000',
            ]);

            if (empty($validated)) {
                return $this->problemResponse(
                    title: 'Bad Request',
                    detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                    status: 400,
                    errorSlug: 'empty-patch-payload'
                );
            }
        }

        $columns = Schema::getColumnListing('bukus');
        $dataToUpdate = array_intersect_key($validated, array_flip($columns));

        $buku->update($dataToUpdate);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $isPut
                ? 'Seluruh representasi data buku berhasil diganti penuh (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $activeLoansCount = $buku->peminjamans()
            ->where('status', 'dipinjam')
            ->count();

        if ($activeLoansCount > 0) {
            return $this->problemResponse(
                title: 'Conflict - Book Has Active Loans',
                detail: "Buku '{$buku->judul}' tidak dapat dihapus karena sedang dipinjam aktif oleh {$activeLoansCount} mahasiswa.",
                status: 409,
                errorSlug: 'buku-has-active-loans'
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }
}