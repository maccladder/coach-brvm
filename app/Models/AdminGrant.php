<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminGrant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'grantable_type',
        'grantable_id',
        'reason',
        'granted_by',
        'granted_at',
        'expires_at',
        'revoked_at',
        'revoked_by',
        'revoke_reason',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /* ================================================================
     | Relations
     ================================================================ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Relation polymorphique — ne pas appeler directement pour les grants
     * de type 'lettreci' (grantable_id = 0, pas de modèle associé).
     * Utiliser AdminGrant::resolveItemNames() pour l'affichage.
     */
    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    /* ================================================================
     | Scopes
     ================================================================ */

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('revoked_at')
                 ->where(function (Builder $sub) {
                     $sub->whereNull('expires_at')
                         ->orWhere('expires_at', '>', now());
                 });
    }

    public function scopeRevoked(Builder $q): Builder
    {
        return $q->whereNotNull('revoked_at');
    }

    public function scopeExpired(Builder $q): Builder
    {
        return $q->whereNull('revoked_at')
                 ->whereNotNull('expires_at')
                 ->where('expires_at', '<=', now());
    }

    /* ================================================================
     | Helpers
     ================================================================ */

    public function isActive(): bool
    {
        return is_null($this->revoked_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function revoke(User $admin, string $reason): void
    {
        $this->update([
            'revoked_at'    => now(),
            'revoked_by'    => $admin->id,
            'revoke_reason' => $reason,
        ]);
    }

    public function restore(): void
    {
        $this->update([
            'revoked_at'    => null,
            'revoked_by'    => null,
            'revoke_reason' => null,
        ]);
    }

    /** Vérification d'accès LettreCI par grant admin (type spécial). */
    public static function hasLettreCIAccess(User $user): bool
    {
        return static::where('user_id', $user->id)
            ->where('grantable_type', 'lettreci')
            ->active()
            ->exists();
    }

    /* ================================================================
     | Accessors pour l'affichage
     ================================================================ */

    public function getStatusLabelAttribute(): string
    {
        if (!is_null($this->revoked_at)) {
            return 'Révoquée';
        }
        if (!is_null($this->expires_at) && $this->expires_at->isPast()) {
            return 'Expirée';
        }
        return 'Active';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status_label) {
            'Active'   => 'success',
            'Révoquée' => 'danger',
            'Expirée'  => 'secondary',
            default    => 'dark',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->grantable_type) {
            'lettreci'                              => 'LettreCI',
            \App\Models\Course::class               => 'Formation',
            \App\Models\MarketplaceProduct::class   => 'Marketplace',
            \App\Models\Document::class             => 'Document',
            default                                 => class_basename($this->grantable_type),
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->grantable_type) {
            'lettreci'                              => 'warning',
            \App\Models\Course::class               => 'primary',
            \App\Models\MarketplaceProduct::class   => 'info',
            \App\Models\Document::class             => 'secondary',
            default                                 => 'dark',
        };
    }

    /**
     * Charge les noms d'items sur une collection de grants sans déclencher
     * la résolution morphTo (qui échouerait sur 'lettreci').
     * Retourne un tableau [grant_id => 'nom de l'item'].
     */
    public static function resolveItemNames(\Illuminate\Support\Collection $grants): array
    {
        $names = [];

        // Cas spécial LettreCI
        foreach ($grants->where('grantable_type', 'lettreci') as $grant) {
            $names[$grant->id] = 'Accès LettreCI';
        }

        // Autres types : regroupement par type pour éviter les N+1
        $byType = $grants->where('grantable_type', '!=', 'lettreci')
                         ->groupBy('grantable_type');

        foreach ($byType as $type => $typeGrants) {
            if (!class_exists($type)) {
                foreach ($typeGrants as $grant) {
                    $names[$grant->id] = '(item supprimé)';
                }
                continue;
            }

            $ids    = $typeGrants->pluck('grantable_id')->unique();
            $models = $type::whereIn('id', $ids)->pluck('title', 'id');

            foreach ($typeGrants as $grant) {
                $names[$grant->id] = $models[$grant->grantable_id] ?? '(item supprimé)';
            }
        }

        return $names;
    }
}
