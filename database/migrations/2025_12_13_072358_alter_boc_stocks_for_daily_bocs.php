<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('boc_stocks', function (Blueprint $table) {

            // 1) drop FK + index/unique sur client_boc_id
            // ⚠️ Laravel devine les noms, sinon on force (voir note plus bas)
            $table->dropForeign(['client_boc_id']);
            $table->dropUnique(['client_boc_id', 'ticker']);

            // 2) renommer colonnes
            $table->renameColumn('client_boc_id', 'daily_boc_id');
            $table->renameColumn('boc_date', 'date_boc');

            // 3) recréer contraintes correctes
            $table->unique(['daily_boc_id', 'ticker']);
            $table->index(['ticker', 'date_boc']);

            $table->foreign('daily_boc_id')
                ->references('id')
                ->on('daily_bocs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('boc_stocks', function (Blueprint $table) {

            $table->dropForeign(['daily_boc_id']);
            $table->dropUnique(['daily_boc_id', 'ticker']);

            $table->renameColumn('daily_boc_id', 'client_boc_id');
            $table->renameColumn('date_boc', 'boc_date');

            $table->unique(['client_boc_id', 'ticker']);

            $table->foreign('client_boc_id')
                ->references('id')
                ->on('client_bocs')
                ->onDelete('cascade');
        });
    }
};
