<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Catégorie de navigateur (facebook, instagram, whatsapp, chrome, safari, autre) déduite
        // du User-Agent, sans le stocker : pour comparer l'entonnoir selon la provenance.
        // null = événement antérieur au 04/11/2026.
        Schema::table('akwaba_events', function (Blueprint $table) {
            $table->string('browser', 20)->nullable()->after('session_hash');
            $table->index(['browser', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('akwaba_events', function (Blueprint $table) {
            $table->dropIndex(['browser', 'type']);
            $table->dropColumn('browser');
        });
    }
};
