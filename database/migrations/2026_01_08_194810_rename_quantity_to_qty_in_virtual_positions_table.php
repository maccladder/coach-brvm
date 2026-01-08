<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_positions', function (Blueprint $table) {
            $table->renameColumn('quantity', 'qty');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_positions', function (Blueprint $table) {
            $table->renameColumn('qty', 'quantity');
        });
    }
};
