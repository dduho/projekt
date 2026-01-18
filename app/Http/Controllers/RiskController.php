<?php

namespace App\Http\Controllers;

use App\Events\RiskCreated;
use App\Http\Requests\RiskRequest;
use App\Http\Resources\RiskResource;
use App\Models\Risk;
use App\Models\Project;
use App\Models\ActivityLog;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RiskController extends Controller
{
    public function __construct(
        private RiskService $riskService
    ) {}

    /**
     * List risks - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
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

        $query = Risk::with('project');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('risk_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('project', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('risk_score')) {
            $query->where('risk_score', $request->risk_score);
        }

        $risks = $query->orderByRaw("
            CASE risk_score
                WHEN 'Critical' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Medium' THEN 3
                WHEN 'Low' THEN 4
            END
        ")->paginate(20);

        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);

        // Build projects risk data manually
        $allProjects = [];
        $projectsList = Project::with('risks', 'phases')->get();
        
        foreach ($projectsList as $p) {
            // Skip projects with no risks
            if ($p->risks->count() === 0) {
                continue;
            }
            
            $highRiskCount = 0;
            foreach ($p->risks as $risk) {
                if (in_array($risk->risk_score, ['Critical', 'High'])) {
                    $highRiskCount++;
                }
            }
            
            $allProjects[] = [
                'id' => (int)$p->id,
                'name' => (string)$p->name,
                'code' => (string)$p->project_code,
                'completion' => (int)($p->calculated_completion_percent ?? $p->completion_percent ?? 0),
                'risks_count' => $p->risks->count(),
                'risk_analysis' => $p->risk_analysis,
                'high_risks' => $highRiskCount,
                'need_po' => (bool)$p->need_po,
            ];
        }
        
        // Sort manually
        usort($allProjects, function($a, $b) {
            return $b['high_risks'] - $a['high_risks'];
        });

        $stats = [
            'total' => Risk::count(),
            'critical' => Risk::where('risk_score', 'Critical')->count(),
            'high' => Risk::where('risk_score', 'High')->count(),
            'medium' => Risk::where('risk_score', 'Medium')->count(),
            'low' => Risk::where('risk_score', 'Low')->count(),
            'open' => Risk::where('status', 'Open')->count(),
            'auto_generated' => Risk::where('auto_generated', true)->count(),
        ];

        return Inertia::render('Risks/Index', [
            'risks' => $risks,
            'projects' => $projects,
            'allProjects' => $allProjects,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'project_id', 'risk_score'])
        ]);
    }

    /**
     * Show create form (Web)
     */
    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);
        return Inertia::render('Risks/Create', [
            'projects' => $projects
        ]);
    }

    /**
     * Store risk - Web or API
     */
    public function store(Request $request)
    {
        if ($request->wantsJson()) {
            $riskRequest = app(RiskRequest::class);
            $risk = $this->riskService->create($riskRequest->validated());

            return response()->json([
                'message' => 'Risque créé avec succès.',
                'data' => new RiskResource($risk),
            ], 201);
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:Risk,Issue',
            'description' => 'required|string',
            'impact' => 'required|in:Low,Medium,High,Critical',
            'probability' => 'required|in:Low,Medium,High',
            'status' => 'nullable|in:Open,In Progress,Mitigated,Closed',
            'mitigation_plan' => 'nullable|string',
            'owner' => 'nullable|string|max:100'
        ]);

        $validated['status'] = $validated['status'] ?? 'Open';

        $risk = Risk::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Risk::class,
            'loggable_id' => $risk->id,
            'action' => 'created',
            'changes' => [
                'risk_code' => $risk->risk_code,
                'risk_score' => $risk->risk_score,
            ],
        ]);

        // Dispatch event
        event(new RiskCreated($risk, Auth::user()));

        return redirect()->route('risks.index')
            ->with('success', 'Risque cree avec succes!');
    }

    /**
     * Show risk - Web (Inertia) or API (JSON)
     */
    public function show(Request $request, Risk $risk)
    {
        if ($request->wantsJson()) {
            $risk = $this->riskService->find($risk->id);
            return new RiskResource($risk);
        }

        $risk->load(['project']);

        return Inertia::render('Risks/Show', [
            'risk' => $risk
        ]);
    }

    /**
     * Show risk matrix - Web (Inertia) or API (JSON)
     */
    public function matrix(Request $request)
    {
        $matrix = $this->riskService->getMatrix();

        if ($request->wantsJson()) {
            return response()->json($matrix);
        }

        $risks = Risk::with('project:id,name,project_code')
            ->active()
            ->get();

        $stats = [
            'total' => $risks->count(),
            'critical' => $risks->where('risk_score', 'Critical')->count(),
            'high' => $risks->where('risk_score', 'High')->count(),
            'medium' => $risks->where('risk_score', 'Medium')->count(),
            'low' => $risks->where('risk_score', 'Low')->count(),
        ];

        return Inertia::render('Risks/Matrix', [
            'matrix' => $matrix,
            'risks' => $risks,
            'stats' => $stats
        ]);
    }

    /**
     * Update risk - Web or API
     */
    public function update(Request $request, Risk $risk)
    {
        if ($request->wantsJson()) {
            $riskRequest = app(RiskRequest::class);
            $risk = $this->riskService->update($risk, $riskRequest->validated());

            return response()->json([
                'message' => 'Risque mis à jour avec succès.',
                'data' => new RiskResource($risk),
            ]);
        }

        $validated = $request->validate([
            'type' => 'required|in:Risk,Issue',
            'description' => 'required|string',
            'impact' => 'required|in:Low,Medium,High,Critical',
            'probability' => 'required|in:Low,Medium,High',
            'status' => 'required|in:Open,In Progress,Mitigated,Closed',
            'mitigation_plan' => 'nullable|string',
            'owner' => 'nullable|string|max:100'
        ]);

        // Set resolved_at if closing
        if ($validated['status'] === 'Closed' && $risk->status !== 'Closed') {
            $validated['resolved_at'] = now();
        }

        $risk->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Risk::class,
            'loggable_id' => $risk->id,
            'action' => 'updated',
            'changes' => $risk->getChanges(),
        ]);

        return back()->with('success', 'Risque mis a jour avec succes!');
    }

    /**
     * Delete risk - Web or API
     */
    public function destroy(Request $request, Risk $risk)
    {
        $riskCode = $risk->risk_code;

        if ($request->wantsJson()) {
            $this->riskService->delete($risk);
            return response()->json([
                'message' => 'Risque supprimé avec succès.',
            ]);
        }

        $risk->delete();

        return back()->with('success', "Risque {$riskCode} supprime avec succes!");
    }

    /**
     * Update risk status (API)
     */
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
}
