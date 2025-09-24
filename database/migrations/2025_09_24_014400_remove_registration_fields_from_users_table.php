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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type',
                'usage_type',
                'team_emails',
                'plants_per_cycle',
                'harvest_products',
                'active',
                'activated_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->nullable()->comment('cultivador_hogareño, growshop, cooperativa, otro');
            $table->string('usage_type')->nullable()->comment('individual, equipo_trabajo');
            $table->text('team_emails')->nullable()->comment('Emails separados por coma para equipo de trabajo');
            $table->integer('plants_per_cycle')->nullable()->comment('Cantidad de plantas por ciclo');
            $table->json('harvest_products')->nullable()->comment('Productos de cosecha en formato JSON');
            $table->boolean('active')->default(false)->comment('Usuario activo/inactivo');
            $table->timestamp('activated_at')->nullable()->comment('Fecha de activación del usuario');
        });
    }
};