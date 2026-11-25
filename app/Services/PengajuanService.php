<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Models\Approval;
use App\Models\Jadwal;
use App\Models\Pelanggan;
use App\Models\Pengajuan;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PengajuanService
{
    // -------------------------------------------------------------------------
    // Helper: Hitung Hari & Harga
    // -------------------------------------------------------------------------

    /**
     * Hitung jumlah hari sewa secara inklusif.
     *
     * KEBIJAKAN GNP BELUM DIKONFIRMASI: hitung inklusif (selesai - mulai + 1 hari).
     * Contoh: sewa 10–12 = 3 hari. Ganti method ini bila kebijakan berubah.
     */
    public function hitungJumlahHari(string $mulai, string $selesai): int
    {
        return (int) Carbon::parse($mulai)->diffInDays(Carbon::parse($selesai)) + 1;
    }

    /**
     * Hitung estimasi harga sewa.
     * estimasi_harga = harga_sewa * jumlah_hari
     */
    public function hitungEstimasiHarga(float $hargaSewa, int $jumlahHari): float
    {
        return $hargaSewa * $jumlahHari;
    }

    // -------------------------------------------------------------------------
    // Helper: Cek Bentrok
    // -------------------------------------------------------------------------

    /**
     * Cek apakah jadwal baru bentrok dengan jadwal yang sudah ada pada unit yang sama.
     *
     * KEBIJAKAN GNP BELUM DIKONFIRMASI: bentrok bila mulai_lama <= selesai_baru
     * AND selesai_lama >= mulai_baru (inklusif). Lihat hitungJumlahHari().
     * Ganti kondisi ini bila kebijakan berubah (misalnya tanggal eksklusif).
     *
     * @param  int|null  $excludeJadwalId  ID jadwal yang dikecualikan dari cek (berguna saat update).
     * @return bool true bila ada jadwal yang bentrok
     */
    public function cekBentrok(
        int $idUnit,
        string $mulai,
        string $selesai,
        ?int $excludeJadwalId = null
    ): bool {
        $query = Jadwal::where('id_unit', $idUnit)
            ->where('tanggal_mulai', '<=', $selesai)
            ->where('tanggal_selesai', '>=', $mulai);

        if ($excludeJadwalId !== null) {
            $query->where('id_jadwal', '!=', $excludeJadwalId);
        }

        return $query->exists();
    }

    // -------------------------------------------------------------------------
    // Helper: Generate Kode Pengajuan
    // -------------------------------------------------------------------------

    /**
     * Buat kode pengajuan unik, format: GNP-YYYYMMDD-XXXXXX (6 karakter alfanumerik uppercase).
     * Cek keunikan ke database; coba maksimal 10 kali sebelum menyerah.
     *
     * @throws \RuntimeException
     */
    public function generateKode(): string
    {
        $prefix = 'GNP-'.now()->format('Ymd').'-';

        for ($i = 0; $i < 10; $i++) {
            $kode = $prefix.strtoupper(Str::random(6));

            if (! Pengajuan::where('kode_pengajuan', $kode)->exists()) {
                return $kode;
            }
        }

        throw new \RuntimeException('Gagal membuat kode pengajuan unik. Coba lagi.');
    }

    // -------------------------------------------------------------------------
    // Validasi Transisi Status (Single Source of Truth)
    // -------------------------------------------------------------------------

    /**
     * Daftar transisi status yang diizinkan per aktor.
     * Jadikan konstanta/array di sini supaya mudah diubah tanpa menyentuh controller.
     *
     * Aktor:
     * - 'admin'  → perubahan via PATCH /pengajuan/{id}/status
     * - 'owner'  → perubahan via POST  /pengajuan/{id}/approval
     *
     * Status terminal (tidak bisa diubah lagi): ditolak, selesai, dibatalkan
     */
    private const TRANSISI_VALID = [
        'admin' => [
            'menunggu' => ['diproses'],
            'disetujui' => ['selesai', 'dibatalkan'],
        ],
        'owner' => [
            'menunggu' => ['disetujui', 'ditolak'],
            'diproses' => ['disetujui', 'ditolak'],
        ],
    ];

    private const STATUS_TERMINAL = ['ditolak', 'selesai', 'dibatalkan'];

    /**
     * Periksa apakah transisi status dari $dari ke $ke oleh $aktor valid.
     *
     * @throws ConflictException bila terminal atau transisi tidak diizinkan
     */
    public function statusTransisiValid(string $dari, string $ke, string $aktor): bool
    {
        if (in_array($dari, self::STATUS_TERMINAL, true)) {
            throw new ConflictException(
                "Pengajuan tidak dapat diubah karena sudah berstatus \"{$dari}\"."
            );
        }

        $allowed = self::TRANSISI_VALID[$aktor][$dari] ?? [];

        if (! in_array($ke, $allowed, true)) {
            throw new ConflictException(
                "Transisi status dari \"{$dari}\" ke \"{$ke}\" tidak diizinkan untuk peran {$aktor}."
            );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Use Case: Buat Pengajuan (Publik)
    // -------------------------------------------------------------------------

    /**
     * Buat pengajuan sewa baru oleh pelanggan (tanpa login).
     *
     * Alur:
     * 1. Cari atau buat pelanggan berdasarkan nomor_telepon.
     * 2. Generate kode_pengajuan unik.
     * 3. Hitung estimasi_harga.
     * 4. Simpan pengajuan dengan status 'menunggu'.
     */
    public function buatPengajuan(array $data): Pengajuan
    {
        // 1. Upsert pelanggan — nomor_telepon sebagai identifier unik publik
        $pelanggan = Pelanggan::firstOrCreate(
            ['nomor_telepon' => $data['nomor_telepon']],
            [
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'alamat' => $data['alamat'],
            ]
        );

        // Kalau pelanggan sudah ada tapi namanya berbeda, update nama & alamat
        if (! $pelanggan->wasRecentlyCreated) {
            $pelanggan->update([
                'nama' => $data['nama'],
                'email' => $data['email'] ?? $pelanggan->email,
                'alamat' => $data['alamat'],
            ]);
        }

        // 2. Ambil data unit untuk hitung harga
        $unit = Unit::findOrFail($data['id_unit']);

        // 3. Hitung estimasi harga
        $jumlahHari = $this->hitungJumlahHari($data['tanggal_mulai'], $data['tanggal_selesai']);
        $estimasiHarga = $this->hitungEstimasiHarga($unit->harga_sewa, $jumlahHari);

        // 4. Simpan pengajuan
        $pengajuan = Pengajuan::create([
            'kode_pengajuan' => $this->generateKode(),
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_unit' => $unit->id_unit,
            'tanggal_pengajuan' => now(),
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'lokasi_proyek' => $data['lokasi_proyek'],
            'keterangan' => $data['keterangan'] ?? null,
            'estimasi_harga' => $estimasiHarga,
            'status' => 'menunggu',
        ]);

        return $pengajuan->load(['pelanggan', 'unit.rekanan']);
    }

    // -------------------------------------------------------------------------
    // Use Case: Update Status (Admin)
    // -------------------------------------------------------------------------

    /**
     * Admin memperbarui status pengajuan.
     * Validasi transisi dilakukan via statusTransisiValid().
     *
     * @throws ConflictException
     */
    public function updateStatus(Pengajuan $pengajuan, string $status): void
    {
        $this->statusTransisiValid($pengajuan->status, $status, 'admin');
        $pengajuan->update(['status' => $status]);
    }

    // -------------------------------------------------------------------------
    // Use Case: Beri Keputusan (Owner)
    // -------------------------------------------------------------------------

    /**
     * Owner memberi keputusan setuju/tolak terhadap pengajuan.
     *
     * Semua operasi (approval, update status, buat jadwal) dijalankan dalam
     * SATU transaksi database agar konsisten.
     *
     * @throws ConflictException bila sudah diputuskan sebelumnya atau status tidak sesuai
     * @throws BusinessRuleException bila jadwal bentrok (422)
     */
    public function beriKeputusan(
        Pengajuan $pengajuan,
        User $owner,
        string $keputusan,
        ?string $catatan = null
    ): Approval {
        return DB::transaction(function () use ($pengajuan, $owner, $keputusan, $catatan) {
            // 1. Pastikan belum pernah diputuskan
            if ($pengajuan->approval()->exists()) {
                throw new ConflictException(
                    'Pengajuan ini sudah pernah mendapat keputusan dan tidak dapat diputuskan ulang.'
                );
            }

            // 2. Validasi status pengajuan membolehkan keputusan owner
            $this->statusTransisiValid($pengajuan->status, $keputusan, 'owner');

            // 3. Buat record Approval
            $approval = Approval::create([
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'id_user' => $owner->id_user,
                'tanggal' => now(),
                'keputusan' => $keputusan,
                'catatan' => $catatan,
            ]);

            // 4. Update status pengajuan
            $pengajuan->update(['status' => $keputusan]);

            // 5. Bila disetujui: cek bentrok lalu buat jadwal
            if ($keputusan === 'disetujui') {
                $bentrok = $this->cekBentrok(
                    idUnit: $pengajuan->id_unit,
                    mulai: $pengajuan->tanggal_mulai,
                    selesai: $pengajuan->tanggal_selesai,
                );

                if ($bentrok) {
                    // Lempar exception → transaksi di-rollback otomatis
                    throw new BusinessRuleException(
                        'Unit tidak tersedia pada periode yang diminta karena jadwal bentrok dengan pengajuan lain yang sudah disetujui.'
                    );
                }

                Jadwal::create([
                    'id_unit' => $pengajuan->id_unit,
                    'id_pengajuan' => $pengajuan->id_pengajuan,
                    'tanggal_mulai' => $pengajuan->tanggal_mulai,
                    'tanggal_selesai' => $pengajuan->tanggal_selesai,
                    'status' => 'terjadwal',
                ]);
            }

            return $approval;
        });
    }

    // -------------------------------------------------------------------------
    // Use Case: Pemeriksaan (Owner)
    // -------------------------------------------------------------------------

    /**
     * Owner memeriksa pengajuan sebelum memberi keputusan.
     * Mengembalikan data lengkap pengajuan + info ketersediaan unit + harga.
     *
     * @return array{pengajuan: Pengajuan, ketersediaan: bool, jumlah_hari: int, estimasi_harga: float}
     */
    public function getPemeriksaan(Pengajuan $pengajuan): array
    {
        $pengajuan->load(['pelanggan', 'unit.rekanan', 'approval', 'jadwal']);

        $jumlahHari = $this->hitungJumlahHari(
            $pengajuan->tanggal_mulai,
            $pengajuan->tanggal_selesai
        );

        // Cek ketersediaan unit pada periode pengajuan ini,
        // kecualikan jadwal milik pengajuan ini sendiri (bila sudah pernah disetujui lalu dibuka ulang)
        $excludeJadwalId = $pengajuan->jadwal?->id_jadwal;
        $bentrok = $this->cekBentrok(
            idUnit: $pengajuan->id_unit,
            mulai: $pengajuan->tanggal_mulai,
            selesai: $pengajuan->tanggal_selesai,
            excludeJadwalId: $excludeJadwalId,
        );

        return [
            'pengajuan' => $pengajuan,
            'ketersediaan' => ! $bentrok,   // true = tersedia / tidak bentrok
            'jumlah_hari' => $jumlahHari,
            'estimasi_harga' => $this->hitungEstimasiHarga($pengajuan->unit->harga_sewa, $jumlahHari),
        ];
    }
}
