<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_bocs', function (Blueprint $table) {
            $table->string('previous_file_path')->nullable()->after('file_path');
            $table->timestamp('replaced_at')->nullable()->after('previous_file_path');
            $table->unsignedBigInteger('replaced_by')->nullable()->after('replaced_at');
        });
    }

    public function down(): void
    {
        Schema::table('daily_bocs', function (Blueprint $table) {
            $table->dropColumn([
                'previous_file_path',
                'replaced_at',
                'replaced_by',
            ]);
        });
    }
};
