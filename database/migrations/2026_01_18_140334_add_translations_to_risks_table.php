<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->json('description_translations')->nullable()->after('description');
            $table->json('mitigation_plan_translations')->nullable()->after('mitigation_plan');
            $table->json('response_plan_translations')->nullable()->after('response_plan');
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn(['description_translations', 'mitigation_plan_translations', 'response_plan_translations']);
        });
    }
};
