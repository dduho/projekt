<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('risk_code', 20)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['Risk', 'Issue'])->default('Risk');
            $table->text('description');
            $table->enum('impact', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('probability', ['Low', 'Medium', 'High'])->default('Medium');
            $table->enum('risk_score', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->text('mitigation_plan')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Open', 'In Progress', 'Mitigated', 'Closed'])->default('Open');
            $table->timestamps();

            $table->index(['status', 'risk_score']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
