<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PortfolioReportExport implements WithMultipleSheets
{
    public function __construct(protected array $reportData)
    {
    }

    public function sheets(): array
    {
        // Summary Sheet
        $summarySheet = new class($this->reportData['summary']) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $summary)
            {
            }

            public function array(): array
            {
                return [
                    ['Total projets', $this->summary['total_projects']],
                    ['Projets Verts', $this->summary['green_projects']],
                    ['Projets Orange', $this->summary['amber_projects']],
                    ['Projets Rouges', $this->summary['red_projects']],
                    ['Complétion Moyenne', round($this->summary['avg_completion'], 2) . '%'],
                    ['Total Risques', $this->summary['total_risks']],
                    ['Risques Hauts', $this->summary['high_risk_count']],
                ];
            }

            public function headings(): array
            {
                return ['Métrique', 'Valeur'];
            }

            public function title(): string
            {
                return 'Résumé';
            }
        };

        // Projects Sheet
        $projectsSheet = new class($this->reportData['projects']) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $projects)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->projects as $project) {
                    $data[] = [
                        $project['name'],
                        $project['code'],
                        ucfirst($project['status']),
                        strtoupper($project['rag_status']),
                        $project['completion'] . '%',
                        $project['risks'],
                        $project['changes'],
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Projet', 'Code', 'Statut', 'RAG', 'Complétion %', 'Risques', 'Changements'];
            }

            public function title(): string
            {
                return 'Projets';
            }
        };

        // By Category Sheet
        $categorySheet = new class($this->reportData['by_category'] ?? []) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $byCategory)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->byCategory as $category => $stats) {
                    $data[] = [
                        $category,
                        $stats['total'] ?? 0,
                        $stats['green'] ?? 0,
                        $stats['amber'] ?? 0,
                        $stats['red'] ?? 0,
                        isset($stats['avg_completion']) ? round($stats['avg_completion'], 2) . '%' : 'N/A',
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Catégorie', 'Total', 'Verts', 'Orange', 'Rouges', 'Complétion Moy'];
            }

            public function title(): string
            {
                return 'Par Catégorie';
            }
        };

        // By Status Sheet
        $statusSheet = new class($this->reportData['by_status'] ?? []) implements FromArray, WithHeadings, WithTitle {
            public function __construct(protected array $byStatus)
            {
            }

            public function array(): array
            {
                $data = [];
                foreach ($this->byStatus as $status => $count) {
                    $data[] = [
                        ucfirst($status),
                        $count,
                    ];
                }
                return $data;
            }

            public function headings(): array
            {
                return ['Statut de Développement', 'Nombre'];
            }

            public function title(): string
            {
                return 'Par Statut';
            }
        };
        
        return [
            $summarySheet,
            $projectsSheet,
            $categorySheet,
            $statusSheet,
        ];
    }
}
