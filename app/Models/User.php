<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\AdminGrant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\VirtualWallet;
use App\Models\VirtualPosition;
use App\Models\VirtualWalletTransaction;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'phone_reward_claimed',
        'wallet_bonus_claimed_at',
        'is_affiliate',
        'affiliate_status',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

   public function virtualWallet()
{
    return $this->hasOne(VirtualWallet::class);
}

public function virtualPositions()
{
    return $this->hasMany(VirtualPosition::class);
}

public function coursePurchases()
{
    return $this->hasMany(\App\Models\CoursePurchase::class);
}

public function virtualWalletTransactions()
{
    return $this->hasMany(VirtualWalletTransaction::class);
}



public function courses()
{
    return $this->belongsToMany(\App\Models\Course::class, 'course_purchases')
        ->withPivot(['paid_at','amount_fcfa','payment_ref'])
        ->withTimestamps();
}

public function marketplacePurchases()
{
    return $this->hasMany(\App\Models\MarketplacePurchase::class);
}

public function marketplaceProducts()
{
    return $this->hasMany(\App\Models\MarketplaceProduct::class, 'user_id');
}

public function documentPurchases()
{
    return $this->hasMany(\App\Models\DocumentPurchase::class);
}

public function purchasedProducts()
{
    return $this->belongsToMany(\App\Models\MarketplaceProduct::class, 'marketplace_purchases', 'user_id', 'product_id')
        ->withPivot(['status','paid_at','amount','provider','provider_ref'])
        ->withTimestamps();
}

public function adminGrants(): HasMany
{
    return $this->hasMany(AdminGrant::class);
}

public function affiliate()
{
    return $this->hasOne(\App\Models\Affiliate::class);
}

/**
 * Vérifie si cet utilisateur a accès à un item via un AdminGrant actif.
 * Accepte un modèle Eloquent (Course, MarketplaceProduct, Document…)
 * ou la chaîne 'lettreci' pour le module LettreCI.
 *
 * @param  \Illuminate\Database\Eloquent\Model|string  $grantable
 */
public function hasAccessTo(mixed $grantable): bool
{
    if ($grantable === 'lettreci') {
        return AdminGrant::hasLettreCIAccess($this);
    }

    return AdminGrant::where('user_id', $this->id)
        ->where('grantable_type', get_class($grantable))
        ->where('grantable_id', $grantable->id)
        ->active()
        ->exists();
}

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'phone_verified_at'       => 'datetime',
            'phone_reward_claimed'    => 'boolean',
            'wallet_bonus_claimed_at' => 'datetime',
            'is_affiliate'            => 'boolean',
        ];
    }
}
