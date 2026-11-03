<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Akwaba passe en ligne : publié, avec sa couverture versionnée dans le dépôt
     * (public/img/jeux/akwaba-partage.jpg, 1200×630, aussi utilisée pour le partage).
     */
    public function up(): void
    {
        DB::table('marketplace_products')->where('slug', 'akwaba')->update([
            'status'           => 'published',
            'cover_image_path' => 'img/jeux/akwaba-partage.jpg',
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('marketplace_products')->where('slug', 'akwaba')->update([
            'status'           => 'draft',
            'cover_image_path' => null,
            'updated_at'       => now(),
        ]);
    }
};
