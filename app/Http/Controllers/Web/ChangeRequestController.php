<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ChangeRequest::with(['project', 'requestedBy']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('change_code', 'like', '%' . $request->search . '%')
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
            'total_cost' => ChangeRequest::where('status', 'Approved')->sum('cost_impact') ?? 0,
        ];

        return Inertia::render('ChangeRequests/Index', [
            'changeRequests' => $changeRequests,
            'projects' => $projects,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'change_type', 'project_id'])
        ]);
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'project_code']);
        return Inertia::render('ChangeRequests/Create', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
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

        // Auto-set defaults
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

    public function show(ChangeRequest $changeRequest)
    {
        $changeRequest->load(['project', 'requestedBy', 'approvedBy', 'comments.user']);

        return Inertia::render('ChangeRequests/Show', [
            'changeRequest' => $changeRequest
        ]);
    }

    public function approve(Request $request, ChangeRequest $changeRequest)
    {
        $changeRequest->update([
            'status' => 'Approved',
            'approved_by_id' => Auth::id(),
            'resolved_at' => now()
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'approved',
            'changes' => ['status' => 'Approved'],
        ]);

        return back()->with('success', 'Demande de changement approuvee!');
    }

    public function reject(Request $request, ChangeRequest $changeRequest)
    {
        $changeRequest->update([
            'status' => 'Rejected',
            'approved_by_id' => Auth::id(),
            'resolved_at' => now()
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => ChangeRequest::class,
            'loggable_id' => $changeRequest->id,
            'action' => 'rejected',
            'changes' => ['status' => 'Rejected'],
        ]);

        return back()->with('success', 'Demande de changement rejetee!');
    }

    public function destroy(ChangeRequest $changeRequest)
    {
        $changeCode = $changeRequest->change_code;
        $changeRequest->delete();

        return redirect()->route('change-requests.index')
            ->with('success', "Demande {$changeCode} supprimee avec succes!");
    }
}
