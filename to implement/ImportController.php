<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    public function __construct(
        protected ExcelImportService $importService
    ) {}

    /**
     * Upload et valider le fichier Excel
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ], [
            'file.required' => 'Veuillez sélectionner un fichier Excel.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx ou .xls).',
            'file.max' => 'Le fichier ne doit pas dépasser 10 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $filename = 'imports/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        
        Storage::disk('local')->put($filename, file_get_contents($file));
        
        return response()->json([
            'success' => true,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'message' => 'Fichier uploadé avec succès. Utilisez /import/preview pour visualiser les données.',
        ]);
    }

    /**
     * Preview des données avant import
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $filename = $request->input('filename');
        $path = Storage::disk('local')->path($filename);
        
        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'error' => 'Fichier non trouvé. Veuillez re-uploader le fichier.',
            ], 404);
        }
        
        try {
            $preview = $this->importService->preview($path);
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la lecture du fichier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exécuter l'import
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $filename = $request->input('filename');
        $path = Storage::disk('local')->path($filename);
        
        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'error' => 'Fichier non trouvé. Veuillez re-uploader le fichier.',
            ], 404);
        }
        
        // Exécuter l'import
        $result = $this->importService->import($path);
        
        // Supprimer le fichier temporaire après import réussi
        if ($result['success']) {
            Storage::disk('local')->delete($filename);
        }
        
        return response()->json($result);
    }

    /**
     * Import direct d'un fichier (upload + import en une seule requête)
     * Utile pour les imports automatisés
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function directImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $tempPath = $file->getRealPath();
        
        try {
            $result = $this->importService->import($tempPath);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            ], 500);
        }
    }
}
