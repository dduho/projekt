<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\CommentResource;
use App\Models\Project;
use App\Models\Comment;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): ProjectCollection
    {
        $projects = $this->projectService->list($request->all());
        return new ProjectCollection($projects);
    }

    public function show(Project $project): ProjectResource
    {
        $project = $this->projectService->find($project->id);
        return new ProjectResource($project);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return response()->json([
            'message' => 'Projet créé avec succès.',
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update($project, $request->validated());

        return response()->json([
            'message' => 'Projet mis à jour avec succès.',
            'data' => new ProjectResource($project),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return response()->json([
            'message' => 'Projet supprimé avec succès.',
        ]);
    }

    public function phases(Project $project): JsonResponse
    {
        return response()->json($project->phases);
    }

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

    public function risks(Project $project): JsonResponse
    {
        $risks = $project->risks()
            ->with('owner')
            ->orderByRaw("CASE risk_score WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END")
            ->get();

        return response()->json($risks);
    }

    public function changes(Project $project): JsonResponse
    {
        $changes = $project->changeRequests()
            ->with(['requestedBy', 'approvedBy'])
            ->latest('requested_at')
            ->get();

        return response()->json($changes);
    }

    public function activity(Project $project): JsonResponse
    {
        $activities = $project->activities()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($activities);
    }

    public function comments(Project $project): JsonResponse
    {
        $comments = $project->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json(CommentResource::collection($comments));
    }

    public function addComment(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $project->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté.',
            'data' => new CommentResource($comment->load('user')),
        ], 201);
    }

    public function duplicate(Project $project): JsonResponse
    {
        $newProject = $this->projectService->duplicate($project);

        return response()->json([
            'message' => 'Projet dupliqué avec succès.',
            'data' => new ProjectResource($newProject),
        ], 201);
    }

    public function archive(Project $project): JsonResponse
    {
        $project = $this->projectService->archive($project);

        return response()->json([
            'message' => 'Projet archivé avec succès.',
            'data' => new ProjectResource($project),
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);
        $project = $this->projectService->restore($project);

        return response()->json([
            'message' => 'Projet restauré avec succès.',
            'data' => new ProjectResource($project),
        ]);
    }
}
