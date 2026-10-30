<?php

namespace App\Services\Marketplace;

use App\Mail\ProductFilesAddedMail;
use App\Models\AdminGrant;
use App\Models\MarketplaceAsset;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePurchase;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fichiers téléchargeables d'un produit (marketplace_product_files).
 * Stockage toujours sur le disque privé ; l'accès se vérifie au niveau du produit.
 */
class ProductFileService
{
    /** Types de produits dont les fichiers sont téléchargeables par l'acheteur. */
    public const DOWNLOADABLE_TYPES = ['book', 'software'];

    public function disk(): string
    {
        return config('marketplace.files_disk', 'local');
    }

    /** Stocke un upload sur le disque privé : [disk, path]. */
    public function storeUpload(MarketplaceProduct $product, UploadedFile $upload): array
    {
        $ext  = strtolower($upload->getClientOriginalExtension() ?: ($upload->guessExtension() ?: 'bin'));
        $disk = $this->disk();
        $path = Storage::disk($disk)->putFileAs(
            "marketplace/product-files/{$product->id}",
            $upload,
            Str::random(40) . '.' . $ext
        );

        return [$disk, $path];
    }

    public function addFile(MarketplaceProduct $product, UploadedFile $upload, ?string $label, ?string $platform): ProductFile
    {
        [$disk, $path] = $this->storeUpload($product, $upload);

        $nextOrder = (int) $product->files()->max('sort_order') + ($product->files()->exists() ? 1 : 0);

        return $product->files()->create([
            'label'         => $this->cleanLabel($label) ?? $this->defaultLabel($upload->getClientOriginalName(), $platform),
            'platform'      => $platform,
            'original_name' => $this->cleanOriginalName($upload->getClientOriginalName()),
            'disk'          => $disk,
            'path'          => $path,
            'size'          => $upload->getSize(),
            'mime'          => $upload->getClientMimeType() ?: $upload->getMimeType(),
            'sort_order'    => $nextOrder,
        ]);
    }

    /**
     * Plateforme devinée depuis l'extension (pré-remplissage quand le formulaire n'en fournit pas).
     */
    public function guessPlatform(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'exe', 'msi'  => 'windows',
            'dmg', 'pkg'  => 'macos',
            'apk'         => 'android',
            default       => null,
        };
    }

    /**
     * Répercute le fichier unique d'un livre/logiciel (ancien formulaire) sur sa ligne
     * marketplace_product_files. Crée la ligne si besoin, la retire si l'asset n'est plus
     * téléchargeable (ex. produit passé en vidéo).
     */
    public function syncFromLegacyAsset(MarketplaceAsset $asset, ?string $originalName = null): ?ProductFile
    {
        $asset->loadMissing('product');
        $product = $asset->product;
        $file    = ProductFile::where('legacy_asset_id', $asset->id)->first();

        $eligible = $product
            && $asset->kind === 'file'
            && $asset->path
            && $asset->is_downloadable
            && in_array($product->type, self::DOWNLOADABLE_TYPES, true);

        if (!$eligible) {
            $file?->delete();   // la ligne seulement : le fichier physique appartient encore à l'asset
            return null;
        }

        $disk = $asset->disk ?: 'public';
        $ext  = strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
        $data = [
            'disk'          => $disk,
            'path'          => $asset->path,
            'size'          => Storage::disk($disk)->exists($asset->path) ? Storage::disk($disk)->size($asset->path) : null,
            'mime'          => Storage::disk($disk)->exists($asset->path) ? (Storage::disk($disk)->mimeType($asset->path) ?: null) : null,
            'original_name' => $originalName
                ? $this->cleanOriginalName($originalName)
                : $product->slug . ($ext !== '' ? '.' . $ext : ''),
        ];

        if ($file) {
            $file->update($data);
            return $file;
        }

        return $product->files()->create($data + [
            'legacy_asset_id' => $asset->id,
            'label'           => $asset->label ?: ($product->type === 'book' ? 'PDF' : 'Logiciel'),
            'platform'        => null,
            'sort_order'      => 0,
        ]);
    }

    /** Supprime la ligne, l'asset d'origine éventuel, puis le fichier physique s'il n'est plus référencé. */
    public function deleteFile(ProductFile $file): void
    {
        DB::transaction(function () use ($file) {
            if ($file->legacy_asset_id) {
                MarketplaceAsset::whereKey($file->legacy_asset_id)->delete();
            }
            $file->delete();
        });

        $this->deletePhysicalIfUnreferenced($file->disk, $file->path);
    }

    /** Efface un fichier physique que plus aucune ligne (fichier produit ou asset) ne référence. */
    public function deletePhysicalIfUnreferenced(?string $disk, ?string $path): void
    {
        if (!$disk || !$path) return;

        $stillUsed = ProductFile::where('disk', $disk)->where('path', $path)->exists()
            || MarketplaceAsset::where('disk', $disk)->where('path', $path)->exists();

        if (!$stillUsed && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /** Monte (-1) ou descend (+1) un fichier dans la liste ; renumérote 0..n-1. */
    public function move(ProductFile $file, int $direction): void
    {
        $files = $file->product->files()->get()->values();
        $index = $files->search(fn ($f) => $f->id === $file->id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $files->count()) return;

        $ordered = $files->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $i => $f) {
                if ($f->sort_order !== $i) {
                    $f->update(['sort_order' => $i]);
                }
            }
        });
    }

    /**
     * Prévient par email (en queue) chaque acheteur payé et chaque bénéficiaire d'une
     * attribution active. Retourne le nombre de destinataires.
     */
    public function notifyBuyers(MarketplaceProduct $product, array $fileLabels): int
    {
        $count = 0;

        User::query()
            ->whereNotNull('email')
            ->where(function ($q) use ($product) {
                $q->whereIn('id', MarketplacePurchase::query()
                        ->select('user_id')
                        ->where('product_id', $product->id)
                        ->where('status', 'paid'))
                  ->orWhereIn('id', AdminGrant::query()
                        ->select('user_id')
                        ->where('grantable_type', MarketplaceProduct::class)
                        ->where('grantable_id', $product->id)
                        ->active());
            })
            ->chunkById(200, function ($users) use ($product, $fileLabels, &$count) {
                foreach ($users as $user) {
                    Mail::to($user)->queue(new ProductFilesAddedMail($product, $user, $fileLabels));
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Limites effectives d'upload, en octets : par fichier (config ∩ PHP) et par requête (PHP).
     */
    public function uploadLimits(): array
    {
        $configBytes = config('marketplace.files_max_mb', 500) * 1024 * 1024;
        $phpFile     = $this->iniBytes(ini_get('upload_max_filesize'));
        $phpPost     = $this->iniBytes(ini_get('post_max_size'));

        return [
            'file'  => $phpFile > 0 ? min($configBytes, $phpFile) : $configBytes,
            'total' => $phpPost > 0 ? $phpPost : null,
        ];
    }

    public function iniBytes(string|false|null $value): int
    {
        $value = trim((string) $value);
        if ($value === '') return 0;

        $unit   = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g'     => $number * 1024 ** 3,
            'm'     => $number * 1024 ** 2,
            'k'     => $number * 1024,
            default => $number,
        };
    }

    private function cleanLabel(?string $label): ?string
    {
        $label = trim((string) $label);
        return $label === '' ? null : Str::limit($label, 120, '');
    }

    private function defaultLabel(string $originalName, ?string $platform): string
    {
        return $platform
            ? (ProductFile::PLATFORMS[$platform] ?? $platform)
            : Str::limit(pathinfo($originalName, PATHINFO_FILENAME) ?: 'Fichier', 120, '');
    }

    private function cleanOriginalName(string $name): string
    {
        $name = trim(str_replace(['/', '\\', "\0", '"'], '-', $name));
        return Str::limit($name !== '' ? $name : 'fichier', 200, '');
    }
}
