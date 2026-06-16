<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Charge extends Model
{
    use HasFactory;

    // ─── Colonnes autorisées à la saisie en masse ──────────
    protected $fillable = [
        'user_id',
        'libelle',
        'montant',
        'date_prevue',
        'categorie',
        'type',
        'recurrence',
        'date_fin_recurrence',
        'payee',
    ];

    // ─── Conversions automatiques des types ────────────────
    protected $casts = [
        'date_prevue'         => 'datetime',  // → objet Carbon
        'date_fin_recurrence' => 'datetime',  // → objet Carbon ou null
        'montant'             => 'decimal:2',
        'payee'               => 'boolean',
    ];

    // ─── Relation : une charge appartient à un utilisateur ─
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scope : charges d'un mois précis ──────────────────
    // Utilisé par le calcul du plan pour grouper les sorties par mois
    public function scopeDuMois($query, int $annee, int $mois)
    {
        return $query->whereYear('date_prevue', $annee)
                     ->whereMonth('date_prevue', $mois);
    }

    // ─── Scope : uniquement les charges fixes ──────────────
    public function scopeFixe($query)
    {
        return $query->where('type', 'fixe');
    }

    // ─── Génère les occurrences d'une charge récurrente ────
    // C'est la fonction centrale de ce module
    public function genererOccurrences(): void
    {
        // Si la charge n'est pas récurrente, on ne fait rien
        if ($this->recurrence === 'aucune') {
            return;
        }

        // Date de départ : le mois SUIVANT la charge créée
        $date = $this->date_prevue->copy()->addMonth();

        // Date de fin : soit la date configurée, soit dans 1 an par défaut
        $fin = $this->date_fin_recurrence ?? now()->addYear();

        // Boucle : on crée une occurrence pour chaque période
        while ($date->lte($fin)) {

            // Crée une copie de la charge pour ce mois
            static::create([
                'user_id'            => $this->user_id,
                'libelle'            => $this->libelle,
                'montant'            => $this->montant,
                'date_prevue'        => $date->copy(),
                'categorie'          => $this->categorie,
                'type'               => $this->type,
                // Important : les occurrences ne se régénèrent pas
                'recurrence'         => 'aucune',
                'date_fin_recurrence'=> null,
            ]);

            // Avancer selon le type de récurrence
            match($this->recurrence) {
                'mensuelle'     => $date->addMonth(),
                'trimestrielle' => $date->addMonths(3),
                'annuelle'      => $date->addYear(),
                default         => $date->addYears(100), // sort de la boucle
            };
        }
    }

    // ─── Vérifie si la charge est récurrente ───────────────
    public function estRecurrente(): bool
    {
        return $this->recurrence !== 'aucune';
    }

    // ─── Retourne le libellé de la catégorie en français ───
    public function labelCategorie(): string
    {
        return match($this->categorie) {
            'loyer'        => 'Loyer',
            'salaires'     => 'Salaires',
            'impots'       => 'Impôts / Taxes',
            'fournisseurs' => 'Fournisseurs',
            'services'     => 'Services',
            default        => 'Autre',
        };
    }
    // ─── Retourne le libellé de la récurrence en français ───
    public function labelRecurrence(): string
    {
    return match($this->recurrence) {
            'aucune'        => 'Ponctuelle',
            'mensuelle'     => 'Mensuelle',
            'trimestrielle' => 'Trimestrielle',
            'annuelle'      => 'Annuelle',
            default         => ucfirst($this->recurrence),
        };
    }
}