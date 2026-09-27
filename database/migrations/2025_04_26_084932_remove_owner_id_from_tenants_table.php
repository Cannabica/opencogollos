<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // SQLite no soporta DROP CONSTRAINT; dropColumn reconstruye la
            // tabla y elimina la columna (y su FK) sin necesidad del dropForeign.
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['owner_id']);
            }
            $table->dropColumn('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
        });
    }
};
