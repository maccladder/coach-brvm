<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_bocs', function (Blueprint $table) {
            $table->integer('amount')->default(0);
            // $table->string('status')->default('pending'); // pending / paid / failed
            $table->string('transaction_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('client_bocs', function (Blueprint $table) {
            $table->dropColumn(['amount', 'status', 'transaction_id']);
        });
    }
};
