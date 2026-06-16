<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Facture;
use App\Models\Charge;
use App\Models\Tresorerie;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    /**
     * index() — affiche le formulaire de simulation
     *
     * On passe la liste des clients pour le scénario
     * "retard client" (l'utilisateur choisit quel client).
     */
    public function index()
    {
        // Clients actifs de l'utilisateur connecté
        // pour le scénario retard_client
        $clients = Client::where('user_id', auth()->id())
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        // Plan réel actuel pour comparaison
        $annee      = now()->year;
        $planReel   = Tresorerie::where('annee', $annee)
            ->orderBy('mois')
            ->get();

        return view('simulation.index', compact('clients', 'planReel', 'annee'));
    }

    /**
     * simuler() — calcule le plan simulé selon le scénario choisi
     *
     * PRINCIPE FONDAMENTAL :
     * Cette méthode ne modifie JAMAIS la base de données.
     * Tout le calcul est fait en mémoire PHP (tableaux).
     * Elle retourne les résultats simulés pour affichage,
     * puis PHP libère la mémoire — les vraies données sont intactes.
     */
    public function simuler(Request $request)
    {
        // ── VALIDATION ────────────────────────────────────────
        $request->validate([
            'type_scenario'   => 'required|in:retard_client,baisse_activite,charge_exceptionnelle',
            'annee'           => 'required|integer|min:2024|max:2030',
            'solde_initial'   => 'required|numeric',
            // Champs spécifiques à chaque scénario
            'client_id'       => 'required_if:type_scenario,retard_client|nullable|exists:clients,id',
            'retard_mois'     => 'required_if:type_scenario,retard_client|nullable|integer|min:1|max:12',
            'taux_reduction'  => 'required_if:type_scenario,baisse_activite|nullable|numeric|min:1|max:100',
            'mois_impact'     => 'required_if:type_scenario,charge_exceptionnelle|nullable|integer|min:1|max:12',
            'montant_extra'   => 'required_if:type_scenario,charge_exceptionnelle|nullable|numeric|min:0',
            'libelle_extra'   => 'nullable|string|max:150',
        ], [
            'type_scenario.required'  => 'Choisissez un type de scénario.',
            'client_id.required_if'   => 'Sélectionnez un client pour ce scénario.',
            'retard_mois.required_if' => 'Indiquez le nombre de mois de retard.',
            'taux_reduction.required_if' => 'Indiquez le taux de réduction.',
            'mois_impact.required_if' => 'Indiquez le mois impacté.',
            'montant_extra.required_if'  => 'Indiquez le montant de la charge.',
        ]);

        $annee        = (int) $request->annee;
        $soldeInitial = (float) $request->solde_initial;
        $type         = $request->type_scenario;

        // ── PLAN RÉEL (données actuelles de la BDD) ───────────
        // On recalcule pour être sûr d'avoir les données fraîches
        Tresorerie::calculerPlan($annee, $soldeInitial);
        $planReel = Tresorerie::where('annee', $annee)
            ->orderBy('mois')
            ->get();

        // ── CALCUL DU PLAN SIMULÉ (EN MÉMOIRE) ───────────────
        $planSimule      = [];
        $cumulePrecedent = $soldeInitial;

        for ($mois = 1; $mois <= 12; $mois++) {

            // Charge les données RÉELLES du mois
            $entrees = (float) Facture::whereYear('date_echeance', $annee)
                ->whereMonth('date_echeance', $mois)
                ->whereIn('statut', ['en_attente', 'payee', 'en_retard'])
                ->sum('montant_ttc');

            $sorties = (float) Charge::where('user_id', auth()->id())
                ->whereYear('date_prevue', $annee)
                ->whereMonth('date_prevue', $mois)
                ->sum('montant');

            // ── APPLIQUE LA MODIFICATION DU SCÉNARIO ─────────
            // IMPORTANT : on modifie seulement les variables locales
            // La base de données n'est PAS touchée
            switch ($type) {

                // ────────────────────────────────────────────────
                case 'retard_client':
                    /**
                     * Scénario 1 — Retard d'un client
                     *
                     * Le client ne paie pas dans les délais.
                     * Ses factures sont décalées de N mois.
                     * On retire le montant de ce mois
                     * et on l'ajoute au mois (mois + retard).
                     */
                    $clientId    = (int) $request->client_id;
                    $retardMois  = (int) $request->retard_mois;

                    // Montant des factures du client ce mois
                    $montantClient = (float) Facture::whereYear('date_echeance', $annee)
                        ->whereMonth('date_echeance', $mois)
                        ->where('client_id', $clientId)
                        ->whereIn('statut', ['en_attente', 'payee', 'en_retard'])
                        ->sum('montant_ttc');

                    // On retire les factures du client ce mois
                    $entrees -= $montantClient;

                    // On les ajoute au mois (mois + retard)
                    // si ce mois correspond à un mois décalé
                    $moisSource = $mois - $retardMois;
                    if ($moisSource >= 1 && $moisSource <= 12) {
                        $montantDecale = (float) Facture::whereYear('date_echeance', $annee)
                            ->whereMonth('date_echeance', $moisSource)
                            ->where('client_id', $clientId)
                            ->whereIn('statut', ['en_attente', 'payee', 'en_retard'])
                            ->sum('montant_ttc');
                        $entrees += $montantDecale;
                    }
                    break;

                // ────────────────────────────────────────────────
                case 'baisse_activite':
                    /**
                     * Scénario 2 — Baisse d'activité
                     *
                     * L'entreprise facture moins (crise, saisonnalité).
                     * On réduit TOUTES les entrées d'un pourcentage.
                     * Ex : -30% → les factures de 10 000 MAD
                     *      deviennent 7 000 MAD.
                     */
                    $taux    = (float) $request->taux_reduction;
                    $entrees = $entrees * (1 - $taux / 100);
                    break;

                // ────────────────────────────────────────────────
                case 'charge_exceptionnelle':
                    /**
                     * Scénario 3 — Charge exceptionnelle
                     *
                     * Une dépense imprévue survient un mois précis.
                     * Ex : panne machine, procès, réparation urgente.
                     * On ajoute le montant aux sorties du mois impacté.
                     */
                    $moisImpact   = (int) $request->mois_impact;
                    $montantExtra = (float) $request->montant_extra;

                    if ($mois === $moisImpact) {
                        $sorties += $montantExtra;
                    }
                    break;
            }

            // ── CALCULE LES SOLDES SIMULÉS ────────────────────
            $soldeMois   = $entrees - $sorties;
            $soldeCumule = $cumulePrecedent + $soldeMois;

            // ── STOCKE LE RÉSULTAT EN MÉMOIRE ─────────────────
            $planSimule[] = [
                'mois'          => $mois,
                'label'         => Tresorerie::nomMois($mois),
                'total_entrees' => round($entrees, 2),
                'total_sorties' => round($sorties, 2),
                'solde_mois'    => round($soldeMois, 2),
                'solde_cumule'  => round($soldeCumule, 2),
                'en_deficit'    => $soldeCumule < 0,
                'en_tension'    => $soldeMois < 0 && $soldeCumule >= 0,
            ];

            $cumulePrecedent = $soldeCumule;
        }

        // ── CALCUL DES IMPACTS (différences réel vs simulé) ───
        $impacts = $this->calculerImpacts($planReel, $planSimule);

        // ── TITRE DU SCÉNARIO ─────────────────────────────────
        $titreScenario = $this->titreScenario($type, $request, $planSimule);

        // ── CLIENTS POUR LE FORMULAIRE ────────────────────────
        $clients = Client::where('user_id', auth()->id())
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return view('simulation.index', compact(
            'planReel',
            'planSimule',
            'impacts',
            'clients',
            'annee',
            'titreScenario',
            'type'
        ))->with('solde_initial', $request->solde_initial);
    }

    /**
     * calculerImpacts() — compare le plan réel et le plan simulé
     *
     * Retourne les différences pour chaque mois :
     * - diff_entrees    : entrées simulées − entrées réelles
     * - diff_sorties    : sorties simulées − sorties réelles
     * - diff_cumule     : cumulé simulé − cumulé réel
     * - mois_sauves     : mois qui passent de déficit/tension à OK
     * - mois_degrades   : mois qui passent de OK à déficit/tension
     */
    private function calculerImpacts($planReel, $planSimule): array
    {
        $impacts = [
            'mois_degrades'       => 0,
            'mois_sauves'         => 0,
            'diff_cumule_final'   => 0,
            'diff_entrees_total'  => 0,
            'diff_sorties_total'  => 0,
            'details'             => [],
        ];

        foreach ($planSimule as $i => $moisSimule) {
            $moisReel = $planReel[$i] ?? null;
            if (!$moisReel) continue;

            $diffCumule  = $moisSimule['solde_cumule'] - $moisReel->solde_cumule;
            $diffEntrees = $moisSimule['total_entrees'] - $moisReel->total_entrees;
            $diffSorties = $moisSimule['total_sorties'] - $moisReel->total_sorties;

            // Détecter les mois qui s'améliorent ou se dégradent
            $etaitProblematiqueReel = $moisReel->solde_cumule < 0 || $moisReel->solde_mois < 0;
            $estProblematiqueSim    = $moisSimule['en_deficit'] || $moisSimule['en_tension'];

            if (!$etaitProblematiqueReel && $estProblematiqueSim) {
                $impacts['mois_degrades']++;
            } elseif ($etaitProblematiqueReel && !$estProblematiqueSim) {
                $impacts['mois_sauves']++;
            }

            $impacts['diff_entrees_total'] += $diffEntrees;
            $impacts['diff_sorties_total'] += $diffSorties;
            $impacts['details'][] = [
                'diff_cumule'  => round($diffCumule, 2),
                'diff_entrees' => round($diffEntrees, 2),
                'diff_sorties' => round($diffSorties, 2),
            ];
        }

        // Impact sur le solde final (décembre)
        $dernierSimule = end($planSimule);
        $dernierReel   = $planReel->last();
        if ($dernierSimule && $dernierReel) {
            $impacts['diff_cumule_final'] = round(
                $dernierSimule['solde_cumule'] - $dernierReel->solde_cumule, 2
            );
        }

        return $impacts;
    }

    /**
     * titreScenario() — génère un titre lisible pour le scénario
     */
    private function titreScenario(string $type, Request $request, array $planSimule): string
    {
        switch ($type) {
            case 'retard_client':
                $client = Client::find($request->client_id);
                return "Retard de {$request->retard_mois} mois — Client : " . ($client?->nom ?? '');

            case 'baisse_activite':
                return "Baisse d'activité de {$request->taux_reduction}%";

            case 'charge_exceptionnelle':
                $moisLabel = Tresorerie::nomMois((int)$request->mois_impact);
                return "Charge exceptionnelle de " .
                    number_format((float)$request->montant_extra, 2, ',', ' ') .
                    " MAD en {$moisLabel}" .
                    ($request->libelle_extra ? " ({$request->libelle_extra})" : "");
        }

        return "Simulation";
    }
}
