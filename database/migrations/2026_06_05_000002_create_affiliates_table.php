<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('code', 20)->unique();
            $table->boolean('custom_code')->default(false);

            // none | pending | active | suspended
            $table->string('status', 20)->default('pending');

            $table->unsignedBigInteger('click_count')->default(0);

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();

            $table->string('admin_note', 500)->nullable();

            $table->timestamps();

            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
