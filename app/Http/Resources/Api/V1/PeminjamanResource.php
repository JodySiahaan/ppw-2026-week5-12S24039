<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $links = [
            'self' => [
                'href'   => url("/api/v1/peminjamans/{$this->id}"),
                'method' => 'GET',
            ],
            'buku' => [
                'href'   => url("/api/v1/bukus/{$this->buku_id}"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS Dinamis: Link kembalikan_buku HANYA muncul apabila status === 'dipinjam'
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = [
                'href'   => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH',
            ];
        }

        return [
            'id'                  => $this->id,
            'kode_pinjam'         => $this->kode_pinjam ?? null,
            'buku_id'             => (int) $this->buku_id,
            'buku_ringkas'        => $this->whenLoaded('buku', fn () => [
                'id'    => $this->buku->id,
                'isbn'  => $this->buku->isbn,
                'judul' => $this->buku->judul,
            ]),
            'nim_peminjam'        => $this->nim_peminjam,
            'nama_peminjam'       => $this->nama_peminjam,
            'tanggal_pinjam'      => $this->tanggal_pinjam,
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
            'tanggal_kembali'     => $this->tanggal_kembali,
            'status'              => $this->status,
            'created_at'          => $this->created_at?->toIso8601String(),
            '_links'              => $links,
        ];
    }
}