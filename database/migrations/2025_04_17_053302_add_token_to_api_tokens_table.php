<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->text('token')->nullable()->after('token_hash');
        });
    }

    public function down()
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};