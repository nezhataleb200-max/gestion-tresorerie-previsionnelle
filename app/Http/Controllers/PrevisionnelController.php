<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Charge;
use App\Models\Tresorerie;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PrevisionnelController extends Controller
{
    /**
     * index() — affiche le volet prévisionnel
     *
     * Cette page analyse les encaissements prévus
     * et conseille sur quand et comment payer chaque charge.
     */
    public function index(Request $request)
    {
        $annee        = $request->get('annee', now()->year);
        $soldeActuel  = (float) $request->get('solde_actuel', 15000);

        // ── 1. ENCAISSEMENTS PRÉVUS ──────────────────────────
        // Ce sont les factures en attente — l'argent qui va rentrer
        $encaissements = Facture::with('client')
            ->whereHas('client', fn($q) =>
                $q->where('user_id', auth()->id())
            )
            ->whereIn('statut', ['en_attente', 'en_retard'])
            ->whereYear('date_echeance', $annee)
            ->orderBy('date_echeance')
            ->get()
            ->map(fn($f) => [
                'id'          => $f->id,
                'label'       => $f->numero . ' — ' . $f->client->nom,
                'montant'     => (float) $f->montant_ttc,
                'date'        => $f->date_echeance,
                'date_label'  => $f->date_echeance->format('d/m/Y'),
                'mois'        => $f->date_echeance->month,
                'type'        => 'encaissement',
                'statut'      => $f->statut,
            ]);

        // ── 2. CHARGES À PAYER ───────────────────────────────
        // Ce sont les charges non payées — l'argent qui doit sortir
        $chargesAPayer = Charge::where('user_id', auth()->id())
            ->where('payee', false)
            ->whereYear('date_prevue', $annee)
            ->orderBy('date_prevue')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'label'      => $c->libelle,
                'montant'    => (float) $c->montant,
                'date'       => $c->date_prevue,
                'date_label' => $c->date_prevue->format('d/m/Y'),
                'mois'       => $c->date_prevue->month,
                'categorie'  => $c->categorie,
                'type_charge'=> $c->type,
            ]);

        // ── 3. ALGORITHME PRÉVISIONNEL ────────────────────────
        $previsionnel = $this->calculerPrevisionnel(
            $encaissements,
            $chargesAPayer,
            $soldeActuel
        );

        return view('previsionnel.index', compact(
            'previsionnel',
            'encaissements',
            'chargesAPayer',
            'annee',
            'soldeActuel'
        ));
    }

    /**
     * calculerPrevisionnel() — cœur de l'algorithme
     *
     * Pour chaque charge, l'algorithme détermine :
     * - Si elle peut être payée à sa date prévue (solde suffisant)
     * - Sinon, à quelle date elle pourra être payée (après un encaissement)
     * - Si elle nécessite un crédit (aucun encaissement ne couvre)
     *
     * @param Collection $encaissements  Factures à encaisser
     * @param Collection $charges        Charges à payer
     * @param float      $soldeActuel    Trésorerie disponible maintenant
     * @return array     Plan prévisionnel complet
     */
    private function calculerPrevisionnel(
        $encaissements,
        $charges,
        float $soldeActuel
    ): array {
        $plan           = [];
        $solde          = $soldeActuel;
        $encToProcess   = $encaissements->toArray();
        $chargesTraitees = [];

        // ── Trier les charges par date prévue ────────────────
        $chargesSorted = $charges->sortBy('date')->values();

        foreach ($chargesSorted as $charge) {

            $dateCharge   = Carbon::parse($charge['date']);
            $peutPayer    = false;
            $datePaiement = null;
            $conseil      = '';
            $encaissementUtilise = null;
            $priorite     = 'normale';

            // ── Priorité de la charge ─────────────────────────
            // Les charges fixes (loyer, salaires) = priorité haute
            if (in_array($charge['categorie'], ['loyer', 'salaires'])) {
                $priorite = 'haute';
            } elseif (in_array($charge['categorie'], ['impots'])) {
                $priorite = 'obligatoire';
            }

            // ── Vérifier si le solde actuel suffit ───────────
            if ($solde >= $charge['montant']) {
                // On peut payer maintenant ou à la date prévue
                $peutPayer    = true;
                $datePaiement = $dateCharge;
                $solde        -= $charge['montant'];

                $conseil = $solde > 0
                    ? "Payable à la date prévue. Solde restant après paiement : "
                      . number_format($solde, 2, ',', ' ') . " MAD"
                    : "Payable mais attention : solde nul après paiement.";

            } else {
                // Solde insuffisant — chercher un encaissement futur
                $manque = $charge['montant'] - $solde;
                $encaissementTrouve = false;

                foreach ($encToProcess as $key => $enc) {
                    $dateEnc = Carbon::parse($enc['date']);

                    // Cet encaissement va-t-il arriver avant ou à la date de la charge ?
                    // Et couvre-t-il le montant manquant ?
                    if ($dateEnc->lte($dateCharge) && ($solde + $enc['montant']) >= $charge['montant']) {
                        // Cet encaissement couvre la charge
                        $solde             += $enc['montant'];
                        $encaissementUtilise = $enc;
                        unset($encToProcess[$key]); // Consommé
                        $solde             -= $charge['montant'];
                        $peutPayer          = true;
                        $datePaiement       = $dateCharge;
                        $encaissementTrouve = true;

                        $conseil = "Payable après encaissement de "
                            . number_format($enc['montant'], 2, ',', ' ')
                            . " MAD (" . $enc['label'] . " le " . $dateEnc->format('d/m/Y') . ").";
                        break;
                    }

                    // Encaissement arrive après la charge → payer après
                    if ($dateEnc->gt($dateCharge) && $enc['montant'] >= $manque) {
                        $datePaiementSimule = $dateEnc->copy()->addDays(2);
                        $solde             += $enc['montant'];
                        $encaissementUtilise = $enc;
                        unset($encToProcess[$key]);
                        $solde             -= $charge['montant'];
                        $peutPayer          = true;
                        $datePaiement       = $datePaiementSimule;
                        $encaissementTrouve = true;

                        $conseil = "⚠️ Décaler le paiement au "
                            . $datePaiementSimule->format('d/m/Y')
                            . " — après l'encaissement de "
                            . number_format($enc['montant'], 2, ',', ' ')
                            . " MAD (" . $enc['label'] . ").";
                        break;
                    }
                }

                // Aucun encaissement ne couvre
                if (!$encaissementTrouve) {
                    $peutPayer    = false;
                    $datePaiement = null;

                    $conseil = "🔴 Impossible à payer sans crédit. Manque : "
                        . number_format($manque, 2, ',', ' ')
                        . " MAD. Envisager un découvert bancaire ou reporter cette charge.";
                }
            }

            // ── Ajouter au plan ───────────────────────────────
            $plan[] = [
                'charge'              => $charge,
                'peut_payer'          => $peutPayer,
                'date_paiement'       => $datePaiement,
                'date_paiement_label' => $datePaiement?->format('d/m/Y') ?? 'Non déterminée',
                'retard'              => $datePaiement && $datePaiement->gt($dateCharge),
                'jours_retard'        => $datePaiement && $datePaiement->gt($dateCharge)
                                         ? $dateCharge->diffInDays($datePaiement)
                                         : 0,
                'conseil'             => $conseil,
                'solde_apres'         => $peutPayer ? round($solde, 2) : null,
                'encaissement_lie'    => $encaissementUtilise,
                'priorite'            => $priorite,
            ];
        }

        // ── Statistiques globales ─────────────────────────────
        $stats = [
            'total_charges'     => $charges->sum('montant'),
            'total_encaissements' => $encaissements->sum('montant'),
            'payables_a_temps'  => collect($plan)->where('peut_payer', true)->where('retard', false)->count(),
            'payables_en_retard'=> collect($plan)->where('peut_payer', true)->where('retard', true)->count(),
            'impossible'        => collect($plan)->where('peut_payer', false)->count(),
            'solde_final'       => $solde,
        ];

        return [
            'plan'  => $plan,
            'stats' => $stats,
        ];
    }
}
