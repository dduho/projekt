<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectPhaseController extends Controller
{
    /**
     * Update phase status
     */
    public function updateStatus(Request $request, ProjectPhase $phase)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed,Blocked',
            'remarks' => 'nullable|string|max:500',
        ]);

        $oldStatus = $phase->status;
        
        // Update timestamps based on status
        if ($validated['status'] === 'In Progress' && !$phase->started_at) {
            $phase->started_at = now();
        }
        
        if ($validated['status'] === 'Completed' && !$phase->completed_at) {
            $phase->completed_at = now();
        }
        
        // Reset completed_at if going back from Completed
        if ($oldStatus === 'Completed' && $validated['status'] !== 'Completed') {
            $phase->completed_at = null;
        }

        $phase->status = $validated['status'];
        
        if (isset($validated['remarks'])) {
            $phase->remarks = $validated['remarks'];
        }
        
        $phase->save();

        // Log activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $phase->project_id,
            'action' => 'phase_updated',
            'changes' => [
                'phase' => $phase->phase,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ],
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Phase mise à jour',
                'phase' => $phase->fresh(),
            ]);
        }

        return back()->with('success', 'Phase mise à jour avec succès');
    }

    /**
     * Bulk update phases
     */
    public function bulkUpdate(Request $request, Project $project)
    {
        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.id' => 'required|exists:project_phases,id',
            'phases.*.status' => 'required|in:Pending,In Progress,Completed,Blocked',
        ]);

        foreach ($validated['phases'] as $phaseData) {
            $phase = ProjectPhase::find($phaseData['id']);
            
            if ($phase->project_id !== $project->id) {
                continue;
            }

            $oldStatus = $phase->status;
            
            if ($phaseData['status'] === 'In Progress' && !$phase->started_at) {
                $phase->started_at = now();
            }
            
            if ($phaseData['status'] === 'Completed' && !$phase->completed_at) {
                $phase->completed_at = now();
            }

            $phase->status = $phaseData['status'];
            $phase->save();

            if ($oldStatus !== $phaseData['status']) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'loggable_type' => Project::class,
                    'loggable_id' => $project->id,
                    'action' => 'phase_updated',
                    'changes' => [
                        'phase' => $phase->phase,
                        'old_status' => $oldStatus,
                        'new_status' => $phaseData['status'],
                    ],
                ]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Phases mises à jour',
                'phases' => $project->phases()->get(),
            ]);
        }

        return back()->with('success', 'Phases mises à jour avec succès');
    }
}
