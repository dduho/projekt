<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Project::query()
            ->with(['category', 'phases'])
            ->withCount(['risks', 'changeRequests'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->search($s))
            ->when($filters['category_id'] ?? null, fn($q, $id) => $q->where('category_id', $id))
            ->when($filters['priority'] ?? null, fn($q, $p) => $q->byPriority($p))
            ->when($filters['rag_status'] ?? null, fn($q, $s) => $q->byRagStatus($s))
            ->when($filters['dev_status'] ?? null, fn($q, $s) => $q->byDevStatus($s))
            ->when($filters['owner'] ?? null, fn($q, $text) => $q->where('owner', 'LIKE', "%{$text}%"))
            ->when($filters['frs_status'] ?? null, fn($q, $s) => $q->where('frs_status', $s))
            ->when($filters['has_blockers'] ?? false, fn($q) => $q->whereNotNull('blockers'))
            ->orderBy($filters['sort_by'] ?? 'project_code', $filters['sort_dir'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Project
    {
        return Project::with([
            'category',
            'phases',
            'risks' => fn($q) => $q->orderByRaw("
                CASE risk_score
                    WHEN 'Critical' THEN 1
                    WHEN 'High' THEN 2
                    WHEN 'Medium' THEN 3
                    ELSE 4
                END
            ")->limit(10),
            'changeRequests' => fn($q) => $q->latest()->limit(10),
            'comments.user',
            'activities' => fn($q) => $q->with('user')->latest()->limit(20),
        ])->findOrFail($id);
    }

    public function create(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create($data);

            $this->logActivity($project, 'created', $data);

            return $project->load(['category', 'phases']);
        });
    }

    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $oldData = $project->toArray();
            $project->update($data);

            $changes = [
                'old' => array_intersect_key($oldData, $data),
                'new' => $data,
            ];

            $this->logActivity($project, 'updated', $changes);

            return $project->fresh(['category', 'phases']);
        });
    }

    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $this->logActivity($project, 'deleted');
            $project->delete();
        });
    }

    public function updatePhase(Project $project, string $phase, string $status, ?string $remarks = null): ProjectPhase
    {
        return DB::transaction(function () use ($project, $phase, $status, $remarks) {
            $projectPhase = $project->phases()->where('phase', $phase)->firstOrFail();

            $oldStatus = $projectPhase->status;

            $updateData = [
                'status' => $status,
                'remarks' => $remarks,
            ];

            if ($status === 'In Progress' && !$projectPhase->started_at) {
                $updateData['started_at'] = now();
            }

            if ($status === 'Completed') {
                $updateData['completed_at'] = now();
            } elseif ($status !== 'Completed') {
                $updateData['completed_at'] = null;
            }

            $projectPhase->update($updateData);

            $this->syncProjectDevStatus($project);

            $this->logActivity($project, 'phase_updated', [
                'phase' => $phase,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            return $projectPhase->fresh();
        });
    }

    public function duplicate(Project $project): Project
    {
        return DB::transaction(function () use ($project) {
            $newProject = $project->replicate([
                'project_code',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $newProject->name = $project->name . ' (Copie)';
            $newProject->dev_status = 'Not Started';
            $newProject->completion_percent = 0;
            $newProject->rag_status = 'Green';
            $newProject->blockers = null;
            $newProject->go_live_date = null;
            $newProject->save();

            $this->logActivity($newProject, 'created', ['duplicated_from' => $project->project_code]);

            return $newProject->load(['category', 'phases']);
        });
    }

    public function archive(Project $project): Project
    {
        return DB::transaction(function () use ($project) {
            $project->update(['dev_status' => 'On Hold']);

            $this->logActivity($project, 'status_changed', [
                'action' => 'archived',
                'new_status' => 'On Hold',
            ]);

            return $project->fresh();
        });
    }

    public function restore(Project $project): Project
    {
        return DB::transaction(function () use ($project) {
            $project->restore();

            $this->logActivity($project, 'status_changed', [
                'action' => 'restored',
            ]);

            return $project->fresh(['category', 'owner', 'phases']);
        });
    }

    private function syncProjectDevStatus(Project $project): void
    {
        $phases = $project->phases()->get();

        $statusMap = [
            'Deployment' => 'Deployed',
            'UAT' => 'UAT',
            'Testing' => 'Testing',
            'Development' => 'In Development',
            'FRS' => 'Not Started',
        ];

        if ($phases->contains('status', 'Blocked')) {
            $project->update(['dev_status' => 'On Hold']);
            return;
        }

        foreach (['Deployment', 'UAT', 'Testing', 'Development', 'FRS'] as $phase) {
            $projectPhase = $phases->firstWhere('phase', $phase);
            if ($projectPhase && in_array($projectPhase->status, ['In Progress', 'Completed'])) {
                if ($projectPhase->status === 'Completed' && $phase === 'Deployment') {
                    $project->update([
                        'dev_status' => 'Deployed',
                        'completion_percent' => 100,
                        'go_live_date' => $projectPhase->completed_at ?? now(),
                    ]);
                } else {
                    $project->update(['dev_status' => $statusMap[$phase] ?? 'In Development']);
                }
                break;
            }
        }

        $this->updateCompletionPercent($project, $phases);
    }

    private function updateCompletionPercent(Project $project, $phases): void
    {
        $completedCount = $phases->where('status', 'Completed')->count();
        $inProgressCount = $phases->where('status', 'In Progress')->count();

        $percent = ($completedCount * 20) + ($inProgressCount * 10);
        $project->update(['completion_percent' => min(100, $percent)]);
    }

    private function logActivity(Project $project, string $action, array $changes = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $project->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
