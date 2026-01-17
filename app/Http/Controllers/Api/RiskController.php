<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RiskRequest;
use App\Http\Resources\RiskResource;
use App\Models\Risk;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RiskController extends Controller
{
    public function __construct(
        private RiskService $riskService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $risks = $this->riskService->list($request->all());

        return response()->json([
            'data' => RiskResource::collection($risks),
            'meta' => [
                'current_page' => $risks->currentPage(),
                'last_page' => $risks->lastPage(),
                'per_page' => $risks->perPage(),
                'total' => $risks->total(),
            ],
        ]);
    }

    public function show(Risk $risk): RiskResource
    {
        $risk = $this->riskService->find($risk->id);
        return new RiskResource($risk);
    }

    public function store(RiskRequest $request): JsonResponse
    {
        $risk = $this->riskService->create($request->validated());

        return response()->json([
            'message' => 'Risque créé avec succès.',
            'data' => new RiskResource($risk),
        ], 201);
    }

    public function update(RiskRequest $request, Risk $risk): JsonResponse
    {
        $risk = $this->riskService->update($risk, $request->validated());

        return response()->json([
            'message' => 'Risque mis à jour avec succès.',
            'data' => new RiskResource($risk),
        ]);
    }

    public function destroy(Risk $risk): JsonResponse
    {
        $this->riskService->delete($risk);

        return response()->json([
            'message' => 'Risque supprimé avec succès.',
        ]);
    }

    public function updateStatus(Request $request, Risk $risk): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Open,In Progress,Mitigated,Closed',
        ]);

        $risk = $this->riskService->updateStatus($risk, $request->status);

        return response()->json([
            'message' => 'Statut du risque mis à jour.',
            'data' => new RiskResource($risk),
        ]);
    }

    public function matrix(): JsonResponse
    {
        $matrix = $this->riskService->getMatrix();

        return response()->json($matrix);
    }
}
