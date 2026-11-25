<?php

namespace App\Services;

use App\Models\Simulasi;
use App\Models\Unit;
use Illuminate\Support\Collection;

class SimulasiService
{
    /**
     * Simpan data simulasi, hitung rekomendasi unit, dan kembalikan record simulasi lengkap.
     */
    public function buatSimulasi(array $data): Simulasi
    {
        $rekomendasi = $this->hitungRekomendasi(
            kapasitasBeban: (float) $data['kapasitas_beban'],
            jenisUnit: $data['jenis_unit'] ?? null,
        );

        // Simpan hasil rekomendasi sebagai JSON (kolom di-cast ke array oleh model)
        $simulasi = Simulasi::create([
            'id_pelanggan' => $data['id_pelanggan'] ?? null,
            'jenis_kebutuhan' => $data['jenis_kebutuhan'],
            'kapasitas_beban' => $data['kapasitas_beban'],
            'lokasi_proyek' => $data['lokasi_proyek'],
            'periode_penggunaan' => $data['periode_penggunaan'],
            'hasil_rekomendasi' => $rekomendasi->values()->all(),
            'tanggal' => now(),
        ]);

        return $simulasi;
    }

    /**
     * Hitung rekomendasi unit berdasarkan kapasitas dan jenis_unit (opsional).
     *
     * Kriteria rekomendasi:
     * - status = 'tersedia'
     * - kapasitas >= kapasitas_beban
     * - Bila jenis_unit dikirim, filter jenis_unit juga
     * - Urut kapasitas terkecil yang masih mencukupi, maksimal 3 unit
     *
     * @return Collection<int, array>
     */
    public function hitungRekomendasi(float $kapasitasBeban, ?string $jenisUnit = null): Collection
    {
        $query = Unit::where('status', 'tersedia')
            ->where('kapasitas', '>=', $kapasitasBeban)
            ->orderBy('kapasitas', 'asc')
            ->limit(3);

        if ($jenisUnit !== null && $jenisUnit !== '') {
            $query->where('jenis_unit', $jenisUnit);
        }

        return $query->get()->map(fn (Unit $unit) => [
            'id_unit' => $unit->id_unit,
            'nama_unit' => $unit->nama_unit,
            'jenis_unit' => $unit->jenis_unit,
            'kapasitas' => $unit->kapasitas,
            'harga_sewa' => $unit->harga_sewa,
            'lokasi_unit' => $unit->lokasi_unit,
        ]);
    }
}
