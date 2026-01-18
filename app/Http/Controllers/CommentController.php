<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a new comment for a project
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $project->comments()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $project->id,
            'action' => 'comment_added',
            'description' => 'Commentaire ajouté: ' . substr($validated['content'], 0, 50) . '...',
        ]);

        return back()->with('success', 'Commentaire ajouté avec succès.');
    }

    /**
     * Update a comment
     */
    public function update(Request $request, Comment $comment)
    {
        // Only owner can update
        if ($comment->user_id !== Auth::id()) {
            abort(403, 'Non autorisé');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Commentaire mis à jour.');
    }

    /**
     * Delete a comment
     */
    public function destroy(Comment $comment)
    {
        // Only owner or admin can delete
        $user = Auth::user();
        $isAdmin = $user->role === 'admin' || (is_array($user->roles) && in_array('admin', $user->roles));
        
        if ($comment->user_id !== $user->id && !$isAdmin) {
            abort(403, 'Non autorisé');
        }

        // Store info before delete
        $projectId = $comment->commentable_id;

        $comment->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => Project::class,
            'loggable_id' => $projectId,
            'action' => 'comment_deleted',
            'description' => 'Commentaire supprimé',
        ]);

        return back()->with('success', 'Commentaire supprimé.');
    }
}
