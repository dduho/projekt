<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Category;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\ProjectPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ExcelImportService
{
    private array $stats = [
        'projects' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'risks' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'changes' => ['created' => 0, 'updated' => 0, 'errors' => 0],
    ];

    private array $errors = [];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        DB::beginTransaction();

        try {
            $this->importProjectRegister($spreadsheet->getSheetByName('PROJECT REGISTER'));
            $this->importStatusTracking($spreadsheet->getSheetByName('STATUS TRACKING'));
            $this->importRisks($spreadsheet->getSheetByName('RISK & ISSUES LOG'));
            $this->importChanges($spreadsheet->getSheetByName('CHANGE LOG'));

            DB::commit();

            return [
                'success' => true,
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        }
    }

    public function validate(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheets = [];

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $rowCount = $sheet->getHighestRow();
                $sheets[$sheetName] = [
                    'exists' => true,
                    'rows' => $rowCount - 1,
                ];
            }

            $requiredSheets = ['PROJECT REGISTER', 'STATUS TRACKING', 'RISK & ISSUES LOG', 'CHANGE LOG'];
            $missingSheets = [];

            foreach ($requiredSheets as $required) {
                if (!isset($sheets[$required])) {
                    $missingSheets[] = $required;
                    $sheets[$required] = ['exists' => false, 'rows' => 0];
                }
            }

            return [
                'valid' => empty($missingSheets),
                'sheets' => $sheets,
                'missing_sheets' => $missingSheets,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function importProjectRegister($sheet): void
    {
        if (!$sheet) return;

        $rows = $sheet->toArray();
        $headers = null;

        foreach ($rows as $index => $row) {
            if ($headers === null && isset($row[0]) && strtoupper(trim($row[0])) === 'ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }

            if ($headers === null || empty($row[0])) {
                continue;
            }

            $projectCode = trim($row[0]);
            if (!preg_match('/^[A-Z]+-\d+$/', $projectCode)) {
                continue;
            }

            try {
                $data = $this->mapProjectData($row, $headers);
                $this->upsertProject($data);
            } catch (\Exception $e) {
                $this->errors[] = "Projet ligne {$index}: " . $e->getMessage();
                $this->stats['projects']['errors']++;
            }
        }
    }

    private function mapProjectData(array $row, array $headers): array
    {
        return [
            'project_code' => trim($row[$headers['id'] ?? 0] ?? ''),
            'name' => trim($row[$headers['project_name'] ?? $headers['name'] ?? 1] ?? ''),
            'submission_date' => $this->parseDate($row[$headers['submission_date'] ?? $headers['date'] ?? null] ?? null),
            'category_name' => trim($row[$headers['category'] ?? null] ?? 'General'),
            'business_area' => trim($row[$headers['business_area'] ?? null] ?? ''),
            'priority' => $this->normalizePriority($row[$headers['priority'] ?? null] ?? 'Medium'),
            'description' => trim($row[$headers['description'] ?? null] ?? ''),
            'planned_release' => trim($row[$headers['planned_release'] ?? null] ?? ''),
            'frs_status' => $this->normalizeFrsStatus($row[$headers['frs_status'] ?? $headers['frs'] ?? null] ?? 'Draft'),
            'dev_status' => $this->normalizeDevStatus($row[$headers['development_status'] ?? $headers['dev_status'] ?? $headers['status'] ?? null] ?? 'Not Started'),
            'current_progress' => trim($row[$headers['current_progress'] ?? $headers['progress'] ?? null] ?? ''),
            'blockers' => trim($row[$headers['blockers'] ?? null] ?? ''),
            'target_date' => $this->parseDate($row[$headers['target_date'] ?? null] ?? null),
            'rag_status' => $this->normalizeRagStatus($row[$headers['rag_status'] ?? $headers['rag'] ?? null] ?? 'Green'),
            'service_type' => trim($row[$headers['service_type'] ?? null] ?? ''),
            'remarks' => trim($row[$headers['remarks'] ?? $headers['notes'] ?? null] ?? ''),
        ];
    }

    private function upsertProject(array $data): Project
    {
        $categoryName = $data['category_name'] ?: 'General';
        unset($data['category_name']);

        $category = Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => Str::slug($categoryName),
                'color' => $this->generateCategoryColor($categoryName),
            ]
        );

        $data['category_id'] = $category->id;
        $data['last_update'] = now();

        $data = array_filter($data, fn($value) => $value !== '' && $value !== null);

        $project = Project::where('project_code', $data['project_code'])->first();

        if ($project) {
            $project->update($data);
            $this->stats['projects']['updated']++;
        } else {
            $project = Project::create($data);
            $this->stats['projects']['created']++;
        }

        return $project;
    }

    private function importStatusTracking($sheet): void
    {
        if (!$sheet) return;

        $rows = $sheet->toArray();
        $headers = null;

        foreach ($rows as $row) {
            if ($headers === null && isset($row[0]) && str_contains(strtolower($row[0]), 'project')) {
                $headers = $this->mapHeaders($row);
                continue;
            }

            if ($headers === null || empty($row[0])) {
                continue;
            }

            $projectCode = trim($row[0]);
            $project = Project::where('project_code', $projectCode)->first();

            if (!$project) continue;

            $phases = ['FRS', 'Development', 'Testing', 'UAT', 'Deployment'];
            foreach ($phases as $phase) {
                $phaseKey = strtolower($phase);
                if (!isset($headers[$phaseKey])) continue;

                $status = $this->parsePhaseStatus($row[$headers[$phaseKey]] ?? null);

                ProjectPhase::updateOrCreate(
                    ['project_id' => $project->id, 'phase' => $phase],
                    [
                        'status' => $status,
                        'completed_at' => $status === 'Completed' ? now() : null,
                    ]
                );
            }

            if (isset($headers['completion_%']) || isset($headers['completion'])) {
                $completionKey = $headers['completion_%'] ?? $headers['completion'] ?? null;
                if ($completionKey !== null) {
                    $completion = intval(preg_replace('/[^0-9]/', '', $row[$completionKey] ?? 0));
                    $project->update(['completion_percent' => min(100, max(0, $completion))]);
                }
            }
        }
    }

    private function importRisks($sheet): void
    {
        if (!$sheet) return;

        $rows = $sheet->toArray();
        $headers = null;

        foreach ($rows as $index => $row) {
            if ($headers === null && isset($row[0]) && strtoupper(trim($row[0])) === 'ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }

            if ($headers === null || empty($row[0])) {
                continue;
            }

            $riskCode = trim($row[0]);
            if (!preg_match('/^RISK-\d+$/', $riskCode)) {
                continue;
            }

            try {
                $projectCode = trim($row[$headers['related_project'] ?? $headers['project'] ?? $headers['project_id'] ?? 1] ?? '');
                $project = Project::where('project_code', $projectCode)->first();

                if (!$project) {
                    throw new \Exception("Projet {$projectCode} non trouvé");
                }

                Risk::updateOrCreate(
                    ['risk_code' => $riskCode],
                    [
                        'project_id' => $project->id,
                        'type' => $this->normalizeRiskType($row[$headers['type'] ?? null] ?? 'Risk'),
                        'description' => trim($row[$headers['description'] ?? 2] ?? ''),
                        'impact' => $this->normalizeImpact($row[$headers['impact'] ?? null] ?? 'Medium'),
                        'probability' => $this->normalizeProbability($row[$headers['probability'] ?? null] ?? 'Medium'),
                        'mitigation_plan' => trim($row[$headers['mitigation_plan'] ?? $headers['mitigation'] ?? null] ?? ''),
                        'status' => $this->normalizeRiskStatus($row[$headers['status'] ?? null] ?? 'Open'),
                    ]
                );

                $this->stats['risks']['created']++;
            } catch (\Exception $e) {
                $this->errors[] = "Risque ligne {$index}: " . $e->getMessage();
                $this->stats['risks']['errors']++;
            }
        }
    }

    private function importChanges($sheet): void
    {
        if (!$sheet) return;

        $rows = $sheet->toArray();
        $headers = null;

        foreach ($rows as $index => $row) {
            if ($headers === null && isset($row[0]) && str_contains(strtolower($row[0]), 'change')) {
                $headers = $this->mapHeaders($row);
                continue;
            }

            if ($headers === null || empty($row[0])) {
                continue;
            }

            $changeCode = trim($row[0]);
            if (!preg_match('/^CHG-\d+$/', $changeCode)) {
                continue;
            }

            try {
                $projectCode = trim($row[$headers['project_id'] ?? $headers['project'] ?? 1] ?? '');
                $project = Project::where('project_code', $projectCode)->first();

                if (!$project) {
                    throw new \Exception("Projet {$projectCode} non trouvé");
                }

                ChangeRequest::updateOrCreate(
                    ['change_code' => $changeCode],
                    [
                        'project_id' => $project->id,
                        'change_type' => $this->normalizeChangeType($row[$headers['change_type'] ?? $headers['type'] ?? null] ?? 'Scope Change'),
                        'description' => trim($row[$headers['description'] ?? 2] ?? ''),
                        'requested_by_id' => 1,
                        'status' => $this->normalizeChangeStatus($row[$headers['status'] ?? null] ?? 'Pending'),
                        'requested_at' => $this->parseDate($row[$headers['date'] ?? $headers['requested_at'] ?? null] ?? null) ?? now(),
                    ]
                );

                $this->stats['changes']['created']++;
            } catch (\Exception $e) {
                $this->errors[] = "Changement ligne {$index}: " . $e->getMessage();
                $this->stats['changes']['errors']++;
            }
        }
    }

    private function mapHeaders(array $row): array
    {
        $mapping = [];
        foreach ($row as $index => $header) {
            if (empty($header)) continue;
            $key = strtolower(trim($header));
            $key = preg_replace('/[^a-z0-9_]/', '_', $key);
            $key = preg_replace('/_+/', '_', $key);
            $key = trim($key, '_');
            $mapping[$key] = $index;
        }
        return $mapping;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizePriority(string $value): string
    {
        $map = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low', 'haute' => 'High', 'moyenne' => 'Medium', 'basse' => 'Low'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeRagStatus(string $value): string
    {
        $map = ['green' => 'Green', 'amber' => 'Amber', 'red' => 'Red', 'vert' => 'Green', 'orange' => 'Amber', 'rouge' => 'Red'];
        return $map[strtolower(trim($value))] ?? 'Green';
    }

    private function normalizeFrsStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'sign') || str_contains($val, 'valid')) return 'Signoff';
        if (str_contains($val, 'review') || str_contains($val, 'revue')) return 'Review';
        return 'Draft';
    }

    private function normalizeDevStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'deploy') || str_contains($val, 'prod') || str_contains($val, 'live')) return 'Deployed';
        if (str_contains($val, 'uat') || str_contains($val, 'accept')) return 'UAT';
        if (str_contains($val, 'test')) return 'Testing';
        if (str_contains($val, 'develop') || str_contains($val, 'progress') || str_contains($val, 'cours')) return 'In Development';
        if (str_contains($val, 'hold') || str_contains($val, 'pause') || str_contains($val, 'attente')) return 'On Hold';
        return 'Not Started';
    }

    private function parsePhaseStatus(?string $value): string
    {
        if (empty($value)) return 'Pending';
        $val = strtolower(trim($value));
        if ($val === '✓' || $val === 'x' || str_contains($val, 'complet') || str_contains($val, 'done') || str_contains($val, 'terminé')) return 'Completed';
        if ($val === '-' || str_contains($val, 'pending') || str_contains($val, 'attente')) return 'Pending';
        if (str_contains($val, 'block') || str_contains($val, 'bloqué')) return 'Blocked';
        if (str_contains($val, 'progress') || str_contains($val, 'cours')) return 'In Progress';
        return 'Pending';
    }

    private function normalizeRiskType(string $value): string
    {
        return strtolower(trim($value)) === 'issue' ? 'Issue' : 'Risk';
    }

    private function normalizeImpact(string $value): string
    {
        $map = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical', 'faible' => 'Low', 'moyen' => 'Medium', 'eleve' => 'High', 'critique' => 'Critical'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeProbability(string $value): string
    {
        $map = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'faible' => 'Low', 'moyen' => 'Medium', 'eleve' => 'High'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeRiskStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'closed') || str_contains($val, 'fermé')) return 'Closed';
        if (str_contains($val, 'mitigat') || str_contains($val, 'atténu')) return 'Mitigated';
        if (str_contains($val, 'progress') || str_contains($val, 'cours')) return 'In Progress';
        return 'Open';
    }

    private function normalizeChangeType(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'schedule') || str_contains($val, 'délai') || str_contains($val, 'planning')) return 'Schedule Change';
        if (str_contains($val, 'budget') || str_contains($val, 'cost') || str_contains($val, 'coût')) return 'Budget Change';
        if (str_contains($val, 'resource') || str_contains($val, 'ressource')) return 'Resource Change';
        return 'Scope Change';
    }

    private function normalizeChangeStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'approved') || str_contains($val, 'approuvé') || str_contains($val, 'accepté')) return 'Approved';
        if (str_contains($val, 'reject') || str_contains($val, 'refusé')) return 'Rejected';
        if (str_contains($val, 'review') || str_contains($val, 'revue') || str_contains($val, 'analyse')) return 'Under Review';
        return 'Pending';
    }

    private function generateCategoryColor(string $name): string
    {
        $colors = ['#5C6BC0', '#26A69A', '#FF7043', '#AB47BC', '#42A5F5', '#66BB6A', '#FFA726', '#EC407A'];
        $index = crc32($name) % count($colors);
        return $colors[$index];
    }
}
