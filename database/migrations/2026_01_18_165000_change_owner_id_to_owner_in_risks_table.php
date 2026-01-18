<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère et l'index
            $table->dropForeign(['owner_id']);
            
            // Supprimer la colonne owner_id
            $table->dropColumn('owner_id');
            
            // Ajouter la nouvelle colonne owner en tant que string
            $table->string('owner', 100)->nullable()->after('response_plan_translations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Supprimer la colonne owner
            $table->dropColumn('owner');
            
            // Restaurer owner_id
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('response_plan_translations');
        });
    }
};
