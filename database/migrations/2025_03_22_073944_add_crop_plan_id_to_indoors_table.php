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
        Schema::table('indoors', function (Blueprint $table) {
            $table->foreignId('crop_plan_id')
                ->nullable()
                ->constrained('crop_plans')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indoors', function (Blueprint $table) {
            $table->dropForeign(['crop_plan_id']);
            $table->dropColumn('crop_plan_id');
        });
    }
};
