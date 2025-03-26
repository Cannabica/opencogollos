<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('seeds', function (Blueprint $table) {
            $table->boolean('aprobado_inase')->default(false);
            $table->string('provider')->nullable();
        });
    }

    public function down()
    {
        Schema::table('seeds', function (Blueprint $table) {
            $table->dropColumn(['aprobado_inase', 'provider']);
        });
    }
}; 