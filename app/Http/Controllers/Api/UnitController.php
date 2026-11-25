<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\FilterUnitRequest;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    public function __construct(
        private readonly UnitService $unitService
    ) {}

    /**
     * GET /api/units — Katalog unit alat berat (publik, dengan filter opsional).
     */
    public function index(FilterUnitRequest $request): JsonResponse
    {
        $paginator = $this->unitService->getAll($request->validated());

        return $this->successResponse([
            'items' => UnitResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/units/{id} — Detail satu unit alat berat (publik).
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitService->getById($id);

        return $this->successResponse(new UnitResource($unit));
    }

    /**
     * POST /api/units — Tambah unit alat berat baru (khusus admin).
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = $this->unitService->create($request->validated());

        return $this->successResponse(new UnitResource($unit->load('rekanan')), 201);
    }

    /**
     * PUT /api/units/{id} — Perbarui data unit alat berat (khusus admin).
     */
    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
        $unit = $this->unitService->getById($id);
        $updated = $this->unitService->update($unit, $request->validated());

        return $this->successResponse(new UnitResource($updated));
    }

    /**
     * DELETE /api/units/{id} — Hapus unit alat berat (khusus admin, 409 jika masih dirujuk).
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitService->getById($id);
        $this->unitService->delete($unit);

        return $this->successResponse(['message' => 'Unit berhasil dihapus.']);
    }
}
