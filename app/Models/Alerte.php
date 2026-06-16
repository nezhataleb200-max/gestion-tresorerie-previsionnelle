<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    protected $fillable = [
        'tresorerie_id',
        'type',
        'niveau',
        'message',
        'mois_concerne',
        'resolue',
    ];

    protected $casts = [
        'mois_concerne' => 'date',
        'resolue'       => 'boolean',
    ];

    // ─── Relation ─────────────────────────────────────────────
    // Une alerte est liée à un mois de trésorerie
    public function tresorerie()
    {
        return $this->belongsTo(Tresorerie::class, 'tresorerie_id');
    }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeNonResolues($query)
    {
        return $query->where('resolue', false);
    }

    public function scopeCritiques($query)
    {
        return $query->where('niveau', 'critique');
    }

    // ─── Helpers ──────────────────────────────────────────────
    public function marquerResolue(): void
    {
        $this->update(['resolue' => true]);
    }

    public function estCritique(): bool
    {
        return $this->niveau === 'critique';
    }

    // Retourne la classe CSS Bootstrap selon le niveau
    public function classeBadge(): string
    {
        return match($this->niveau) {
            'critique' => 'danger',
            'warning'  => 'warning',
            default    => 'info',
        };
    }
}