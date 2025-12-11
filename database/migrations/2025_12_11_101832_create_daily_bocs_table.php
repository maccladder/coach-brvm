<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_daily_bocs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_bocs', function (Blueprint $table) {
            $table->id();
            $table->date('date_boc')->unique();      // 1 BOC par date
            $table->string('file_path');             // chemin du fichier
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_bocs');
    }
};
