<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('change_code', 20)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('change_type', ['Scope Change', 'Schedule Change', 'Budget Change', 'Resource Change']);
            $table->text('description');
            $table->text('impact_analysis')->nullable();
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Pending', 'Under Review', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamp('requested_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
