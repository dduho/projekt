<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChecklistItemController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a newly created checklist item.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $maxOrder = $project->checklistItems()->max('order') ?? 0;

        $item = $project->checklistItems()->create([
            ...$validated,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist item created successfully!',
            'data' => $item,
        ], 201);
    }

    /**
     * Update a checklist item.
     */
    public function update(Request $request, ChecklistItem $item): JsonResponse
    {
        $this->authorize('update', $item->project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'completed' => 'sometimes|boolean',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Checklist item updated successfully!',
            'data' => $item,
        ]);
    }

    /**
     * Delete a checklist item.
     */
    public function destroy(ChecklistItem $item): JsonResponse
    {
        $this->authorize('delete', $item->project);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist item deleted successfully!',
        ]);
    }

    /**
     * Reorder checklist items.
     */
    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $itemData) {
            ChecklistItem::where('id', $itemData['id'])
                ->where('project_id', $project->id)
                ->update(['order' => $itemData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Checklist items reordered successfully!',
        ]);
    }
}
