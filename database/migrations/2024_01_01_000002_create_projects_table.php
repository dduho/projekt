<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('business_area', 100)->nullable();
            $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->enum('frs_status', ['Draft', 'Review', 'Signoff'])->default('Draft');
            $table->enum('dev_status', [
                'Not Started',
                'In Development',
                'Testing',
                'UAT',
                'Deployed'
            ])->default('Not Started');
            $table->string('current_progress', 100)->nullable();
            $table->text('blockers')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('planned_release', 50)->nullable();
            $table->date('target_date')->nullable();
            $table->date('submission_date')->nullable();
            $table->enum('rag_status', ['Green', 'Amber', 'Red'])->default('Green');
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['rag_status', 'priority']);
            $table->index('category_id');
            $table->index('owner_id');
            $table->index('dev_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
