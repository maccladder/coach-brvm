<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();

            // optionnel: pièce jointe (image/pdf)
            $table->string('attachment_path')->nullable(); // storage/app/public/announcements/...
            $table->string('attachment_type')->nullable(); // image|pdf|null

            // publication
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
