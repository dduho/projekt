<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            // État du projet à ce moment
            $table->string('dev_status')->nullable(); // En cours, Suspendu, Terminé, etc
            $table->string('rag_status')->default('green'); // green, amber, red
            $table->decimal('completion_percent', 5, 2)->default(0); // 0-100
            
            // Métriques
            $table->integer('active_risks_count')->default(0);
            $table->integer('pending_changes_count')->default(0);
            $table->integer('completed_phases_count')->default(0);
            $table->integer('total_phases_count')->default(0);
            
            // Dates
            $table->date('snapshot_date'); // Date du snapshot (généralement aujourd'hui)
            $table->timestamps();
            
            // Index pour requêtes rapides
            $table->index(['project_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_snapshots');
    }
};
