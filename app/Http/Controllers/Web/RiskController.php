<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\Project;
use App\Models\ActivityLog;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class RiskController extends Controller
{
    public function __construct(
        private RiskService $riskService
    ) {}

    public function index(Request $request)
    {
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

        $stats = [
            'total' => Risk::count(),
            'critical' => Risk::where('risk_score', 'Critical')->count(),
            'high' => Risk::where('risk_score', 'High')->count(),
            'medium' => Risk::where('risk_score', 'Medium')->count(),
            'low' => Risk::where('risk_score', 'Low')->count(),
            'open' => Risk::where('status', 'Open')->count(),
        ];

        return Inertia::render('Risks/Index', [
            'risks' => $risks,
            'projects' => $projects,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'project_id', 'risk_score'])
        ]);
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);
        return Inertia::render('Risks/Create', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:Risk,Issue',
            'description' => 'required|string',
            'impact' => 'required|in:Low,Medium,High,Critical',
            'probability' => 'required|in:Low,Medium,High',
            'status' => 'nullable|in:Open,In Progress,Mitigated,Closed',
            'mitigation_plan' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id'
        ]);

        $validated['status'] = $validated['status'] ?? 'Open';
        $validated['identified_at'] = now();

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

        return redirect()->route('risks.index')
            ->with('success', 'Risque cree avec succes!');
    }

    public function matrix()
    {
        $matrix = $this->riskService->getMatrix();
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

    public function update(Request $request, Risk $risk)
    {
        $validated = $request->validate([
            'type' => 'required|in:Risk,Issue',
            'description' => 'required|string',
            'impact' => 'required|in:Low,Medium,High,Critical',
            'probability' => 'required|in:Low,Medium,High',
            'status' => 'required|in:Open,In Progress,Mitigated,Closed',
            'mitigation_plan' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id'
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

    public function destroy(Risk $risk)
    {
        $riskCode = $risk->risk_code;
        $risk->delete();

        return back()->with('success', "Risque {$riskCode} supprime avec succes!");
    }
}
