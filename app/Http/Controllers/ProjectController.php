<?php

namespace App\Http\Controllers;

use App\Events\ProjectCreated;
use App\Events\ProjectStatusChanged;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\CommentResource;
use App\Models\Project;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * List projects - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $projects = $this->projectService->list($request->all());
            return new ProjectCollection($projects);
        }

        $query = Project::with(['category', 'owner'])
            ->withCount(['risks', 'changeRequests']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('project_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('rag_status')) {
            $query->where('rag_status', $request->rag_status);
        }

        if ($request->filled('dev_status')) {
            $query->where('dev_status', $request->dev_status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Apply sorting
        $sort = $request->get('sort', '');
        if ($sort) {
            switch ($sort) {
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'completion_desc':
                    $query->orderBy('completion_percent', 'desc');
                    break;
                case 'completion_asc':
                    $query->orderBy('completion_percent', 'asc');
                    break;
                case 'target_date_asc':
                    $query->orderBy('target_date', 'asc');
                    break;
                case 'target_date_desc':
                    $query->orderBy('target_date', 'desc');
                    break;
                case 'created_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'created_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
            }
        } else {
            // Default sort
            $query->orderBy('created_at', 'desc');
        }

        $projects = $query->paginate(12);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'categories' => $categories,
            'filters' => $request->only(['search', 'rag_status', 'dev_status', 'category', 'sort'])
        ]);
    }

    /**
     * Show create form (Web)
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return Inertia::render('Projects/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store project - Web or API
     */
    public function store(Request $request)
    {
        if ($request->wantsJson()) {
            $projectRequest = app(ProjectRequest::class);
            $project = $this->projectService->create($projectRequest->validated());

            return response()->json([
                'message' => 'Projet créé avec succès.',
                'data' => new ProjectResource($project),
            ], 201);
        }

        $validated = $request->validate([
            'project_code' => 'nullable|string|unique:projects,project_code|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'business_area' => 'nullable|string|max:100',
            'priority' => 'required|in:High,Medium,Low',
            'frs_status' => 'nullable|in:Draft,Review,Signoff',
            'dev_status' => 'nullable|in:Not Started,In Development,Testing,UAT,Deployed',
            'current_progress' => 'nullable|string|max:100',
            'blockers' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'planned_release' => 'nullable|string|max:50',
            'target_date' => 'nullable|date',
            'submission_date' => 'nullable|date',
            'rag_status' => 'nullable|in:Green,Amber,Red',
            'completion_percent' => 'nullable|integer|min:0|max:100',
        ]);

        // Auto-generate project code if empty
        if (empty($validated['project_code'])) {
            $lastCode = Project::where('project_code', 'like', 'MOOV-%')
                ->orderByRaw("CAST(SUBSTRING(project_code, 6) AS UNSIGNED) DESC")
                ->value('project_code');
            $nextNumber = $lastCode ? (int) substr($lastCode, 5) + 1 : 1;
            $validated['project_code'] = 'MOOV-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        $validated['frs_status'] = $validated['frs_status'] ?? 'Draft';
        $validated['dev_status'] = $validated['dev_status'] ?? 'Not Started';
        $validated['rag_status'] = $validated['rag_status'] ?? 'Green';
        $validated['completion_percent'] = $validated['completion_percent'] ?? 0;
        $validated['submission_date'] = $validated['submission_date'] ?? now();

        $project = Project::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $project->id,
            'action' => 'created',
            'changes' => ['name' => $project->name],
        ]);

        // Dispatch event
        event(new ProjectCreated($project, Auth::user()));

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet cree avec succes!');
    }

    /**
     * Show project - Web (Inertia) or API (JSON)
     */
    public function show(Request $request, Project $project, RiskScoringService $riskService)
    {
        if ($request->wantsJson()) {
            $project = $this->projectService->find($project->id);
            return new ProjectResource($project);
        }

        $project->load([
            'category',
            'owner',
            'phases',
            'risks' => fn($q) => $q->orderByDesc('risk_score'),
            'changeRequests' => fn($q) => $q->orderBy('created_at', 'desc'),
            'activities' => fn($q) => $q->with('user')->orderBy('created_at', 'desc')->limit(10),
            'comments' => fn($q) => $q->with(['user', 'replies.user'])->whereNull('parent_id')->orderBy('created_at', 'desc')
        ]);

        // Auto-calculate ML risk analysis
        $analysis = $riskService->analyzeProject($project);
        $project->ml_risk_analysis = $analysis;

        // Récupérer les utilisateurs pour le sélecteur de owner
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'users' => $users
        ]);
    }

    /**
     * Show edit form (Web)
     */
    public function edit(Project $project)
    {
        $categories = Category::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        
        // Format dates for input[type=date]
        $projectData = $project->toArray();
        if ($project->submission_date) {
            $projectData['submission_date'] = $project->submission_date->format('Y-m-d');
        }
        if ($project->target_date) {
            $projectData['target_date'] = $project->target_date->format('Y-m-d');
        }
        if ($project->planned_release) {
            $projectData['planned_release'] = $project->planned_release->format('Y-m-d');
        }
        
        return Inertia::render('Projects/Edit', [
            'project' => $projectData,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    /**
     * Update project - Web or API
     */
    public function update(Request $request, Project $project)
    {
        if ($request->wantsJson()) {
            $projectRequest = app(ProjectRequest::class);
            $project = $this->projectService->update($project, $projectRequest->validated());

            return response()->json([
                'message' => 'Projet mis à jour avec succès.',
                'data' => new ProjectResource($project),
            ]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'business_area' => 'nullable|string|max:100',
            'priority' => 'sometimes|required|in:High,Medium,Low',
            'frs_status' => 'nullable|in:Draft,Review,Signoff',
            'dev_status' => 'nullable|in:Not Started,In Development,Testing,UAT,Deployed,On Hold',
            'rag_status' => 'nullable|in:Green,Amber,Red',
            'submission_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'go_live_date' => 'nullable|date',
            'planned_release' => 'nullable|string|max:50',
            'completion_percent' => 'nullable|integer|min:0|max:100',
            'blockers' => 'nullable|string',
            'current_progress' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'need_po' => 'nullable|boolean',
        ]);

        $project->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $project->id,
            'action' => 'updated',
            'changes' => $project->getChanges(),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet mis a jour avec succes!');
    }

    /**
     * Delete project - Web or API
     */
    public function destroy(Request $request, Project $project)
    {
        $projectName = $project->name;

        if ($request->wantsJson()) {
            $this->projectService->delete($project);
            return response()->json([
                'message' => 'Projet supprimé avec succès.',
            ]);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '$projectName' deleted successfully!");
    }

    /**
     * Get project phases (API)
     */
    public function phases(Project $project): JsonResponse
    {
        return response()->json($project->phases);
    }

    /**
     * Update a project phase (API)
     */
    public function updatePhase(Request $request, Project $project, string $phase): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed,Blocked',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $projectPhase = $this->projectService->updatePhase(
            $project,
            $phase,
            $request->status,
            $request->remarks
        );

        return response()->json([
            'message' => 'Phase mise à jour avec succès.',
            'data' => $projectPhase,
            'project' => new ProjectResource($project->fresh(['phases'])),
        ]);
    }

    /**
     * Get project risks (API)
     */
    public function risks(Project $project): JsonResponse
    {
        $risks = $project->risks()
            ->with('owner')
            ->orderByRaw("CASE risk_score WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END")
            ->get();

        return response()->json($risks);
    }

    /**
     * Get project change requests (API)
     */
    public function changes(Project $project): JsonResponse
    {
        $changes = $project->changeRequests()
            ->with(['requestedBy', 'approvedBy'])
            ->latest('requested_at')
            ->get();

        return response()->json($changes);
    }

    /**
     * Get project activity (API)
     */
    public function activity(Project $project): JsonResponse
    {
        $activities = $project->activities()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Get project comments (API)
     */
    public function comments(Project $project): JsonResponse
    {
        $comments = $project->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json(CommentResource::collection($comments));
    }

    /**
     * Add comment to project (API)
     */
    public function addComment(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $project->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté.',
            'data' => new CommentResource($comment->load('user')),
        ], 201);
    }

    /**
     * Duplicate project (API)
     */
    public function duplicate(Project $project): JsonResponse
    {
        $newProject = $this->projectService->duplicate($project);

        return response()->json([
            'message' => 'Projet dupliqué avec succès.',
            'data' => new ProjectResource($newProject),
        ], 201);
    }

    /**
     * Archive project (API)
     */
    public function archive(Project $project): JsonResponse
    {
        $project = $this->projectService->archive($project);

        return response()->json([
            'message' => 'Projet archivé avec succès.',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Restore archived project (API)
     */
    public function restore(int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);
        $project = $this->projectService->restore($project);

        return response()->json([
            'message' => 'Projet restauré avec succès.',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Analyser les risques d'un projet via ML
     */
    public function analyzeRisks(Request $request, Project $project, RiskScoringService $riskService)
    {
        $analysis = $riskService->analyzeProject($project);
        $created = $riskService->createSuggestedRisks($project);

        return back()->with('success', "{$created} risque(s) détecté(s) et créé(s) automatiquement. Score de risque: {$analysis['score']} ({$analysis['level']})");
    }
}

