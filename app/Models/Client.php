<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Client extends Model
{
    use HasFactory;

    // Les champs qu'on autorise à remplir en masse
    protected $fillable = [
        'user_id', 'nom', 'type', 'email',
        'telephone', 'delai_paiement', 'notes', 'actif',
    ];

    // Conversion automatique des types
    protected $casts = [
        'actif'           => 'boolean',
        'delai_paiement'  => 'integer',
    ];

    // Relation : un client appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation : un client a plusieurs factures
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    // Scope : filtre les clients actifs uniquement
    // Utilisation : Client::actifs()->get()
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    // Calcule la date d'échéance en ajoutant le délai de paiement
    public function calculerEcheance(string $dateEmission): Carbon
    {
        return Carbon::parse($dateEmission)->addDays($this->delai_paiement);
    }
}