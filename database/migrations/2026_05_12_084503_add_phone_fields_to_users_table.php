<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('phone_reward_claimed')->default(false)->after('phone_verified_at');

            $table->index('phone', 'users_phone_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_phone_index');
            $table->dropColumn(['phone', 'phone_verified_at', 'phone_reward_claimed']);
        });
    }
};
