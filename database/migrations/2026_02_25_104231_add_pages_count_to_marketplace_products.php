<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_pages_count_to_marketplace_products.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->unsignedInteger('pages_count')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn('pages_count');
        });
    }
};
