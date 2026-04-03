<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_financials', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            $table->string('client_email')->nullable()->after('user_id');
            $table->timestamp('notified_at')->nullable()->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_financials', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'client_email', 'notified_at']);
        });
    }
};
