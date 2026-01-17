<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeRequestRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\ActivityLog;
use App\Services\ChangeRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChangeRequestController extends Controller
{
    public function __construct(
        private ChangeRequestService $changeRequestService
    ) {}

    /**
     * List change requests - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
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

        $query = ChangeRequest::with(['project', 'requestedBy']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('change_code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('project', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('change_type')) {
            $query->where('change_type', $request->change_type);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $changeRequests = $query->orderBy('created_at', 'desc')->paginate(15);
        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);

        $stats = [
            'total' => ChangeRequest::count(),
            'pending' => ChangeRequest::whereIn('status', ['Pending', 'Under Review'])->count(),
            'approved' => ChangeRequest::where('status', 'Approved')->count(),
            'rejected' => ChangeRequest::where('status', 'Rejected')->count(),
        ];

        return Inertia::render('ChangeRequests/Index', [
            'changeRequests' => $changeRequests,
            'projects' => $projects,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'change_type', 'project_id'])
        ]);
    }

    /**
     * Show create form (Web)
     */
    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);
        return Inertia::render('ChangeRequests/Create', [
            'projects' => $projects
        ]);
    }

    /**
     * Store change request - Web or API
     */
    public function store(Request $request)
    {
        if ($request->wantsJson()) {
            $changeRequest = app(ChangeRequestRequest::class);
            $change = $this->changeRequestService->create($changeRequest->validated());

            return response()->json([
                'message' => 'Demande de changement créée avec succès.',
                'data' => new ChangeRequestResource($change),
            ], 201);
        }

        $validated = $request->validate([
            'change_code' => 'nullable|string|unique:change_requests,change_code|max:20',
            'project_id' => 'required|exists:projects,id',
            'change_type' => 'required|in:Scope,Schedule,Budget,Resource',
            'description' => 'required|string',
            'requested_by_id' => 'nullable|exists:users,id',
            'approved_by_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:Pending,Under Review,Approved,Rejected',
            'requested_at' => 'nullable|date',
            'resolved_at' => 'nullable|date'
        ]);

        $validated['status'] = $validated['status'] ?? 'Pending';
        $validated['requested_by_id'] = $validated['requested_by_id'] ?? Auth::id();
        $validated['requested_at'] = $validated['requested_at'] ?? now();

        $changeRequest = ChangeRequest::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'created',
            'changes' => [
                'change_code' => $changeRequest->change_code,
                'change_type' => $changeRequest->change_type,
            ],
        ]);

        return redirect()->route('change-requests.show', $changeRequest)
            ->with('success', 'Demande de changement creee avec succes!');
    }

    /**
     * Show change request - Web (Inertia) or API (JSON)
     */
    public function show(Request $request, ChangeRequest $changeRequest)
    {
        if ($request->wantsJson()) {
            $changeRequest = $this->changeRequestService->find($changeRequest->id);
            return new ChangeRequestResource($changeRequest);
        }

        $changeRequest->load(['project', 'requestedBy', 'approvedBy', 'comments.user']);

        return Inertia::render('ChangeRequests/Show', [
            'changeRequest' => $changeRequest
        ]);
    }

    /**
     * Update change request - Web or API
     */
    public function update(Request $request, ChangeRequest $changeRequest)
    {
        if ($request->wantsJson()) {
            $changeRequestReq = app(ChangeRequestRequest::class);
            $changeRequest = $this->changeRequestService->update($changeRequest, $changeRequestReq->validated());

            return response()->json([
                'message' => 'Demande de changement mise à jour.',
                'data' => new ChangeRequestResource($changeRequest),
            ]);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'required|string',
            'change_type' => 'required|in:Scope Change,Schedule Change,Budget Change,Resource Change',
            'impact_analysis' => 'nullable|string',
            'priority' => 'nullable|in:Low,Medium,High',
            'cost_impact' => 'nullable|numeric|min:0',
            'schedule_impact' => 'nullable|integer|min:0'
        ]);

        $changeRequest->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'updated',
            'changes' => $changeRequest->getChanges(),
        ]);

        return back()->with('success', 'Demande de changement mise a jour!');
    }

    /**
     * Approve change request - Web or API
     */
    public function approve(Request $request, ChangeRequest $changeRequest)
    {
        if ($request->wantsJson()) {
            $changeRequest = $this->changeRequestService->approve($changeRequest);

            return response()->json([
                'message' => 'Demande de changement approuvée.',
                'data' => new ChangeRequestResource($changeRequest),
            ]);
        }

        $validated = $request->validate([
            'impact_analysis' => 'nullable|string'
        ]);

        $changeRequest->approve(Auth::user());

        if (!empty($validated['impact_analysis'])) {
            $changeRequest->update(['impact_analysis' => $validated['impact_analysis']]);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'approved',
            'changes' => ['status' => 'Approved'],
        ]);

        return back()->with('success', 'Demande de changement approuvee!');
    }

    /**
     * Reject change request - Web or API
     */
    public function reject(Request $request, ChangeRequest $changeRequest)
    {
        if ($request->wantsJson()) {
            $changeRequest = $this->changeRequestService->reject($changeRequest);

            return response()->json([
                'message' => 'Demande de changement rejetée.',
                'data' => new ChangeRequestResource($changeRequest),
            ]);
        }

        $validated = $request->validate([
            'impact_analysis' => 'nullable|string'
        ]);

        $changeRequest->reject(Auth::user());

        if (!empty($validated['impact_analysis'])) {
            $changeRequest->update(['impact_analysis' => $validated['impact_analysis']]);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'rejected',
            'changes' => ['status' => 'Rejected'],
        ]);

        return back()->with('success', 'Demande de changement rejetee!');
    }

    /**
     * Start review (API)
     */
    public function startReview(ChangeRequest $changeRequest): JsonResponse
    {
        $changeRequest = $this->changeRequestService->startReview($changeRequest);

        return response()->json([
            'message' => 'Demande de changement en cours de revue.',
            'data' => new ChangeRequestResource($changeRequest),
        ]);
    }

    /**
     * Delete change request - Web or API
     */
    public function destroy(Request $request, ChangeRequest $changeRequest)
    {
        $changeCode = $changeRequest->change_code;

        if ($request->wantsJson()) {
            $this->changeRequestService->delete($changeRequest);
            return response()->json([
                'message' => 'Demande de changement supprimée.',
            ]);
        }

        $changeRequest->delete();

        return redirect()->route('change-requests.index')
            ->with('success', "Demande {$changeCode} supprimee avec succes!");
    }
}
