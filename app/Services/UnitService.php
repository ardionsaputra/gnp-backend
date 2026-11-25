<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\Jadwal;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitService
{
    /**
     * Ambil semua unit dengan pagination dan filter opsional.
     * Filter yang tersedia: jenis_unit (string), status (string), per_page (int).
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Unit::with('rekanan')->orderBy('id_unit');

        if (! empty($filters['jenis_unit'])) {
            $query->where('jenis_unit', $filters['jenis_unit']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->paginate($perPage);
    }

    /**
     * Cari satu unit berdasarkan ID. Lempar ModelNotFoundException bila tidak ada (→ 404).
     */
    public function getById(int $id): Unit
    {
        return Unit::with('rekanan')->findOrFail($id);
    }

    /**
     * Buat unit baru.
     */
    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    /**
     * Perbarui data unit yang sudah ada.
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->fresh('rekanan');
    }

    /**
     * Hapus unit. Gagal (409 Conflict) bila unit masih punya pengajuan atau jadwal.
     *
     * @throws ConflictException
     */
    public function delete(Unit $unit): void
    {
        $adaPengajuan = $unit->pengajuan()->exists();
        $adaJadwal = $unit->jadwal()->exists();

        if ($adaPengajuan || $adaJadwal) {
            throw new ConflictException(
                'Unit tidak dapat dihapus karena masih memiliki data pengajuan atau jadwal yang terkait.'
            );
        }

        $unit->delete();
    }

    /**
     * Cek apakah unit tersedia pada periode tertentu (tidak bentrok dengan jadwal yang ada).
     * Logika bentrok sama dengan PengajuanService::cekBentrok() — keduanya mengacu jadwal tabel.
     *
     * @param  int|null  $excludePengajuanId  Jadwal milik pengajuan ini dikecualikan (berguna saat re-approval).
     */
    public function cekKetersediaan(
        Unit $unit,
        string $tanggalMulai,
        string $tanggalSelesai,
        ?int $excludePengajuanId = null
    ): bool {
        // Catatan: cek bentrok inklusif, menunggu konfirmasi aturan dari GNP.
        $query = Jadwal::where('id_unit', $unit->id_unit)
            ->where('tanggal_mulai', '<=', $tanggalSelesai)
            ->where('tanggal_selesai', '>=', $tanggalMulai);

        if ($excludePengajuanId !== null) {
            $query->where('id_pengajuan', '!=', $excludePengajuanId);
        }

        // Tersedia = tidak ada jadwal yang bentrok
        return ! $query->exists();
    }
}
