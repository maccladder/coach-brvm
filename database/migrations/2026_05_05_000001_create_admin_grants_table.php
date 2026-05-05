<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_grants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphique : App\Models\Course, App\Models\MarketplaceProduct,
            // App\Models\Document, ou la valeur spéciale 'lettreci' (grantable_id = 0)
            $table->string('grantable_type');
            $table->unsignedBigInteger('grantable_id');

            $table->text('reason')->nullable();

            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('granted_at');

            $table->timestamp('expires_at')->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->text('revoke_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'grantable_type', 'grantable_id'], 'grants_user_grantable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_grants');
    }
};
