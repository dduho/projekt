<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportController extends Controller
{
    public function __construct(
        private ExcelImportService $importService
    ) {}

    /**
     * Show import page (Web)
     */
    public function index()
    {
        return Inertia::render('Import/Index');
    }

    /**
     * Validate import file - Web or API
     */
    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $validation = $this->importService->validate($fullPath);

        // Toujours retourner le format Web avec file_path pour le frontend
        return response()->json([
            'validation' => $validation,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Preview import data (Web)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $fullPath = Storage::disk('local')->path($request->file_path);

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'Fichier non trouvé'], 404);
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $preview = [];

            foreach (['PROJECT REGISTER', 'RISK & ISSUES LOG', 'CHANGE LOG'] as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if ($sheet) {
                    $rows = $sheet->toArray();
                    $preview[$sheetName] = [
                        'headers' => $rows[0] ?? [],
                        'sample' => array_slice($rows, 1, 5),
                        'total_rows' => count($rows) - 1,
                    ];
                }
            }

            return response()->json(['preview' => $preview]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Execute import - Web or API (excel method for API compatibility)
     */
    public function import(Request $request)
    {
        // API direct upload mode
        if ($request->hasFile('file')) {
            return $this->excel($request);
        }

        // Web mode with pre-uploaded file
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $fullPath = Storage::disk('local')->path($request->file_path);

        if (!file_exists($fullPath)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Fichier non trouvé. Veuillez recommencer.'], 404);
            }
            return back()->with('error', 'Fichier non trouvé. Veuillez recommencer.');
        }

        $result = $this->importService->import($fullPath);

        // Clean up the uploaded file
        Storage::disk('local')->delete($request->file_path);

        // Si c'est une requête AJAX/JSON
        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Import terminé avec succès!',
                    'stats' => $result['stats'],
                    'errors' => $result['errors'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . ($result['error'] ?? 'Erreur inconnue'),
                'stats' => $result['stats'] ?? [],
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        // Sinon retour classique Inertia
        if ($result['success']) {
            return back()->with([
                'success' => 'Import terminé avec succès!',
                'import_stats' => $result['stats'],
                'import_errors' => $result['errors'],
            ]);
        }

        return back()->with([
            'error' => 'Erreur lors de l\'import: ' . ($result['error'] ?? $result['message'] ?? 'Erreur inconnue'),
            'import_stats' => $result['stats'] ?? [],
            'import_errors' => $result['errors'] ?? [],
        ]);
    }

    /**
     * Import Excel file directly (API)
     */
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
                    'message' => 'Erreur lors de l\'import: ' . ($result['error'] ?? $result['message'] ?? 'Erreur inconnue'),
                    'stats' => $result['stats'] ?? [],
                    'errors' => $result['errors'] ?? [],
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

    /**
     * Download import template (Web)
     */
    public function downloadTemplate()
    {
        $templatePath = resource_path('templates/import_template.xlsx');

        if (!file_exists($templatePath)) {
            // Generate template if it doesn't exist
            $this->generateTemplate($templatePath);
        }

        return response()->download($templatePath, 'prism_import_template.xlsx');
    }

    private function generateTemplate(string $path): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // PROJECT REGISTER sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PROJECT REGISTER');
        $sheet->fromArray([
            ['ID', 'Project Name', 'Category', 'Business Area', 'Priority', 'Description', 'Submission Date', 'Target Date', 'Planned Release', 'FRS Status', 'Development Status', 'RAG Status', 'Current Progress', 'Blockers', 'Service Type', 'Remarks'],
            ['PRISM-001', 'Example Project', 'Mobile App', 'Digital Services', 'High', 'Project description here', '2026-01-01', '2026-06-30', 'Q2 2026', 'Draft', 'Not Started', 'Green', '', '', 'API', ''],
        ], null, 'A1');

        // STATUS TRACKING sheet
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('STATUS TRACKING');
        $sheet2->fromArray([
            ['Project ID', 'FRS', 'Development', 'Testing', 'UAT', 'Deployment', 'Completion %'],
            ['PRISM-001', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', '0%'],
        ], null, 'A1');

        // RISK & ISSUES LOG sheet
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('RISK & ISSUES LOG');
        $sheet3->fromArray([
            ['ID', 'Related Project', 'Type', 'Description', 'Impact', 'Probability', 'Mitigation Plan', 'Status'],
            ['RISK-001', 'PRISM-001', 'Risk', 'Risk description here', 'Medium', 'Low', 'Mitigation steps', 'Open'],
        ], null, 'A1');

        // CHANGE LOG sheet
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('CHANGE LOG');
        $sheet4->fromArray([
            ['Change ID', 'Project ID', 'Change Type', 'Description', 'Status', 'Date'],
            ['CHG-001', 'PRISM-001', 'Scope Change', 'Change description here', 'Pending', '2026-01-15'],
        ], null, 'A1');

        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
    }
}
