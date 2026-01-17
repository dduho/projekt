<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('category')
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

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(12);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'categories' => $categories,
            'filters' => $request->only(['search', 'rag_status', 'category_id'])
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return Inertia::render('Projects/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
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

        // Generate project code if not provided
        if (empty($validated['project_code'])) {
            $lastCode = Project::where('project_code', 'like', 'MOOV-%')
                ->orderByRaw("CAST(SUBSTRING(project_code, 6) AS UNSIGNED) DESC")
                ->value('project_code');
            $nextNumber = $lastCode ? (int) substr($lastCode, 5) + 1 : 1;
            $validated['project_code'] = 'MOOV-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        // Set defaults
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

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet cree avec succes!');
    }

    public function show(Project $project)
    {
        $project->load([
            'category',
            'owner',
            'phases',
            'risks' => fn($q) => $q->orderByDesc('risk_score'),
            'changeRequests' => fn($q) => $q->orderBy('created_at', 'desc'),
            'activities' => fn($q) => $q->with('user')->orderBy('created_at', 'desc')->limit(10)
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project
        ]);
    }

    public function edit(Project $project)
    {
        $categories = Category::orderBy('name')->get();
        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'business_area' => 'nullable|string|max:100',
            'priority' => 'required|in:High,Medium,Low',
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

    public function destroy(Project $project)
    {
        $projectName = $project->name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '$projectName' deleted successfully!");
    }
}
