<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_affiliate')->default(false)->after('is_vendor');
            $table->string('affiliate_status', 20)->default('none')->after('is_affiliate');
            // Valeurs : none | pending | active | suspended
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_affiliate', 'affiliate_status']);
        });
    }
};
