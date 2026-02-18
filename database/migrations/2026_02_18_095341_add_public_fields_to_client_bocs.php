<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('client_bocs', function (Blueprint $table) {
            if (!Schema::hasColumn('client_bocs', 'is_public')) {
                $table->boolean('is_public')->default(false)->index();
            }

            if (!Schema::hasColumn('client_bocs', 'daily_boc_id')) {
                $table->unsignedBigInteger('daily_boc_id')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_bocs', function (Blueprint $table) {
            //
        });
    }
};
// database/migrations/xxxx_xx_xx_add_public_fields_to_client_bocs.php

