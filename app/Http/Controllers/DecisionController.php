<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Charge;
use App\Models\Alerte;
use App\Models\Tresorerie;
use App\Models\User;
use App\Mail\RappelFactureClient;
use App\Mail\AvertissementRetardClient;
use App\Mail\AlerteSoldeGestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class DecisionController extends Controller
{
    // Seuil de sécurité configurable (MAD)
    const SEUIL_SECURITE    = 10000;
    const JOURS_AVANT_RAPPEL = 7;

    /**
     * index() — Page du centre de décisions
     * Affiche toutes les décisions automatiques et leur état
     */
    public function index()
    {
        $annee = now()->year;

        // ── Factures proches de l'échéance (dans 7 jours) ────
        $facturesProches = Facture::with('client')
            ->whereHas('client', fn($q) =>
                $q->where('user_id', auth()->id())
            )
            ->where('statut', 'en_attente')
            ->whereBetween('date_echeance', [
                now(),
                now()->addDays(self::JOURS_AVANT_RAPPEL)
            ])
            ->orderBy('date_echeance')
            ->get();

        // ── Factures en retard ────────────────────────────────
        $facturesEnRetard = Facture::with('client')
            ->whereHas('client', fn($q) =>
                $q->where('user_id', auth()->id())
            )
            ->where('statut', 'en_retard')
            ->orderBy('date_echeance')
            ->get();

        // ── Solde actuel ──────────────────────────────────────
        $moisCourant = Tresorerie::where('annee', $annee)
            ->where('mois', now()->month)
            ->first();
        $soldeActuel = $moisCourant?->solde_cumule ?? 15000;

        // ── Charges reportables ───────────────────────────────
        // Charges non critiques du mois qui peuvent être décalées
        $chargesReportables = Charge::where('user_id', auth()->id())
            ->where('payee', false)
            ->whereYear('date_prevue', $annee)
            ->whereMonth('date_prevue', now()->month)
            ->whereNotIn('categorie', ['loyer', 'salaires', 'impots'])
            ->orderBy('montant', 'desc')
            ->get();

        // ── Suggestions si solde bas ──────────────────────────
        $suggestions = $soldeActuel < self::SEUIL_SECURITE
            ? $this->genererSuggestions($soldeActuel, $facturesEnRetard, $chargesReportables)
            : [];

        // ── Historique des décisions prises ───────────────────
        $historique = Alerte::where('resolue', false)
            ->orderByRaw("FIELD(niveau, 'critique', 'warning', 'info')")
            ->limit(10)
            ->get();

        return view('decisions.index', compact(
            'facturesProches',
            'facturesEnRetard',
            'soldeActuel',
            'chargesReportables',
            'suggestions',
            'historique',
            'annee'
        ));
    }

    // ══════════════════════════════════════════════════════════
    // DÉCISION 1 — Envoyer email de rappel à un client
    // ══════════════════════════════════════════════════════════
    /**
     * envoyerRappel() — Email de rappel avant échéance
     *
     * Envoie un email professionnel au client pour lui rappeler
     * que sa facture arrive à échéance dans X jours.
     */
    public function envoyerRappel(Facture $facture)
    {
        // Sécurité : vérifier que la facture appartient à l'utilisateur
        abort_if($facture->client->user_id !== auth()->id(), 403);

        // Calculer le nombre de jours restants
        $joursRestants = (int) now()->diffInDays($facture->date_echeance, false);

        // Vérifier que le client a un email
        if (!$facture->client->email) {
            return back()->with('error',
                "Le client {$facture->client->nom} n'a pas d'adresse email renseignée."
            );
        }

        // Envoyer l'email
        Mail::to($facture->client->email)
            ->send(new RappelFactureClient($facture, $joursRestants));

        // Enregistrer l'action dans les alertes pour traçabilité
        Alerte::create([
            'tresorerie_id' => null,
            'type'          => 'retard_facture',
            'niveau'        => 'info',
            'message'       => "Email de rappel envoyé à {$facture->client->nom} "
                             . "pour la facture {$facture->numero} "
                             . "(échéance : {$facture->date_echeance->format('d/m/Y')})",
            'mois_concerne' => $facture->date_echeance->format('Y-m') . '-01',
            'resolue'       => true, // Déjà traité
        ]);

        return back()->with('success',
            "Email de rappel envoyé à {$facture->client->email} pour la facture {$facture->numero}."
        );
    }

    // ══════════════════════════════════════════════════════════
    // DÉCISION 1B — Envoyer avertissement de retard
    // ══════════════════════════════════════════════════════════
    /**
     * envoyerAvertissement() — Email d'avertissement retard
     *
     * Envoie un email plus sévère au client quand sa facture
     * est en retard de paiement.
     */
    public function envoyerAvertissement(Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);

        $joursRetard = (int) $facture->date_echeance->diffInDays(now());

        if (!$facture->client->email) {
            return back()->with('error',
                "Pas d'email pour {$facture->client->nom}."
            );
        }

        Mail::to($facture->client->email)
            ->send(new AvertissementRetardClient($facture, $joursRetard));

        Alerte::create([
            'tresorerie_id' => null,
            'type'          => 'retard_facture',
            'niveau'        => 'warning',
            'message'       => "Avertissement de retard envoyé à {$facture->client->nom} "
                             . "— Facture {$facture->numero} — "
                             . "{$joursRetard} jours de retard",
            'mois_concerne' => $facture->date_echeance->format('Y-m') . '-01',
            'resolue'       => true,
        ]);

        return back()->with('success',
            "Email d'avertissement envoyé à {$facture->client->email}."
        );
    }

    // ══════════════════════════════════════════════════════════
    // DÉCISION 1C — Envoi automatique en masse (Scheduler)
    // ══════════════════════════════════════════════════════════
    /**
     * envoisAutomatiques() — Appelé chaque matin par le Scheduler
     *
     * Parcourt TOUTES les factures et envoie automatiquement :
     * - Rappel si échéance dans 7 jours
     * - Avertissement si en retard
     */
    public static function envoisAutomatiques(): void
    {
        // Rappels avant échéance
        $facturesProches = Facture::with('client')
            ->where('statut', 'en_attente')
            ->whereBetween('date_echeance', [
                now(),
                now()->addDays(self::JOURS_AVANT_RAPPEL)
            ])
            ->get();

        foreach ($facturesProches as $facture) {
            if (!$facture->client->email) continue;

            $joursRestants = (int) now()->diffInDays($facture->date_echeance, false);

            Mail::to($facture->client->email)
                ->send(new RappelFactureClient($facture, $joursRestants));
        }

        // Avertissements retards
        $facturesEnRetard = Facture::with('client')
            ->where('statut', 'en_retard')
            ->get();

        foreach ($facturesEnRetard as $facture) {
            if (!$facture->client->email) continue;

            $joursRetard = (int) $facture->date_echeance->diffInDays(now());

            Mail::to($facture->client->email)
                ->send(new AvertissementRetardClient($facture, $joursRetard));
        }
    }

    // ══════════════════════════════════════════════════════════
    // DÉCISION 2 — Reporter les charges non critiques
    // ══════════════════════════════════════════════════════════
    /**
     * reporterCharge() — Décale une charge d'un mois
     *
     * Si le solde est insuffisant pour couvrir toutes les charges,
     * l'utilisateur peut décaler les charges non critiques
     * au mois suivant.
     */
    public function reporterCharge(Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);

        // Empêcher le report des charges critiques
        if (in_array($charge->categorie, ['loyer', 'salaires', 'impots'])) {
            return back()->with('error',
                "Impossible de reporter une charge critique ({$charge->categorie}). "
                . "Ces charges ont une priorité obligatoire."
            );
        }

        $ancienneDate  = $charge->date_prevue->copy();
        $nouvelleDatePrevue = $charge->date_prevue->addMonth();

        // Mettre à jour la date prévue
        $charge->update([
            'date_prevue' => $nouvelleDatePrevue
        ]);

        // Recalculer le plan (les sorties ont changé)
        Tresorerie::calculerPlan(now()->year, 15000);

        // Créer une alerte informative pour traçabilité
        Alerte::create([
            'tresorerie_id' => null,
            'type'          => 'tension',
            'niveau'        => 'info',
            'message'       => "Charge \"{$charge->libelle}\" reportée du "
                             . $ancienneDate->format('d/m/Y')
                             . " au "
                             . $nouvelleDatePrevue->format('d/m/Y')
                             . " (solde insuffisant).",
            'mois_concerne' => $ancienneDate->format('Y-m') . '-01',
            'resolue'       => false,
        ]);

        return back()->with('success',
            "Charge \"{$charge->libelle}\" reportée au {$nouvelleDatePrevue->format('d/m/Y')}. "
            . "Le plan de trésorerie a été recalculé."
        );
    }

    /**
     * reporterToutesChargesNonCritiques() — Report en masse
     *
     * Reporte automatiquement toutes les charges non critiques
     * du mois courant au mois suivant.
     */
    public function reporterToutesChargesNonCritiques()
    {
        $charges = Charge::where('user_id', auth()->id())
            ->where('payee', false)
            ->whereYear('date_prevue', now()->year)
            ->whereMonth('date_prevue', now()->month)
            ->whereNotIn('categorie', ['loyer', 'salaires', 'impots'])
            ->get();

        $nbReportees = 0;
        $montantReporte = 0;

        foreach ($charges as $charge) {
            $montantReporte += $charge->montant;
            $charge->update([
                'date_prevue' => $charge->date_prevue->addMonth()
            ]);
            $nbReportees++;
        }

        if ($nbReportees > 0) {
            Tresorerie::calculerPlan(now()->year, 15000);

            Alerte::create([
                'tresorerie_id' => null,
                'type'          => 'tension',
                'niveau'        => 'info',
                'message'       => "{$nbReportees} charge(s) non critique(s) reportées automatiquement "
                                 . "au mois prochain. Montant total reporté : "
                                 . number_format($montantReporte, 2, ',', ' ') . " MAD",
                'mois_concerne' => now()->format('Y-m') . '-01',
                'resolue'       => false,
            ]);
        }

        return back()->with('success',
            "{$nbReportees} charge(s) reportée(s) — "
            . number_format($montantReporte, 0, ',', ' ')
            . " MAD décalés au mois prochain."
        );
    }

    // ══════════════════════════════════════════════════════════
    // DÉCISION 3 — Alerte seuil de sécurité
    // ══════════════════════════════════════════════════════════
    /**
     * verifierSeuilSecurite() — Appelé par le Scheduler
     *
     * Si le solde passe sous le seuil, envoie un email au
     * gestionnaire avec les suggestions d'actions.
     */
    public static function verifierSeuilSecurite(): void
    {
        $annee       = now()->year;
        $moisCourant = Tresorerie::where('annee', $annee)
            ->where('mois', now()->month)
            ->first();

        if (!$moisCourant) return;

        $soldeActuel = $moisCourant->solde_cumule;

        // Vérifier si le solde est sous le seuil
        if ($soldeActuel < self::SEUIL_SECURITE) {

            // Générer les suggestions automatiques
            $facturesEnRetard = Facture::with('client')
                ->where('statut', 'en_retard')
                ->get();

            $chargesReportables = Charge::where('payee', false)
                ->whereYear('date_prevue', $annee)
                ->whereMonth('date_prevue', now()->month)
                ->whereNotIn('categorie', ['loyer', 'salaires', 'impots'])
                ->get();

            $suggestions = [
                [
                    'icon'   => '📧',
                    'titre'  => 'Relancer ' . $facturesEnRetard->count() . ' client(s) en retard',
                    'detail' => 'Montant potentiel à récupérer : '
                                . number_format($facturesEnRetard->sum('montant_ttc'), 0, ',', ' ')
                                . ' MAD',
                ],
                [
                    'icon'   => '📅',
                    'titre'  => 'Reporter ' . $chargesReportables->count() . ' charge(s) non critique(s)',
                    'detail' => 'Économie immédiate possible : '
                                . number_format($chargesReportables->sum('montant'), 0, ',', ' ')
                                . ' MAD',
                ],
                [
                    'icon'   => '🏦',
                    'titre'  => 'Envisager un découvert bancaire',
                    'detail' => 'Manque par rapport au seuil de sécurité : '
                                . number_format(self::SEUIL_SECURITE - $soldeActuel, 0, ',', ' ')
                                . ' MAD',
                ],
                [
                    'icon'   => '📊',
                    'titre'  => 'Accélérer la facturation',
                    'detail' => 'Émettre les factures du mois suivant en avance pour anticiper les encaissements',
                ],
            ];

            // Envoyer l'email d'alerte à tous les admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(
                    new AlerteSoldeGestionnaire($soldeActuel, self::SEUIL_SECURITE, $suggestions)
                );
            }

            // Créer une alerte dans l'application
            Alerte::create([
                'tresorerie_id' => $moisCourant->id,
                'type'          => 'deficit',
                'niveau'        => 'critique',
                'message'       => "Solde sous le seuil de sécurité : "
                                 . number_format($soldeActuel, 2, ',', ' ')
                                 . " MAD (seuil : "
                                 . number_format(self::SEUIL_SECURITE, 0, ',', ' ')
                                 . " MAD). Email d'alerte envoyé aux administrateurs.",
                'mois_concerne' => now()->format('Y-m') . '-01',
                'resolue'       => false,
            ]);
        }
    }

    /**
     * genererSuggestions() — Génère les suggestions affichées dans la vue
     */
    private function genererSuggestions(float $solde, $facturesEnRetard, $chargesReportables): array
    {
        $suggestions = [];

        if ($facturesEnRetard->count() > 0) {
            $suggestions[] = [
                'type'    => 'relancer',
                'icon'    => '📧',
                'titre'   => 'Relancer ' . $facturesEnRetard->count() . ' client(s) en retard',
                'detail'  => 'Récupérer : ' . number_format($facturesEnRetard->sum('montant_ttc'), 0, ',', ' ') . ' MAD',
                'action'  => 'Envoyer avertissements',
                'couleur' => 'warning',
            ];
        }

        if ($chargesReportables->count() > 0) {
            $suggestions[] = [
                'type'    => 'reporter',
                'icon'    => '📅',
                'titre'   => 'Reporter ' . $chargesReportables->count() . ' charge(s) non critique(s)',
                'detail'  => 'Libérer : ' . number_format($chargesReportables->sum('montant'), 0, ',', ' ') . ' MAD',
                'action'  => 'Reporter au mois prochain',
                'couleur' => 'info',
            ];
        }

        $manque = self::SEUIL_SECURITE - $solde;
        if ($manque > 0) {
            $suggestions[] = [
                'type'    => 'credit',
                'icon'    => '🏦',
                'titre'   => 'Demander un découvert bancaire',
                'detail'  => 'Montant conseillé : ' . number_format($manque, 0, ',', ' ') . ' MAD',
                'action'  => 'Contacter votre banque',
                'couleur' => 'danger',
            ];
        }

        return $suggestions;
    }
}
