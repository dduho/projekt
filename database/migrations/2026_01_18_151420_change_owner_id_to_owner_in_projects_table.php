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
        Schema::table('projects', function (Blueprint $table) {
            // Supprimer la foreign key et l'index
            $table->dropForeign(['owner_id']);
            $table->dropIndex(['owner_id']);
            
            // Supprimer la colonne owner_id
            $table->dropColumn('owner_id');
            
            // Ajouter la nouvelle colonne owner (texte)
            $table->string('owner', 100)->nullable()->after('blockers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Supprimer la colonne owner
            $table->dropColumn('owner');
            
            // Restaurer owner_id
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('blockers');
            $table->index('owner_id');
        });
    }
};
