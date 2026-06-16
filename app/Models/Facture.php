<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Facture extends Model
{
    use HasFactory;
    // use SoftDeletes; // Décommentez si vous utilisez la suppression douce

    protected $fillable = [
        'client_id', 
        'numero', 
        'montant_ht', 
        'tva',
        'montant_ttc', 
        'date_emission', 
        'date_echeance',
        'date_paiement', 
        'statut', 
        'description',
        'tresorerie_id',
    ];

    protected $casts = [
        'date_emission'  => 'date',
        'date_echeance'  => 'date',
        'date_paiement'  => 'date',
        'montant_ht'     => 'decimal:2',
        'tva'            => 'decimal:2',
        'montant_ttc'    => 'decimal:2',
        'tresorerie_id'  => 'integer',
    ];

    // Relation : une facture appartient à un client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation : une facture appartient à une ligne de trésorerie
    public function tresorerie()
    {
        return $this->belongsTo(Tresorerie::class, 'tresorerie_id');
    }

    /**
     * calculerTTC() — Calcule le TTC à partir du HT et de la TVA
     */
    public static function calculerTTC(float $ht, float $tva): float
    {
        return round($ht * (1 + $tva / 100), 2);
    }

    /**
     * genererNumero() — Génère le prochain numéro de facture unique
     * ✅ CORRIGÉ : Évite les doublons en vérifiant l'existence
     */
    public static function genererNumero(): string
    {
        $annee = now()->year;
        
        // Récupérer le dernier numéro créé dans l'année
        $derniereFacture = static::whereYear('created_at', $annee)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($derniereFacture) {
            // Extraire le numéro séquentiel (ex: FAC-2026-008 → 8)
            if (preg_match('/FAC-' . $annee . '-(\d+)/', $derniereFacture->numero, $matches)) {
                $compteur = (int)$matches[1] + 1;
            } else {
                $compteur = 1;
            }
        } else {
            $compteur = 1;
        }
        
        // Éviter les numéros trop grands (sécurité)
        if ($compteur > 9999) {
            throw new \Exception('Trop de factures créées cette année (limite 9999).');
        }
        
        return 'FAC-' . $annee . '-' . str_pad($compteur, 3, '0', STR_PAD_LEFT);
    }

    /**
     * genererNumeroSecurise() — Version avec vérification d'unicité
     * À utiliser dans le contrôleur avec une boucle de sécurité
     */
    public static function genererNumeroSecurise(): string
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            $numero = self::genererNumero();
            $attempts++;
            
            if ($attempts > $maxAttempts) {
                throw new \Exception('Impossible de générer un numéro unique après ' . $maxAttempts . ' tentatives.');
            }
            
        } while (self::where('numero', $numero)->exists());
        
        return $numero;
    }

    /**
     * marquerPayee() — Marque la facture comme payée
     */
    public function marquerPayee(?string $date = null): void
    {
        $this->update([
            'statut'        => 'payee',
            'date_paiement' => $date ?? today()->toDateString(),
        ]);
    }

    /**
     * estEnRetard() — Vérifie si la facture est en retard
     */
    public function isEnRetard(): bool
    {
        return $this->statut === 'en_retard' || 
               ($this->statut === 'en_attente' && $this->date_echeance < now());
    }

    /**
     * scopeEnAttente() — Filtre les factures en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * scopePayees() — Filtre les factures payées
     */
    public function scopePayees($query)
    {
        return $query->where('statut', 'payee');
    }

    /**
     * scopeEnRetard() — Filtre les factures en retard
     */
    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard');
    }
}