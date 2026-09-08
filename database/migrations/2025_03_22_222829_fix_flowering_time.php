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

        Schema::table('seeds', function (Blueprint $table) {
            $table->float('flowering_time')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            // First update any NULL values to a default (0.0)
            \DB::table('seeds')
                ->whereNull('flowering_time')
                ->update(['flowering_time' => 0.0]);

            // Then apply the NOT NULL constraint
            Schema::table('seeds', function (Blueprint $table) {
                $table->float('flowering_time')->nullable(false)->change();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
