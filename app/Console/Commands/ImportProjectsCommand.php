<?php

namespace App\Console\Commands;

use App\Services\ExcelImportService;
use Illuminate\Console\Command;

class ImportProjectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:import 
                            {file : Chemin vers le fichier Excel à importer}
                            {--preview : Afficher un aperçu sans importer}
                            {--force : Importer sans confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importer les projets depuis un fichier Excel (Moov Project Portfolio)';

    /**
     * Execute the console command.
     */
    public function handle(ExcelImportService $service): int
    {
        $filePath = $this->argument('file');
        
        // Vérifier que le fichier existe
        if (!file_exists($filePath)) {
            $this->error("❌ Fichier non trouvé: {$filePath}");
            return Command::FAILURE;
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'])) {
            $this->error("❌ Format de fichier invalide. Utilisez .xlsx ou .xls");
            return Command::FAILURE;
        }
        
        $this->info("📁 Fichier: {$filePath}");
        $this->newLine();
        
        // Mode preview
        if ($this->option('preview')) {
            return $this->showPreview($service, $filePath);
        }
        
        // Confirmation avant import
        if (!$this->option('force')) {
            $preview = $service->preview($filePath);
            
            $this->info("📊 Aperçu des données à importer:");
            $this->table(
                ['Type', 'Nombre'],
                [
                    ['Projets', $preview['projects']],
                    ['Phases', $preview['phases']],
                    ['Risques', $preview['risks']],
                    ['Changements', $preview['changes']],
                ]
            );
            $this->newLine();
            
            if (!$this->confirm('Voulez-vous procéder à l\'import?')) {
                $this->info('Import annulé.');
                return Command::SUCCESS;
            }
        }
        
        // Exécuter l'import
        $this->info("🔄 Import en cours...");
        $this->newLine();
        
        $result = $service->import($filePath);
        
        if ($result['success']) {
            $this->info('✅ Import terminé avec succès!');
            $this->newLine();
            
            // Afficher les statistiques
            $this->table(
                ['Type', 'Créés', 'Mis à jour', 'Erreurs'],
                [
                    ['Projets', 
                     $result['stats']['projects']['created'], 
                     $result['stats']['projects']['updated'], 
                     $result['stats']['projects']['errors']],
                    ['Phases', 
                     $result['stats']['phases']['created'], 
                     $result['stats']['phases']['updated'], 
                     $result['stats']['phases']['errors']],
                    ['Risques', 
                     $result['stats']['risks']['created'], 
                     $result['stats']['risks']['updated'], 
                     $result['stats']['risks']['errors']],
                    ['Changements', 
                     $result['stats']['changes']['created'], 
                     $result['stats']['changes']['updated'], 
                     $result['stats']['changes']['errors']],
                ]
            );
            
            // Afficher la durée
            if (isset($result['duration'])) {
                $this->newLine();
                $this->info("⏱️  Durée: {$result['duration']} secondes");
            }
            
            // Afficher les avertissements/erreurs
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('⚠️  Avertissements:');
                foreach ($result['errors'] as $error) {
                    $this->line("   • {$error}");
                }
            }
            
            return Command::SUCCESS;
        }
        
        // Échec de l'import
        $this->error('❌ Import échoué: ' . ($result['error'] ?? 'Erreur inconnue'));
        
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('Détails des erreurs:');
            foreach ($result['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }
        
        return Command::FAILURE;
    }
    
    /**
     * Afficher un aperçu sans importer
     */
    protected function showPreview(ExcelImportService $service, string $filePath): int
    {
        $this->info("👁️  Mode aperçu (aucune donnée ne sera importée)");
        $this->newLine();
        
        try {
            $preview = $service->preview($filePath);
            
            // Statistiques générales
            $this->info("📊 Statistiques:");
            $this->table(
                ['Type', 'Nombre'],
                [
                    ['Projets', $preview['projects']],
                    ['Phases', $preview['phases']],
                    ['Risques', $preview['risks']],
                    ['Changements', $preview['changes']],
                ]
            );
            
            // Échantillon de projets
            if (!empty($preview['sampleProjects'])) {
                $this->newLine();
                $this->info("📋 Échantillon de projets (5 premiers):");
                
                $rows = array_map(function ($p) {
                    return [$p['code'], $p['name'], $p['category'], $p['rag']];
                }, $preview['sampleProjects']);
                
                $this->table(
                    ['Code', 'Nom', 'Catégorie', 'RAG'],
                    $rows
                );
            }
            
            $this->newLine();
            $this->info("💡 Pour importer, exécutez la commande sans --preview");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la lecture du fichier: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
