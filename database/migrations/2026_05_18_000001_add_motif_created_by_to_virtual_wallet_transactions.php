<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores enums as TEXT so new type values (topup_admin, transfer_in, transfer_out)
        // need no column type change — just add the two new columns.
        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            $table->string('motif')->nullable()->after('meta');
            $table->unsignedBigInteger('created_by')->nullable()->after('motif');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['motif', 'created_by']);
        });
    }
};
