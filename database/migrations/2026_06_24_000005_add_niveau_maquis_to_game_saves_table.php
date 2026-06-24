<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->unsignedTinyInteger('niveau_maquis')->default(1)->after('niveau_menu');
        });
    }

    public function down(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->dropColumn('niveau_maquis');
        });
    }
};
