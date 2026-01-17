<?php

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class ChangeRequestService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return ChangeRequest::query()
            ->with(['project', 'requestedBy', 'approvedBy'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('change_code', 'like', "%{$search}%")
                        ->orWhereHas('project', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['project_id'] ?? null, fn($q, $id) => $q->where('project_id', $id))
            ->when($filters['change_type'] ?? null, fn($q, $t) => $q->byType($t))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['requested_by_id'] ?? null, fn($q, $id) => $q->where('requested_by_id', $id))
            ->when($filters['pending_only'] ?? false, fn($q) => $q->pending())
            ->orderBy($filters['sort_by'] ?? 'requested_at', $filters['sort_dir'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): ChangeRequest
    {
        return ChangeRequest::with([
            'project',
            'requestedBy',
            'approvedBy',
            'comments.user',
            'activities' => fn($q) => $q->with('user')->latest()->limit(20),
        ])->findOrFail($id);
    }

    public function create(array $data): ChangeRequest
    {
        return DB::transaction(function () use ($data) {
            $data['requested_by_id'] = Auth::id();
            $data['requested_at'] = now();

            $change = ChangeRequest::create($data);

            $this->logActivity($change, 'created', $data);

            return $change->load(['project', 'requestedBy']);
        });
    }

    public function update(ChangeRequest $change, array $data): ChangeRequest
    {
        return DB::transaction(function () use ($change, $data) {
            $oldData = $change->toArray();
            $change->update($data);

            $changes = [
                'old' => array_intersect_key($oldData, $data),
                'new' => $data,
            ];

            $this->logActivity($change, 'updated', $changes);

            return $change->fresh(['project', 'requestedBy', 'approvedBy']);
        });
    }

    public function delete(ChangeRequest $change): void
    {
        DB::transaction(function () use ($change) {
            $this->logActivity($change, 'deleted');
            $change->delete();
        });
    }

    public function approve(ChangeRequest $change): ChangeRequest
    {
        return DB::transaction(function () use ($change) {
            $change->approve(Auth::user());

            $this->logActivity($change, 'approved', [
                'approved_by' => Auth::user()->name,
                'approved_at' => now()->toDateTimeString(),
            ]);

            return $change->fresh(['project', 'requestedBy', 'approvedBy']);
        });
    }

    public function reject(ChangeRequest $change): ChangeRequest
    {
        return DB::transaction(function () use ($change) {
            $change->reject(Auth::user());

            $this->logActivity($change, 'rejected', [
                'rejected_by' => Auth::user()->name,
                'rejected_at' => now()->toDateTimeString(),
            ]);

            return $change->fresh(['project', 'requestedBy', 'approvedBy']);
        });
    }

    public function startReview(ChangeRequest $change): ChangeRequest
    {
        return DB::transaction(function () use ($change) {
            $change->update(['status' => 'Under Review']);

            $this->logActivity($change, 'status_changed', [
                'new_status' => 'Under Review',
            ]);

            return $change->fresh(['project', 'requestedBy', 'approvedBy']);
        });
    }

    private function logActivity(ChangeRequest $change, string $action, array $changes = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $change->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
