<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Auteur d'un mouvement manuel (ajout d'indices, rattrapage d'un pack) : le code admin est
        // partagé, on garde donc le compte connecté s'il y en a un et un libellé lisible.
        Schema::table('game_credit_ledger', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->after('note')->constrained('users')->nullOnDelete();
            $table->string('admin_label', 120)->nullable()->after('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_credit_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_user_id');
            $table->dropColumn('admin_label');
        });
    }
};
