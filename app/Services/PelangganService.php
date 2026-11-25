<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\Pelanggan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PelangganService
{
    /**
     * Ambil semua pelanggan dengan pagination. Filter opsional: search (nama/email/telepon).
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Pelanggan::orderBy('id_pelanggan');

        if (! empty($filters['search'])) {
            $keyword = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword)
                    ->orWhere('nomor_telepon', 'like', $keyword);
            });
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->paginate($perPage);
    }

    /**
     * Cari satu pelanggan berdasarkan ID. 404 bila tidak ada.
     */
    public function getById(int $id): Pelanggan
    {
        return Pelanggan::findOrFail($id);
    }

    /**
     * Buat pelanggan baru.
     */
    public function create(array $data): Pelanggan
    {
        return Pelanggan::create($data);
    }

    /**
     * Perbarui data pelanggan.
     */
    public function update(Pelanggan $pelanggan, array $data): Pelanggan
    {
        $pelanggan->update($data);

        return $pelanggan->fresh();
    }

    /**
     * Hapus pelanggan. Gagal (409) bila pelanggan masih punya pengajuan.
     *
     * @throws ConflictException
     */
    public function delete(Pelanggan $pelanggan): void
    {
        if ($pelanggan->pengajuan()->exists()) {
            throw new ConflictException(
                'Pelanggan tidak dapat dihapus karena masih memiliki data pengajuan.'
            );
        }

        $pelanggan->delete();
    }
}
