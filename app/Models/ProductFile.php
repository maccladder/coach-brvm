<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFile extends Model
{
    protected $table = 'marketplace_product_files';

    public const PLATFORMS = [
        'windows'          => 'Windows',
        'windows_portable' => 'Windows (portable)',
        'macos'            => 'macOS',
        'linux'            => 'Linux',
        'android'          => 'Android',
        'autre'            => 'Autre',
    ];

    protected $fillable = [
        'product_id', 'legacy_asset_id', 'label', 'platform', 'original_name',
        'disk', 'path', 'size', 'mime', 'sort_order', 'download_count',
        'status', 'replaces_file_id', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'size'           => 'integer',
        'sort_order'     => 'integer',
        'download_count' => 'integer',
        'reviewed_at'    => 'datetime',
    ];

    protected $attributes = [
        'status' => 'approved',
    ];

    public function product()
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    /** Fichier existant que celui-ci remplacera une fois approuvé. */
    public function replaces()
    {
        return $this->belongsTo(self::class, 'replaces_file_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getPlatformLabelAttribute(): ?string
    {
        return $this->platform ? (self::PLATFORMS[$this->platform] ?? $this->platform) : null;
    }

    /** Famille d'OS affichée à l'acheteur ("Windows" pour installeur et portable). */
    public function getPlatformFamilyAttribute(): ?string
    {
        return match ($this->platform) {
            'windows', 'windows_portable' => 'Windows',
            'macos'   => 'macOS',
            'linux'   => 'Linux',
            'android' => 'Android',
            default   => null,
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->platform) {
            'windows', 'windows_portable' => 'bi-windows',
            'macos'   => 'bi-apple',
            'linux'   => 'bi-ubuntu',
            'android' => 'bi-android2',
            default   => match (strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION))) {
                'pdf'          => 'bi-file-earmark-pdf',
                'epub'         => 'bi-book',
                'mp4'          => 'bi-file-earmark-play',
                'zip', 'rar', '7z' => 'bi-file-earmark-zip',
                default        => 'bi-file-earmark-arrow-down',
            },
        };
    }

    /** OS du visiteur d'après son user-agent : windows|macos|linux|android|ios|null. */
    public static function osFromUserAgent(?string $ua): ?string
    {
        $ua = strtolower((string) $ua);
        if ($ua === '') return null;

        // Ordre important : Android contient "linux", iOS contient "mac os x"
        if (str_contains($ua, 'android')) return 'android';
        if (preg_match('/iphone|ipad|ipod/', $ua)) return 'ios';
        if (str_contains($ua, 'windows')) return 'windows';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os x')) return 'macos';
        if (str_contains($ua, 'linux') || str_contains($ua, 'x11')) return 'linux';

        return null;
    }

    public function matchesOs(?string $os): bool
    {
        if (!$os || !$this->platform) return false;

        return $os === 'windows'
            ? in_array($this->platform, ['windows', 'windows_portable'], true)
            : $this->platform === $os;
    }

    /** Taille lisible : "512 Ko", "84,3 Mo", "1,2 Go". */
    public function getHumanSizeAttribute(): ?string
    {
        if ($this->size === null) return null;

        $units = ['octets', 'Ko', 'Mo', 'Go'];
        $size  = (float) $this->size;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return number_format($size, $i >= 2 ? 1 : 0, ',', ' ') . ' ' . $units[$i];
    }
}
