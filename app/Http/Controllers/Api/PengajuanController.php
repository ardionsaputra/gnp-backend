<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengajuan\StoreApprovalRequest;
use App\Http\Requests\Pengajuan\StorePengajuanRequest;
use App\Http\Requests\Pengajuan\UpdateStatusRequest;
use App\Http\Resources\ApprovalResource;
use App\Http\Resources\PemeriksaanResource;
use App\Http\Resources\PengajuanDetailResource;
use App\Http\Resources\PengajuanResource;
use App\Http\Resources\PengajuanStatusResource;
use App\Models\Pengajuan;
use App\Services\PengajuanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function __construct(
        private readonly PengajuanService $pengajuanService
    ) {}

    /**
     * GET /api/pengajuan — Daftar seluruh pengajuan (Admin & Owner).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pengajuan::with(['pelanggan', 'unit.rekanan', 'approval', 'jadwal'])
            ->orderByDesc('id_pengajuan');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage);

        return $this->successResponse([
            'items' => PengajuanDetailResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/pengajuan/{id} — Detail satu pengajuan (Admin & Owner).
     */
    public function show(int $id): JsonResponse
    {
        $pengajuan = Pengajuan::with(['pelanggan', 'unit.rekanan', 'approval.user', 'jadwal'])
            ->findOrFail($id);

        return $this->successResponse(new PengajuanDetailResource($pengajuan));
    }

    /**
     * POST /api/pengajuan — Kirim pengajuan penyewaan oleh pelanggan tanpa login (publik).
     */
    public function store(StorePengajuanRequest $request): JsonResponse
    {
        $pengajuan = $this->pengajuanService->buatPengajuan($request->validated());

        return $this->successResponse(new PengajuanResource($pengajuan), 201);
    }

    /**
     * GET /api/pengajuan/status/{kode} — Cek status pengajuan publik memakai kode_pengajuan.
     * Hanya mengembalikan data seperlunya tanpa data pribadi pelanggan.
     */
    public function status(string $kode): JsonResponse
    {
        $pengajuan = Pengajuan::with('unit')
            ->where('kode_pengajuan', $kode)
            ->firstOrFail();

        return $this->successResponse(new PengajuanStatusResource($pengajuan));
    }

    /**
     * PATCH /api/pengajuan/{id}/status — Admin memperbarui status pengajuan.
     */
    public function updateStatus(UpdateStatusRequest $request, int $id): JsonResponse
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $this->pengajuanService->updateStatus($pengajuan, $request->validated('status'));

        return $this->successResponse(
            new PengajuanDetailResource($pengajuan->fresh(['pelanggan', 'unit.rekanan', 'approval', 'jadwal']))
        );
    }

    /**
     * GET /api/pengajuan/{id}/pemeriksaan — Owner memeriksa kebutuhan, ketersediaan unit, dan harga.
     */
    public function pemeriksaan(int $id): JsonResponse
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $data = $this->pengajuanService->getPemeriksaan($pengajuan);

        return $this->successResponse(new PemeriksaanResource($data));
    }

    /**
     * POST /api/pengajuan/{id}/approval — Owner memberi keputusan setuju/tolak.
     */
    public function approval(StoreApprovalRequest $request, int $id): JsonResponse
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $validated = $request->validated();

        $approval = $this->pengajuanService->beriKeputusan(
            pengajuan: $pengajuan,
            owner: $request->user(),
            keputusan: $validated['keputusan'],
            catatan: $validated['catatan'] ?? null,
        );

        return $this->successResponse(
            new ApprovalResource($approval->load('user')),
            201
        );
    }
}
