<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rekanan\StoreRekananRequest;
use App\Http\Requests\Rekanan\UpdateRekananRequest;
use App\Http\Resources\RekananResource;
use App\Services\RekananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekananController extends Controller
{
    public function __construct(
        private readonly RekananService $rekananService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->rekananService->getAll($request->only(['per_page']));

        return $this->successResponse([
            'items' => RekananResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreRekananRequest $request): JsonResponse
    {
        $rekanan = $this->rekananService->create($request->validated());

        return $this->successResponse(new RekananResource($rekanan), 201);
    }

    public function show(int $id): JsonResponse
    {
        $rekanan = $this->rekananService->getById($id);

        return $this->successResponse(new RekananResource($rekanan));
    }

    public function update(UpdateRekananRequest $request, int $id): JsonResponse
    {
        $rekanan = $this->rekananService->getById($id);
        $updated = $this->rekananService->update($rekanan, $request->validated());

        return $this->successResponse(new RekananResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $rekanan = $this->rekananService->getById($id);
        $this->rekananService->delete($rekanan);

        return $this->successResponse(['message' => 'Rekanan berhasil dihapus.']);
    }
}
