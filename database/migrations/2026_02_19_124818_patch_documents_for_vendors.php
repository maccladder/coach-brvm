<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('documents', 'status')) {
                $table->string('status', 20)->default('published')->after('file_path');
                // published (admin) / pending (vendor) / rejected
            }

            if (!Schema::hasColumn('documents', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('documents', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('documents', 'rejected_note')) {
                $table->string('rejected_note', 500)->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('documents', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }

            $drop = [];
            foreach (['status','approved_at','rejected_note'] as $col) {
                if (Schema::hasColumn('documents', $col)) $drop[] = $col;
            }
            if ($drop) $table->dropColumn($drop);
        });
    }
};
