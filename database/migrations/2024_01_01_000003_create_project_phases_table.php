<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('phase', ['FRS', 'Development', 'Testing', 'UAT', 'Deployment']);
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Blocked'])->default('Pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
