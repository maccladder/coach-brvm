<?php

namespace App\Traits;

use App\Models\AdminGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAdminGrants
{
    public function adminGrants(): MorphMany
    {
        return $this->morphMany(AdminGrant::class, 'grantable');
    }

    public function isGrantedTo(User $user): bool
    {
        return $user->hasAccessTo($this);
    }
}
