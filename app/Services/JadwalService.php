<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Jadwal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JadwalService
{
    public function __construct(private PengajuanService $pengajuanService) {}

    /**
     * Ambil semua jadwal dengan pagination dan filter opsional.
     * Filter: id_unit (int), status (string), per_page (int).
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Jadwal::with(['unit', 'pengajuan.pelanggan'])->orderBy('tanggal_mulai');

        if (! empty($filters['id_unit'])) {
            $query->where('id_unit', $filters['id_unit']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->paginate($perPage);
    }

    /**
     * Buat jadwal baru. Cek bentrok lebih dulu.
     *
     * @throws BusinessRuleException (422) bila bentrok
     */
    public function create(array $data): Jadwal
    {
        $this->pastikanTidakBentrok(
            idUnit: (int) $data['id_unit'],
            mulai: $data['tanggal_mulai'],
            selesai: $data['tanggal_selesai'],
        );

        return Jadwal::create($data);
    }

    /**
     * Perbarui jadwal. Cek bentrok dengan mengecualikan jadwal saat ini.
     *
     * @throws BusinessRuleException (422) bila bentrok
     */
    public function update(Jadwal $jadwal, array $data): Jadwal
    {
        $idUnit = $data['id_unit'] ?? $jadwal->id_unit;
        $mulai = $data['tanggal_mulai'] ?? $jadwal->tanggal_mulai;
        $selesai = $data['tanggal_selesai'] ?? $jadwal->tanggal_selesai;

        $this->pastikanTidakBentrok(
            idUnit: (int) $idUnit,
            mulai: $mulai,
            selesai: $selesai,
            excludeJadwalId: $jadwal->id_jadwal,
        );

        $jadwal->update($data);

        return $jadwal->fresh(['unit', 'pengajuan']);
    }

    /**
     * Hapus jadwal.
     */
    public function delete(Jadwal $jadwal): void
    {
        $jadwal->delete();
    }

    /**
     * Helper internal: lempar BusinessRuleException bila cekBentrok() mengembalikan true.
     *
     * @throws BusinessRuleException
     */
    private function pastikanTidakBentrok(
        int $idUnit,
        string $mulai,
        string $selesai,
        ?int $excludeJadwalId = null
    ): void {
        $bentrok = $this->pengajuanService->cekBentrok($idUnit, $mulai, $selesai, $excludeJadwalId);

        if ($bentrok) {
            throw new BusinessRuleException(
                'Jadwal bentrok dengan jadwal lain pada unit yang sama di periode tersebut.'
            );
        }
    }
}
