<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_user_tenant', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_user_id');
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('telegram_username')->nullable();
            $table->timestamps();

            $table->index('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_user_tenant');
    }
};