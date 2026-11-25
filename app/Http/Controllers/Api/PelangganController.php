<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\StorePelangganRequest;
use App\Http\Requests\Pelanggan\UpdatePelangganRequest;
use App\Http\Resources\PelangganResource;
use App\Services\PelangganService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function __construct(
        private readonly PelangganService $pelangganService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->pelangganService->getAll($request->only(['search', 'per_page']));

        return $this->successResponse([
            'items' => PelangganResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StorePelangganRequest $request): JsonResponse
    {
        $pelanggan = $this->pelangganService->create($request->validated());

        return $this->successResponse(new PelangganResource($pelanggan), 201);
    }

    public function show(int $id): JsonResponse
    {
        $pelanggan = $this->pelangganService->getById($id);

        return $this->successResponse(new PelangganResource($pelanggan));
    }

    public function update(UpdatePelangganRequest $request, int $id): JsonResponse
    {
        $pelanggan = $this->pelangganService->getById($id);
        $updated = $this->pelangganService->update($pelanggan, $request->validated());

        return $this->successResponse(new PelangganResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $pelanggan = $this->pelangganService->getById($id);
        $this->pelangganService->delete($pelanggan);

        return $this->successResponse(['message' => 'Pelanggan berhasil dihapus.']);
    }
}
