<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Category;
use App\Models\ProjectPhase;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ExcelImportService
{
    protected array $stats = [
        'projects' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'phases' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'risks' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'changes' => ['created' => 0, 'updated' => 0, 'errors' => 0],
    ];
    
    protected array $errors = [];
    protected array $categoryCache = [];
    protected array $projectCache = [];
    protected array $ownerCache = [];

    /**
     * Import complet du fichier Excel
     */
    public function import(string $filePath): array
    {
        Log::info('Excel import started', ['file' => $filePath]);
        $startTime = microtime(true);
        
        $spreadsheet = IOFactory::load($filePath);
        
        DB::beginTransaction();
        
        try {
            // 1. Importer les projets depuis PROJECT REGISTER
            $projectSheet = $spreadsheet->getSheetByName('PROJECT REGISTER');
            if ($projectSheet) {
                $this->importProjects($projectSheet);
                Log::info('Projects imported', $this->stats['projects']);
            }
            
            // 2. Importer les phases depuis STATUS TRACKING
            $statusSheet = $spreadsheet->getSheetByName('STATUS TRACKING');
            if ($statusSheet) {
                $this->importPhases($statusSheet);
                Log::info('Phases imported', $this->stats['phases']);
            }
            
            // 3. Importer les risques depuis RISK & ISSUES LOG
            $riskSheet = $spreadsheet->getSheetByName('RISK & ISSUES LOG');
            if ($riskSheet) {
                $this->importRisks($riskSheet);
                Log::info('Risks imported', $this->stats['risks']);
            }
            
            // 4. Importer les changements depuis CHANGE LOG
            $changeSheet = $spreadsheet->getSheetByName('CHANGE LOG');
            if ($changeSheet) {
                $this->importChanges($changeSheet);
                Log::info('Changes imported', $this->stats['changes']);
            }
            
            DB::commit();
            
            $duration = round(microtime(true) - $startTime, 2);
            Log::info('Excel import completed', ['duration' => $duration . 's', 'stats' => $this->stats]);
            
            return [
                'success' => true,
                'stats' => $this->stats,
                'errors' => $this->errors,
                'duration' => $duration,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Preview des données sans import
     */
    public function preview(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        
        $preview = [
            'projects' => 0,
            'phases' => 0,
            'risks' => 0,
            'changes' => 0,
            'sampleProjects' => [],
        ];
        
        // Compter les projets
        $projectSheet = $spreadsheet->getSheetByName('PROJECT REGISTER');
        if ($projectSheet) {
            $data = $projectSheet->toArray(null, true, true, true);
            $count = 0;
            $samples = [];
            
            for ($row = 3; $row <= count($data); $row++) {
                $projectCode = trim($data[$row]['A'] ?? '');
                if (!empty($projectCode) && str_starts_with($projectCode, 'MOOV-')) {
                    $count++;
                    if (count($samples) < 5) {
                        $samples[] = [
                            'code' => $projectCode,
                            'name' => trim($data[$row]['B'] ?? ''),
                            'category' => trim($data[$row]['D'] ?? ''),
                            'rag' => $this->normalizeRagStatus($data[$row]['P'] ?? 'Green'),
                        ];
                    }
                }
            }
            $preview['projects'] = $count;
            $preview['sampleProjects'] = $samples;
            $preview['phases'] = $count * 5; // 5 phases par projet
        }
        
        // Compter les risques
        $riskSheet = $spreadsheet->getSheetByName('RISK & ISSUES LOG');
        if ($riskSheet) {
            $data = $riskSheet->toArray(null, true, true, true);
            for ($row = 3; $row <= count($data); $row++) {
                if (!empty(trim($data[$row]['A'] ?? '')) && str_starts_with(trim($data[$row]['A']), 'RISK-')) {
                    $preview['risks']++;
                }
            }
        }
        
        // Compter les changements
        $changeSheet = $spreadsheet->getSheetByName('CHANGE LOG');
        if ($changeSheet) {
            $data = $changeSheet->toArray(null, true, true, true);
            for ($row = 3; $row <= count($data); $row++) {
                if (!empty(trim($data[$row]['A'] ?? '')) && str_starts_with(trim($data[$row]['A']), 'CHG-')) {
                    $preview['changes']++;
                }
            }
        }
        
        return $preview;
    }

    /**
     * Importer les projets depuis PROJECT REGISTER
     */
    protected function importProjects($sheet): void
    {
        $data = $sheet->toArray(null, true, true, true);
        
        // La ligne d'en-tête est à la ligne 2 (index 2)
        // Les données commencent à la ligne 3
        for ($row = 3; $row <= count($data); $row++) {
            $rowData = $data[$row];
            
            // Skip lignes vides
            $projectCode = trim($rowData['A'] ?? '');
            if (empty($projectCode) || !str_starts_with($projectCode, 'MOOV-')) {
                continue;
            }
            
            try {
                $categoryId = $this->getOrCreateCategory($rowData['D'] ?? 'Uncategorized');
                $ownerId = $this->getOrCreateOwner($rowData['N'] ?? null);
                
                $projectData = [
                    'project_code' => $projectCode,
                    'name' => trim($rowData['B'] ?? ''),
                    'description' => trim($rowData['G'] ?? ''),
                    'category_id' => $categoryId,
                    'business_area' => trim($rowData['E'] ?? ''),
                    'priority' => $this->normalizePriority($rowData['F'] ?? 'Medium'),
                    'frs_status' => $this->normalizeFrsStatus($rowData['I'] ?? 'Draft'),
                    'dev_status' => $this->normalizeDevStatus($rowData['J'] ?? 'Not Started'),
                    'current_progress' => trim($rowData['K'] ?? ''),
                    'blockers' => trim($rowData['L'] ?? ''),
                    'owner_id' => $ownerId,
                    'planned_release' => trim($rowData['H'] ?? ''),
                    'submission_date' => $this->parseDate($rowData['C'] ?? null),
                    'target_date' => $this->parseTargetDate($rowData['M'] ?? null),
                    'rag_status' => $this->normalizeRagStatus($rowData['P'] ?? 'Green'),
                ];
                
                $project = Project::updateOrCreate(
                    ['project_code' => $projectCode],
                    $projectData
                );
                
                // Cache le project_id pour les phases/risques
                $this->projectCache[$projectCode] = $project->id;
                
                if ($project->wasRecentlyCreated) {
                    $this->stats['projects']['created']++;
                } else {
                    $this->stats['projects']['updated']++;
                }
                
            } catch (\Exception $e) {
                $this->stats['projects']['errors']++;
                $this->errors[] = "Projet {$projectCode} (ligne {$row}): " . $e->getMessage();
            }
        }
    }

    /**
     * Importer les phases depuis STATUS TRACKING
     */
    protected function importPhases($sheet): void
    {
        $data = $sheet->toArray(null, true, true, true);
        
        // Mapping des colonnes de phases
        $phases = [
            'E' => 'FRS',
            'F' => 'Development',
            'G' => 'Testing',
            'H' => 'UAT',
            'I' => 'Deployment',
        ];
        
        for ($row = 3; $row <= count($data); $row++) {
            $rowData = $data[$row];
            $projectCode = trim($rowData['A'] ?? '');
            
            if (empty($projectCode) || !str_starts_with($projectCode, 'MOOV-')) {
                continue;
            }
            
            // Trouver le project_id
            $projectId = $this->projectCache[$projectCode] ?? null;
            if (!$projectId) {
                $project = Project::where('project_code', $projectCode)->first();
                $projectId = $project?->id;
                if ($projectId) {
                    $this->projectCache[$projectCode] = $projectId;
                }
            }
            
            if (!$projectId) {
                $this->errors[] = "Phase (ligne {$row}): Projet {$projectCode} non trouvé";
                continue;
            }
            
            // Mettre à jour le completion_percent du projet
            $completion = intval($rowData['J'] ?? 0);
            Project::where('id', $projectId)->update(['completion_percent' => $completion]);
            
            // Créer/mettre à jour chaque phase
            $order = 1;
            foreach ($phases as $col => $phaseName) {
                $status = $this->parsePhaseStatus($rowData[$col] ?? '-');
                
                try {
                    ProjectPhase::updateOrCreate(
                        ['project_id' => $projectId, 'phase' => $phaseName],
                        [
                            'status' => $status,
                            'order' => $order,
                            'started_at' => in_array($status, ['Completed', 'In Progress']) ? now() : null,
                            'completed_at' => $status === 'Completed' ? now() : null,
                        ]
                    );
                    $this->stats['phases']['created']++;
                } catch (\Exception $e) {
                    $this->stats['phases']['errors']++;
                    $this->errors[] = "Phase {$phaseName} pour {$projectCode}: " . $e->getMessage();
                }
                
                $order++;
            }
        }
    }

    /**
     * Importer les risques depuis RISK & ISSUES LOG
     */
    protected function importRisks($sheet): void
    {
        $data = $sheet->toArray(null, true, true, true);
        
        for ($row = 3; $row <= count($data); $row++) {
            $rowData = $data[$row];
            $riskCode = trim($rowData['A'] ?? '');
            
            if (empty($riskCode) || !str_starts_with($riskCode, 'RISK-')) {
                continue;
            }
            
            $projectCode = trim($rowData['C'] ?? '');
            $projectId = $this->projectCache[$projectCode] 
                ?? Project::where('project_code', $projectCode)->value('id');
            
            try {
                Risk::updateOrCreate(
                    ['risk_code' => $riskCode],
                    [
                        'project_id' => $projectId,
                        'type' => trim($rowData['B'] ?? '') === 'Issue' ? 'Issue' : 'Risk',
                        'description' => trim($rowData['D'] ?? ''),
                        'impact' => $this->normalizePriority($rowData['E'] ?? 'Medium'),
                        'probability' => $this->normalizePriority($rowData['F'] ?? 'Medium'),
                        'risk_score' => $this->normalizeRiskScore($rowData['G'] ?? 'Medium'),
                        'mitigation_plan' => trim($rowData['H'] ?? ''),
                        'owner' => trim($rowData['I'] ?? ''),
                        'status' => $this->normalizeRiskStatus($rowData['J'] ?? 'Open'),
                    ]
                );
                $this->stats['risks']['created']++;
            } catch (\Exception $e) {
                $this->stats['risks']['errors']++;
                $this->errors[] = "Risque {$riskCode} (ligne {$row}): " . $e->getMessage();
            }
        }
    }

    /**
     * Importer les changements depuis CHANGE LOG
     */
    protected function importChanges($sheet): void
    {
        $data = $sheet->toArray(null, true, true, true);
        
        for ($row = 3; $row <= count($data); $row++) {
            $rowData = $data[$row];
            $changeCode = trim($rowData['A'] ?? '');
            
            if (empty($changeCode) || !str_starts_with($changeCode, 'CHG-')) {
                continue;
            }
            
            // Vérifier si les données sont vides (lignes template)
            $projectCode = trim($rowData['C'] ?? '');
            if (empty($projectCode)) {
                continue;
            }
            
            $projectId = $this->projectCache[$projectCode] 
                ?? Project::where('project_code', $projectCode)->value('id');
            
            if (!$projectId) {
                $this->errors[] = "Changement {$changeCode}: Projet {$projectCode} non trouvé";
                continue;
            }
            
            try {
                ChangeRequest::updateOrCreate(
                    ['change_code' => $changeCode],
                    [
                        'project_id' => $projectId,
                        'change_type' => $this->normalizeChangeType($rowData['D'] ?? 'Scope Change'),
                        'description' => trim($rowData['E'] ?? ''),
                        'requested_by' => trim($rowData['F'] ?? ''),
                        'approved_by' => !empty(trim($rowData['G'] ?? '')) ? trim($rowData['G']) : null,
                        'status' => $this->normalizeChangeStatus($rowData['H'] ?? 'Pending'),
                        'requested_at' => $this->parseDate($rowData['B'] ?? null),
                    ]
                );
                $this->stats['changes']['created']++;
            } catch (\Exception $e) {
                $this->stats['changes']['errors']++;
                $this->errors[] = "Changement {$changeCode} (ligne {$row}): " . $e->getMessage();
            }
        }
    }

    // ========================================
    // MÉTHODES DE NORMALISATION
    // ========================================
    
    /**
     * Normaliser la priorité
     */
    protected function normalizePriority(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match($value) {
            'high', 'haute', 'élevée', 'critical' => 'High',
            'low', 'basse', 'faible' => 'Low',
            default => 'Medium',
        };
    }
    
    /**
     * Normaliser le statut RAG
     */
    protected function normalizeRagStatus(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match($value) {
            'green', 'vert', 'ok' => 'Green',
            'red', 'rouge', 'critical' => 'Red',
            'amber', 'orange', 'warning', 'yellow' => 'Amber',
            default => 'Green',
        };
    }
    
    /**
     * Normaliser le statut FRS
     */
    protected function normalizeFrsStatus(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        
        if (str_contains($value, 'signoff') || str_contains($value, 'signed') || str_contains($value, 'deployed')) {
            return 'Signoff';
        }
        if (str_contains($value, 'review') || str_contains($value, 'pending signoff')) {
            return 'Review';
        }
        return 'Draft';
    }
    
    /**
     * Normaliser le statut de développement
     */
    protected function normalizeDevStatus(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        
        return match(true) {
            str_contains($value, 'deployed') || str_contains($value, 'live') || str_contains($value, 'production') => 'Deployed',
            str_contains($value, 'uat') => 'UAT',
            str_contains($value, 'testing') || str_contains($value, 'test') => 'Testing',
            str_contains($value, 'development') || str_contains($value, 'dev') || str_contains($value, 'in progress') => 'In Development',
            str_contains($value, 'hold') || str_contains($value, 'pending') || str_contains($value, 'waiting') => 'On Hold',
            default => 'Not Started',
        };
    }
    
    /**
     * Parser le statut d'une phase
     */
    protected function parsePhaseStatus(?string $value): string
    {
        $value = trim($value ?? '');
        return match($value) {
            '✓', '✔', 'Yes', 'Done', 'Complete', 'Completed', 'Y' => 'Completed',
            '⏳', 'In Progress', 'Ongoing', 'WIP' => 'In Progress',
            '⏸', 'On Hold', 'Paused', 'Hold' => 'On Hold',
            '❌', 'Blocked', 'Failed' => 'Blocked',
            default => 'Pending',
        };
    }
    
    /**
     * Normaliser le score de risque
     */
    protected function normalizeRiskScore(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match($value) {
            'critical', 'critique' => 'Critical',
            'high', 'haute', 'élevé' => 'High',
            'low', 'faible', 'basse' => 'Low',
            default => 'Medium',
        };
    }
    
    /**
     * Normaliser le statut de risque
     */
    protected function normalizeRiskStatus(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match($value) {
            'closed', 'fermé', 'resolved' => 'Closed',
            'mitigated', 'atténué' => 'Mitigated',
            'in progress', 'en cours' => 'In Progress',
            default => 'Open',
        };
    }
    
    /**
     * Normaliser le type de changement
     */
    protected function normalizeChangeType(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match(true) {
            str_contains($value, 'schedule') || str_contains($value, 'planning') || str_contains($value, 'date') => 'Schedule Change',
            str_contains($value, 'budget') || str_contains($value, 'cost') => 'Budget Change',
            str_contains($value, 'resource') || str_contains($value, 'team') => 'Resource Change',
            default => 'Scope Change',
        };
    }
    
    /**
     * Normaliser le statut de changement
     */
    protected function normalizeChangeStatus(?string $value): string
    {
        $value = strtolower(trim($value ?? ''));
        return match($value) {
            'approved', 'approuvé' => 'Approved',
            'rejected', 'rejeté', 'refused' => 'Rejected',
            'under review', 'en revue', 'review' => 'Under Review',
            default => 'Pending',
        };
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================
    
    /**
     * Parser une date (format Excel ou texte)
     */
    protected function parseDate($value): ?string
    {
        if (empty($value) || $value === 'TBD' || $value === 'NaN' || $value === 'nan') {
            return null;
        }
        
        // Date Excel (serial number)
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Format "Month YYYY" (ex: "January 2025", "July 2025")
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', trim($value), $matches)) {
            try {
                return Carbon::createFromFormat('F Y', trim($value))->startOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                // Essayer avec le format court
                try {
                    return Carbon::parse(trim($value))->format('Y-m-d');
                } catch (\Exception $e2) {
                    return null;
                }
            }
        }
        
        // Format "DD/MM/YYYY"
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($value))) {
            try {
                return Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Format ISO "YYYY-MM-DD"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return trim($value);
        }
        
        // Tenter un parsing générique
        try {
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Parser target date (peut être "TBD")
     */
    protected function parseTargetDate($value): ?string
    {
        if ($value === 'TBD' || $value === 'Need PO' || $value === 'Completed') {
            return null;
        }
        return $this->parseDate($value);
    }
    
    /**
     * Obtenir ou créer une catégorie
     */
    protected function getOrCreateCategory(string $name): int
    {
        $name = trim($name);
        
        if (empty($name)) {
            $name = 'Uncategorized';
        }
        
        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }
        
        $category = Category::firstOrCreate(
            ['name' => $name],
            [
                'slug' => Str::slug($name),
                'color' => $this->getCategoryColor($name),
            ]
        );
        
        $this->categoryCache[$name] = $category->id;
        return $category->id;
    }
    
    /**
     * Couleurs par défaut pour les catégories
     */
    protected function getCategoryColor(string $name): string
    {
        return match($name) {
            'Integration' => '#3B82F6',
            'Payment Services' => '#10B981',
            'Channel Enhancement' => '#8B5CF6',
            'Financial Services' => '#F59E0B',
            'Security & Compliance' => '#EF4444',
            default => '#6B7280',
        };
    }
    
    /**
     * Obtenir ou créer un owner (équipe)
     */
    protected function getOrCreateOwner(?string $name): ?int
    {
        if (empty($name) || $name === 'TBD') {
            return null;
        }
        
        $name = trim($name);
        
        if (isset($this->ownerCache[$name])) {
            return $this->ownerCache[$name];
        }
        
        // Chercher un utilisateur existant par nom
        $user = User::where('name', $name)->first();
        
        if (!$user) {
            // Créer un utilisateur "équipe" avec un email générique
            $email = Str::slug($name) . '@moov.tg';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => bcrypt(Str::random(16)),
                ]
            );
        }
        
        $this->ownerCache[$name] = $user->id;
        return $user->id;
    }
}
