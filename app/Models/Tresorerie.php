<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tresorerie extends Model
{
    // Nom explicite de la table (Laravel cherche tresorerie_mensuelles par défaut)
    protected $table = 'tresorerie';

    protected $fillable = [
        'annee',
        'mois',
        'solde_initial',
        'total_entrees',
        'total_sorties',
        'solde_mois',
        'solde_cumule',
    ];

    protected $casts = [
        'annee'         => 'integer',
        'mois'          => 'integer',
        'solde_initial' => 'decimal:2',
        'total_entrees' => 'decimal:2',
        'total_sorties' => 'decimal:2',
        'solde_mois'    => 'decimal:2',
        'solde_cumule'  => 'decimal:2',
    ];

    // ─── Relation ────────────────────────────────────────────
    // Un mois de trésorerie génère zéro ou plusieurs alertes
    public function alertes()
    {
        return $this->hasMany(Alerte::class, 'tresorerie_id');
    }

    // ─── MOTEUR DE CALCUL PRINCIPAL ──────────────────────────
    /**
     * calculerPlan() — recalcule le plan de trésorerie complet
     *
     * Cette méthode est appelée automatiquement après chaque
     * création / modification / suppression de facture ou charge.
     *
     * Algorithme :
     * Pour chaque mois de janvier à décembre :
     *   1. Entrées = somme des factures dont date_echeance tombe ce mois
     *   2. Sorties = somme des charges dont date_prevue tombe ce mois
     *   3. Solde mois = Entrées - Sorties
     *   4. Solde cumulé = Solde cumulé du mois précédent + Solde mois
     * Puis vérifier si des alertes doivent être créées.
     *
     * @param int   $annee        Année à recalculer (ex: 2026)
     * @param float $soldeInitial Trésorerie disponible au 1er janvier
     */
    public static function calculerPlan(int $annee, float $soldeInitial = 0): void
    {
        // Le cumulé commence avec le solde initial disponible
        $cumulePrecedent = $soldeInitial;

        // Boucle sur les 12 mois de l'année
        for ($mois = 1; $mois <= 12; $mois++) {

            // ── ÉTAPE 1 : Calculer les entrées du mois ──────
            // On somme toutes les factures (en attente ou payées)
            // dont la DATE D'ÉCHÉANCE tombe dans ce mois-ci.
            // C'est la date d'échéance qui détermine quand l'argent arrive,
            // pas la date d'émission.
            $entrees = Facture::whereYear('date_echeance', $annee)
                ->whereMonth('date_echeance', $mois)
                ->whereIn('statut', ['en_attente', 'payee', 'en_retard'])
                ->sum('montant_ttc');

            // ── ÉTAPE 2 : Calculer les sorties du mois ──────
            // On somme toutes les charges dont la DATE PRÉVUE
            // tombe dans ce mois-ci.
            $sorties = Charge::whereYear('date_prevue', $annee)
                ->whereMonth('date_prevue', $mois)
                ->sum('montant');

            // ── ÉTAPE 3 : Calculer les soldes ───────────────
            // Solde du mois = ce qui rentre - ce qui sort
            $soldeMois = $entrees - $sorties;

            // Solde cumulé = tout ce qu'on avait avant + ce mois-ci
            // Si ce nombre devient négatif → déficit → alerte critique
            $soldeCumule = $cumulePrecedent + $soldeMois;

            // ── ÉTAPE 4 : Enregistrer ou mettre à jour ──────
            // updateOrCreate cherche la ligne (annee=2026, mois=5)
            // Si elle existe : la met à jour
            // Si elle n'existe pas : la crée
            $ligne = static::updateOrCreate(
                // Critères de recherche (clé unique)
                ['annee' => $annee, 'mois' => $mois],
                // Valeurs à enregistrer
                [
                    'solde_initial' => $mois === 1 ? $soldeInitial : $cumulePrecedent,
                    'total_entrees' => round((float)$entrees, 2),
                    'total_sorties' => round((float)$sorties, 2),
                    'solde_mois'    => round($soldeMois, 2),
                    'solde_cumule'  => round($soldeCumule, 2),
                ]
            );

            // ── ÉTAPE 5 : Vérifier et créer les alertes ─────
            $ligne->genererAlertes();

            // Le cumulé de ce mois devient le point de départ du mois suivant
            $cumulePrecedent = $soldeCumule;
        }
    }

    // ─── GÉNÉRATION DES ALERTES ──────────────────────────────
    /**
     * genererAlertes() — crée les alertes pour ce mois
     *
     * Appelé automatiquement après chaque recalcul du plan.
     * Supprime les anciennes alertes du mois et recrée si nécessaire.
     *
     * 3 niveaux d'alerte :
     * - critique  : solde cumulé < 0 (l'entreprise manque d'argent)
     * - warning   : solde mensuel < 0 mais cumulé encore positif (tension)
     * - info      : solde cumulé < seuil minimum configuré
     */
    public function genererAlertes(): void
    {// Supprimer les alertes précédentes de ce mois
$this->alertes()->delete();

$labelMois = $this->labelMois();

// ✅ Conversion en float pour éviter l'erreur number_format()
$soldeCumule = (float) $this->solde_cumule;
$soldeMois = (float) $this->solde_mois;

// ── ALERTE CRITIQUE : solde cumulé négatif ──────────
if ($soldeCumule < 0) {
    Alerte::create([
        'tresorerie_id' => $this->id,
        'type'          => 'deficit',
        'niveau'        => 'critique',
        'message'       => "Déficit prévu en {$labelMois} : solde cumulé de "
                         . number_format($soldeCumule, 2, ',', ' ')
                         . " MAD",
        'mois_concerne' => $this->annee . '-'
                         . str_pad($this->mois, 2, '0', STR_PAD_LEFT) . '-01',
        'resolue'       => false,
    ]);
}
// ── ALERTE WARNING : tension de trésorerie ───────────
elseif ($soldeMois < 0 && $soldeCumule > 0) {
    Alerte::create([
        'tresorerie_id' => $this->id,
        'type'          => 'tension',
        'niveau'        => 'warning',
        'message'       => "Tension en {$labelMois} : solde du mois de "
                         . number_format($soldeMois, 2, ',', ' ')
                         . " MAD (cumulé positif : "
                         . number_format($soldeCumule, 2, ',', ' ') . " MAD)",
        'mois_concerne' => $this->annee . '-'
                         . str_pad($this->mois, 2, '0', STR_PAD_LEFT) . '-01',
        'resolue'       => false,
    ]);
}
// ── ALERTE INFO : solde bas mais positif ─────────────
elseif ($soldeCumule > 0 && $soldeCumule < 5000) {
    Alerte::create([
        'tresorerie_id' => $this->id,
        'type'          => 'tension',
        'niveau'        => 'info',
        'message'       => "Solde bas en {$labelMois} : "
                         . number_format($soldeCumule, 2, ',', ' ')
                         . " MAD (sous le seuil de sécurité)",
        'mois_concerne' => $this->annee . '-'
                         . str_pad($this->mois, 2, '0', STR_PAD_LEFT) . '-01',
        'resolue'       => false,
    ]);
}}

    // ─── SIMULATION DE SCÉNARIOS ─────────────────────────────
    /**
     * simulerScenario() — calcule un plan alternatif EN MÉMOIRE
     *
     * Ne modifie JAMAIS la base de données.
     * Retourne un tableau de résultats simulés comparables au plan réel.
     *
     * @param string $type   'retard_client' | 'baisse_activite' | 'charge_exceptionnelle'
     * @param array  $params Paramètres du scénario
     * @param int    $annee
     * @param float  $soldeInitial
     * @return array  Plan simulé mois par mois
     */
    public static function simulerScenario(
        string $type,
        array  $params,
        int    $annee,
        float  $soldeInitial = 0
    ): array {
        $planSimule      = [];
        $cumulePrecedent = $soldeInitial;

        for ($mois = 1; $mois <= 12; $mois++) {

            // Charge les données réelles
            $entrees = (float) Facture::whereYear('date_echeance', $annee)
                ->whereMonth('date_echeance', $mois)
                ->whereIn('statut', ['en_attente', 'payee', 'en_retard'])
                ->sum('montant_ttc');

            $sorties = (float) Charge::whereYear('date_prevue', $annee)
                ->whereMonth('date_prevue', $mois)
                ->sum('montant');

            // Applique la modification du scénario
            if ($type === 'retard_client' && isset($params['client_id'], $params['retard_mois'])) {
                // Décale les factures du client vers un mois ultérieur
                $montantClient = (float) Facture::whereYear('date_echeance', $annee)
                    ->whereMonth('date_echeance', $mois)
                    ->where('client_id', $params['client_id'])
                    ->sum('montant_ttc');
                $entrees -= $montantClient;
                // Les factures décalées seront ajoutées au mois+retard
                // (logique simplifiée : on les retire du mois courant)
            }

            if ($type === 'baisse_activite' && isset($params['taux_reduction'])) {
                // Réduit toutes les entrées d'un pourcentage
                $entrees = $entrees * (1 - $params['taux_reduction'] / 100);
            }

            if ($type === 'charge_exceptionnelle'
                && isset($params['mois_impact'], $params['montant_extra'])
                && $mois === (int) $params['mois_impact']) {
                // Ajoute une charge imprévue pour ce mois
                $sorties += $params['montant_extra'];
            }

            $soldeMois   = $entrees - $sorties;
            $soldeCumule = $cumulePrecedent + $soldeMois;

            $planSimule[] = [
                'mois'          => $mois,
                'label'         => self::nomMois($mois) . ' ' . $annee,
                'total_entrees' => round($entrees, 2),
                'total_sorties' => round($sorties, 2),
                'solde_mois'    => round($soldeMois, 2),
                'solde_cumule'  => round($soldeCumule, 2),
                'en_deficit'    => $soldeCumule < 0,
                'en_tension'    => $soldeMois < 0 && $soldeCumule >= 0,
            ];

            $cumulePrecedent = $soldeCumule;
        }

        return $planSimule;
    }

    // ─── HELPERS D'AFFICHAGE ─────────────────────────────────

    public function estDeficitaire(): bool
    {
        return $this->solde_cumule < 0;
    }

    public function estEnTension(): bool
    {
        return $this->solde_mois < 0 && $this->solde_cumule >= 0;
    }

    public function labelMois(): string
    {
        return self::nomMois($this->mois) . ' ' . $this->annee;
    }

    public static function nomMois(int $mois): string
    {
        return [
            1=>'Janvier', 2=>'Février',   3=>'Mars',
            4=>'Avril',   5=>'Mai',        6=>'Juin',
            7=>'Juillet', 8=>'Août',       9=>'Septembre',
            10=>'Octobre',11=>'Novembre', 12=>'Décembre',
        ][$mois] ?? '';
    }
}