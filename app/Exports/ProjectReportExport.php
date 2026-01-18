<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectReportExport implements WithMultipleSheets
{
    public function __construct(protected array $reportData)
    {
    }

    public function sheets(): array
    {
        // Overview Sheet
        $overviewSheet = new class($this->reportData) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $reportData)
            {
            }

            public function array(): array
            {
                $project = $this->reportData['project'];
                $overview = $this->reportData['overview'];

                return [
                    ['INFORMATIONS PROJET'],
                    ['Nom du projet', $project['name']],
                    ['Code', $project['code']],
                    ['Description', $project['description'] ?? ''],
                    ['Catégorie', $project['category']],
                    ['Statut', ucfirst($project['status'])],
                    ['RAG Status', strtoupper($project['rag_status'])],
                    ['Complétion', $project['completion'] . '%'],
                    ['Date cible', $project['target_date'] ?? 'N/A'],
                    ['Date soumission', $project['submission_date'] ?? 'N/A'],
                    [],
                    ['OVERVIEW'],
                    ['Phases complétées', $overview['completed_phases'] . ' / ' . $overview['total_phases']],
                    ['Risques', $overview['total_risks'] . ' (Hauts: ' . $overview['high_risks'] . ')'],
                    ['Changements', $overview['total_changes'] . ' (En attente: ' . $overview['pending_changes'] . ')'],
                ];
            }

            public function headings(): array
            {
                return ['Champ', 'Valeur'];
            }

            public function title(): string
            {
                return 'Vue d\'ensemble';
            }
        };

        // Phases Sheet
        $phasesSheet = new class($this->reportData['phases'] ?? []) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $phases)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->phases as $phase) {
                    $data[] = [
                        $phase['name'],
                        $phase['description'] ?? '',
                        ucfirst($phase['status']),
                        $phase['start_date'] ?? 'N/A',
                        $phase['end_date'] ?? 'N/A',
                        $phase['tasks'] ?? 0,
                        $phase['completed_tasks'] ?? 0,
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Phase', 'Description', 'Statut', 'Date début', 'Date fin', 'Tâches', 'Complétées'];
            }

            public function title(): string
            {
                return 'Phases';
            }
        };

        // Risks Sheet
        $risksSheet = new class($this->reportData['risks'] ?? []) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $risks)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->risks as $risk) {
                    $data[] = [
                        $risk['code'],
                        $risk['description'],
                        $risk['score'],
                        ucfirst($risk['status']),
                        $risk['mitigation'] ?? '',
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Code', 'Description', 'Score', 'Statut', 'Mitigation'];
            }

            public function title(): string
            {
                return 'Risques';
            }
        };

        // Changes Sheet
        $changesSheet = new class($this->reportData['changes'] ?? []) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $changes)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->changes as $change) {
                    $data[] = [
                        $change['title'],
                        $change['description'] ?? '',
                        ucfirst($change['status']),
                        $change['impact_level'] ?? 'N/A',
                        $change['requested_by'] ?? 'N/A',
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Titre', 'Description', 'Statut', 'Impact', 'Demandeur'];
            }

            public function title(): string
            {
                return 'Changements';
            }
        };

        return [
            $overviewSheet,
            $phasesSheet,
            $risksSheet,
            $changesSheet,
        ];
    }
}
 
