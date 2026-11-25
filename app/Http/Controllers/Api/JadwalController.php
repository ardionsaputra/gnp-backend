<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jadwal\StoreJadwalRequest;
use App\Http\Requests\Jadwal\UpdateJadwalRequest;
use App\Http\Resources\JadwalResource;
use App\Models\Jadwal;
use App\Services\JadwalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function __construct(
        private readonly JadwalService $jadwalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->jadwalService->getAll($request->only(['id_unit', 'status', 'per_page']));

        return $this->successResponse([
            'items' => JadwalResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreJadwalRequest $request): JsonResponse
    {
        $jadwal = $this->jadwalService->create($request->validated());

        return $this->successResponse(new JadwalResource($jadwal->load(['unit', 'pengajuan'])), 201);
    }

    public function show(int $id): JsonResponse
    {
        $jadwal = Jadwal::with(['unit', 'pengajuan.pelanggan'])->findOrFail($id);

        return $this->successResponse(new JadwalResource($jadwal));
    }

    public function update(UpdateJadwalRequest $request, int $id): JsonResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        $updated = $this->jadwalService->update($jadwal, $request->validated());

        return $this->successResponse(new JadwalResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        $this->jadwalService->delete($jadwal);

        return $this->successResponse(['message' => 'Jadwal berhasil dihapus.']);
    }
}
