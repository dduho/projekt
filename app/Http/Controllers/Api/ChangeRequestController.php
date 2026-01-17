<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeRequestRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Services\ChangeRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChangeRequestController extends Controller
{
    public function __construct(
        private ChangeRequestService $changeRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $changes = $this->changeRequestService->list($request->all());

        return response()->json([
            'data' => ChangeRequestResource::collection($changes),
            'meta' => [
                'current_page' => $changes->currentPage(),
                'last_page' => $changes->lastPage(),
                'per_page' => $changes->perPage(),
                'total' => $changes->total(),
            ],
        ]);
    }

    public function show(ChangeRequest $change): ChangeRequestResource
    {
        $change = $this->changeRequestService->find($change->id);
        return new ChangeRequestResource($change);
    }

    public function store(ChangeRequestRequest $request): JsonResponse
    {
        $change = $this->changeRequestService->create($request->validated());

        return response()->json([
            'message' => 'Demande de changement créée avec succès.',
            'data' => new ChangeRequestResource($change),
        ], 201);
    }

    public function update(ChangeRequestRequest $request, ChangeRequest $change): JsonResponse
    {
        $change = $this->changeRequestService->update($change, $request->validated());

        return response()->json([
            'message' => 'Demande de changement mise à jour.',
            'data' => new ChangeRequestResource($change),
        ]);
    }

    public function destroy(ChangeRequest $change): JsonResponse
    {
        $this->changeRequestService->delete($change);

        return response()->json([
            'message' => 'Demande de changement supprimée.',
        ]);
    }

    public function approve(ChangeRequest $change): JsonResponse
    {
        $change = $this->changeRequestService->approve($change);

        return response()->json([
            'message' => 'Demande de changement approuvée.',
            'data' => new ChangeRequestResource($change),
        ]);
    }

    public function reject(ChangeRequest $change): JsonResponse
    {
        $change = $this->changeRequestService->reject($change);

        return response()->json([
            'message' => 'Demande de changement rejetée.',
            'data' => new ChangeRequestResource($change),
        ]);
    }

    public function startReview(ChangeRequest $change): JsonResponse
    {
        $change = $this->changeRequestService->startReview($change);

        return response()->json([
            'message' => 'Demande de changement en cours de revue.',
            'data' => new ChangeRequestResource($change),
        ]);
    }
}
