<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function __construct(
        private ExcelImportService $importService
    ) {}

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $fullPath = storage_path('app/' . $path);

        $validation = $this->importService->validate($fullPath);

        // Clean up temp file
        unlink($fullPath);

        return response()->json($validation);
    }

    public function excel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $fullPath = storage_path('app/' . $path);

        try {
            $result = $this->importService->import($fullPath);

            // Clean up temp file
            unlink($fullPath);

            if ($result['success']) {
                return response()->json([
                    'message' => 'Import réussi.',
                    'stats' => $result['stats'],
                    'errors' => $result['errors'],
                ]);
            } else {
                return response()->json([
                    'message' => 'Erreur lors de l\'import: ' . ($result['message'] ?? 'Erreur inconnue'),
                    'stats' => $result['stats'],
                    'errors' => $result['errors'],
                ], 422);
            }
        } catch (\Exception $e) {
            // Clean up temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            ], 500);
        }
    }
}
