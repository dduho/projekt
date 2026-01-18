<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttachmentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a newly uploaded attachment.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        try {
            $this->authorize('update', $project);

            $validated = $request->validate([
                'file' => 'required|file|max:52428800', // 50MB
            ]);

            $file = $validated['file'];
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = 'attachments/' . $project->id;

            // Store file
            $filePath = Storage::disk('local')->putFileAs($path, $file, $filename);

            // Create attachment record
            $attachment = $project->attachments()->create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully!',
                'data' => $attachment->load('creator'),
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error uploading attachment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file',
            ], 500);
        }
    }

    /**
     * Download an attachment.
     */
    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment->project);

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        return Storage::download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        try {
            // Authorize the user
            $this->authorize('update', $attachment->project);

            // Delete file from storage
            if (Storage::disk('local')->exists($attachment->file_path)) {
                Storage::disk('local')->delete($attachment->file_path);
            }

            // Delete record
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully!',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file',
            ], 500);
        }
    }
}
