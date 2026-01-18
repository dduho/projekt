<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->json('description_translations')->nullable()->after('description');
            $table->json('impact_analysis_translations')->nullable()->after('impact_analysis');
            $table->json('implementation_plan_translations')->nullable()->after('implementation_plan');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['description_translations', 'impact_analysis_translations', 'implementation_plan_translations']);
        });
    }
};
