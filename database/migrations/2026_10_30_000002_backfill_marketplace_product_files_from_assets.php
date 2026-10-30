<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Reprend le fichier unique de chaque livre/logiciel (marketplace_assets kind=file) dans
     * marketplace_product_files. Ne déplace ni ne supprime rien : le fichier physique reste sur
     * le disque public et la ligne marketplace_assets est conservée (rétrocompatibilité).
     * Les vidéos sont exclues : leur .mp4 est la source Cloudflare, jamais téléchargeable.
     * Idempotent : un asset déjà repris (legacy_asset_id) est ignoré.
     */
    public function up(): void
    {
        $disk = 'public';

        $assets = DB::table('marketplace_assets as a')
            ->join('marketplace_products as p', 'p.id', '=', 'a.product_id')
            ->where('a.kind', 'file')
            ->where('a.is_downloadable', true)
            ->whereNotNull('a.path')
            ->whereIn('p.type', ['book', 'software'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('marketplace_product_files as f')
                    ->whereColumn('f.legacy_asset_id', 'a.id');
            })
            ->orderBy('a.id')
            ->get(['a.id', 'a.product_id', 'a.path', 'a.label', 'a.created_at', 'a.updated_at', 'p.slug', 'p.type']);

        foreach ($assets as $asset) {
            $ext    = strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
            $exists = Storage::disk($disk)->exists($asset->path);

            if (!$exists) {
                Log::warning('Backfill product_files : fichier physique absent', ['asset_id' => $asset->id, 'path' => $asset->path]);
            }

            $mime = null;
            if ($exists) {
                try {
                    $mime = Storage::disk($disk)->mimeType($asset->path) ?: null;
                } catch (\Throwable $e) {
                    $mime = null;
                }
            }

            DB::table('marketplace_product_files')->insert([
                'product_id'      => $asset->product_id,
                'legacy_asset_id' => $asset->id,
                'label'           => $asset->label ?: ($asset->type === 'book' ? 'PDF' : 'Logiciel'),
                'platform'        => null,
                // Même nom que celui servi jusqu'ici par my.products.download
                'original_name'   => $asset->slug . ($ext !== '' ? '.' . $ext : ''),
                'disk'            => $disk,
                'path'            => $asset->path,
                'size'            => $exists ? Storage::disk($disk)->size($asset->path) : null,
                'mime'            => $mime,
                'sort_order'      => 0,
                'download_count'  => 0,
                'created_at'      => $asset->created_at ?? now(),
                'updated_at'      => $asset->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Retire uniquement les lignes reprises ; aucun fichier physique n'est touché.
     */
    public function down(): void
    {
        DB::table('marketplace_product_files')->whereNotNull('legacy_asset_id')->delete();
    }
};
