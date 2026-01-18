<?php

namespace App\Services;

use App\Models\Risk;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class RiskService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Risk::query()
            ->with(['project'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('risk_code', 'like', "%{$search}%")
                        ->orWhereHas('project', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['project_id'] ?? null, fn($q, $id) => $q->where('project_id', $id))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->byType($t))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['impact'] ?? null, fn($q, $i) => $q->where('impact', $i))
            ->when($filters['probability'] ?? null, fn($q, $p) => $q->where('probability', $p))
            ->when($filters['risk_score'] ?? null, fn($q, $s) => $q->where('risk_score', $s))
            ->when($filters['owner'] ?? null, fn($q, $text) => $q->where('owner', 'LIKE', "%{$text}%"))
            ->when($filters['critical_only'] ?? false, fn($q) => $q->critical())
            ->when($filters['active_only'] ?? false, fn($q) => $q->active())
            ->orderByRaw("
                CASE risk_score
                    WHEN 'Critical' THEN 1
                    WHEN 'High' THEN 2
                    WHEN 'Medium' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Risk
    {
        return Risk::with([
            'project',
            'owner',
            'comments.user',
            'activities' => fn($q) => $q->with('user')->latest()->limit(20),
        ])->findOrFail($id);
    }

    public function create(array $data): Risk
    {
        return DB::transaction(function () use ($data) {
            $risk = Risk::create($data);

            $this->logActivity($risk, 'created', $data);

            return $risk->load(['project']);
        });
    }

    public function update(Risk $risk, array $data): Risk
    {
        return DB::transaction(function () use ($risk, $data) {
            $oldData = $risk->toArray();
            $risk->update($data);

            $changes = [
                'old' => array_intersect_key($oldData, $data),
                'new' => $data,
            ];

            $this->logActivity($risk, 'updated', $changes);

            return $risk->fresh(['project', 'owner']);
        });
    }

    public function delete(Risk $risk): void
    {
        DB::transaction(function () use ($risk) {
            $this->logActivity($risk, 'deleted');
            $risk->delete();
        });
    }

    public function updateStatus(Risk $risk, string $status): Risk
    {
        return DB::transaction(function () use ($risk, $status) {
            $oldStatus = $risk->status;

            $updateData = ['status' => $status];

            if (in_array($status, ['Mitigated', 'Closed'])) {
                $updateData['resolved_at'] = now();
            }

            $risk->update($updateData);

            $this->logActivity($risk, 'status_changed', [
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            return $risk->fresh(['project', 'owner']);
        });
    }

    public function getMatrix(): array
    {
        $matrix = [];
        $impacts = ['Critical', 'High', 'Medium', 'Low'];
        $probabilities = ['High', 'Medium', 'Low'];

        foreach ($impacts as $impactIndex => $impact) {
            foreach ($probabilities as $probIndex => $probability) {
                $risks = Risk::where('impact', $impact)
                    ->where('probability', $probability)
                    ->active()
                    ->with('project:id,project_code,name')
                    ->get();

                $matrix[] = [
                    'impact' => $impact,
                    'probability' => $probability,
                    'count' => $risks->count(),
                    'risks' => $risks->map(fn($r) => [
                        'id' => $r->id,
                        'code' => $r->risk_code,
                        'project_code' => $r->project->project_code,
                        'project_name' => $r->project->name,
                        'score' => $r->risk_score,
                    ])->toArray(),
                    'severity' => $this->calculateCellSeverity($impactIndex, $probIndex),
                ];
            }
        }

        return $matrix;
    }

    private function calculateCellSeverity(int $impactIndex, int $probIndex): string
    {
        $score = (3 - $impactIndex) + (2 - $probIndex);
        if ($score >= 4) return 'critical';
        if ($score >= 3) return 'high';
        if ($score >= 2) return 'medium';
        return 'low';
    }

    private function logActivity(Risk $risk, string $action, array $changes = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Risk::class,
            'loggable_id' => $risk->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
