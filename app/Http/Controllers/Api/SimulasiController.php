<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulasi\StoreSimulasiRequest;
use App\Http\Resources\SimulasiResource;
use App\Services\SimulasiService;
use Illuminate\Http\JsonResponse;

class SimulasiController extends Controller
{
    public function __construct(
        private readonly SimulasiService $simulasiService
    ) {}

    /**
     * POST /api/simulasi — Hitung rekomendasi unit & simpan simulasi kebutuhan (publik).
     */
    public function store(StoreSimulasiRequest $request): JsonResponse
    {
        $simulasi = $this->simulasiService->buatSimulasi($request->validated());

        return $this->successResponse(new SimulasiResource($simulasi), 201);
    }
}
