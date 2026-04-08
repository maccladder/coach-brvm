<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagiaireLog extends Model
{
    protected $fillable = [
        'action',
        'label',
        'route',
        'method',
        'url',
        'ip',
        'user_agent',
    ];

    public static function record(string $action, ?string $label = null, ?string $route = null, ?string $method = null, ?string $url = null): void
    {
        static::create([
            'action'     => $action,
            'label'      => $label,
            'route'      => $route,
            'method'     => $method,
            'url'        => $url,
            'ip'         => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
        ]);
    }

    // Icone selon l'action pour la vue
    public function getIconAttribute(): string
    {
        return match(true) {
            $this->action === 'login'              => '🔐',
            $this->action === 'logout'             => '🚪',
            str_contains($this->action, 'approve') => '✅',
            str_contains($this->action, 'view')    => '👁️',
            str_contains($this->action, 'update')  => '✏️',
            str_contains($this->action, 'create')  => '➕',
            str_contains($this->action, 'delete')  => '🗑️',
            default                                => '📋',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match(true) {
            $this->action === 'login'              => 'success',
            $this->action === 'logout'             => 'secondary',
            str_contains($this->action, 'approve') => 'primary',
            str_contains($this->action, 'view')    => 'light',
            str_contains($this->action, 'update')  => 'warning',
            str_contains($this->action, 'delete')  => 'danger',
            default                                => 'info',
        };
    }
}
