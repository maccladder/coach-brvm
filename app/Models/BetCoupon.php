<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetCoupon extends Model
{
    protected $fillable = [
        'date_coupon', 'niveau', 'selections', 'cote_totale', 'analyse',
        'lien_betclic', 'statut', 'resultat', 'details_resultat', 'vues',
    ];

    protected $casts = [
        'date_coupon' => 'date',
        'selections' => 'array',
        'details_resultat' => 'array',
        'cote_totale' => 'float',
    ];

    public function scopePublies($q)  { return $q->where('statut', 'publie'); }
    public function scopeDuJour($q)   { return $q->where('date_coupon', today()); }
    public function scopeVerifies($q) { return $q->whereIn('resultat', ['gagne', 'perdu']); }

    public function debutPremierMatch(): ?\Carbon\Carbon
    {
        return collect($this->selections)
            ->map(function ($sel) {
                try {
                    return \Carbon\Carbon::parse($sel['heure'] ?? null);
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->sort()
            ->first();
    }

    public function estCommence(): bool
    {
        $debut = $this->debutPremierMatch();

        return $debut !== null && $debut->isPast();
    }

    public function badge(): array
    {
        return match ($this->niveau) {
            'sur'       => ['emoji' => '🟢', 'label' => 'Sûr',       'color' => '#22c55e'],
            'equilibre' => ['emoji' => '🟡', 'label' => 'Équilibré', 'color' => '#eab308'],
            'jackpot'   => ['emoji' => '🔴', 'label' => 'Jackpot',   'color' => '#ef4444'],
            'crazy'     => ['emoji' => '🎲', 'label' => 'Crazy',     'color' => '#a855f7'],
            'goals'     => ['emoji' => '⚽', 'label' => 'Goals',       'color' => '#14b8a6'],
            'pepite05'  => ['emoji' => '💎', 'label' => 'Pépite +0,5', 'color' => '#38bdf8'],
            'tennis_sur'       => ['emoji' => '🎾', 'label' => 'Tennis Sûr',       'color' => '#22c55e'],
            'tennis_equilibre' => ['emoji' => '🎾', 'label' => 'Tennis Équilibré', 'color' => '#eab308'],
            'tennis_jackpot'   => ['emoji' => '🎾', 'label' => 'Tennis Jackpot',   'color' => '#ef4444'],
            default     => ['emoji' => '❓', 'label' => ucfirst($this->niveau), 'color' => '#6b7280'],
        };
    }

    public static function stats(): array
    {
        $verifies = static::publies()->verifies()->get()->groupBy('niveau');

        $out = [];
        foreach (['sur', 'equilibre', 'jackpot'] as $niveau) {
            $lot = $verifies->get($niveau, collect());
            $total = $lot->count();
            $gagnes = $lot->where('resultat', 'gagne')->count();
            $out[$niveau] = [
                'total'  => $total,
                'gagnes' => $gagnes,
                'taux'   => $total ? round($gagnes / $total * 100) : null,
            ];
        }
        return $out;
    }
}
