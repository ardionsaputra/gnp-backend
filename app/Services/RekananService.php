<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\Rekanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RekananService
{
    /**
     * Ambil semua rekanan dengan pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return Rekanan::orderBy('id_rekanan')->paginate($perPage);
    }

    /**
     * Cari satu rekanan berdasarkan ID. 404 bila tidak ada.
     */
    public function getById(int $id): Rekanan
    {
        return Rekanan::findOrFail($id);
    }

    /**
     * Buat rekanan baru.
     */
    public function create(array $data): Rekanan
    {
        return Rekanan::create($data);
    }

    /**
     * Perbarui data rekanan.
     */
    public function update(Rekanan $rekanan, array $data): Rekanan
    {
        $rekanan->update($data);

        return $rekanan->fresh();
    }

    /**
     * Hapus rekanan. Gagal (409) bila masih ada unit yang merujuk rekanan ini.
     *
     * @throws ConflictException
     */
    public function delete(Rekanan $rekanan): void
    {
        if ($rekanan->units()->exists()) {
            throw new ConflictException(
                'Rekanan tidak dapat dihapus karena masih memiliki unit alat berat yang terkait.'
            );
        }

        $rekanan->delete();
    }
}
