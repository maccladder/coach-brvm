<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Erreurs JavaScript remontées par le jeu (message postMessage « error »). Ni IP, ni
        // compte, ni session : message tronqué, position, catégorie de navigateur, date.
        Schema::create('akwaba_errors', function (Blueprint $table) {
            $table->id();
            $table->string('msg', 200);
            $table->unsignedInteger('line')->nullable();
            $table->unsignedInteger('col')->nullable();
            $table->string('browser', 20);
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akwaba_errors');
    }
};
