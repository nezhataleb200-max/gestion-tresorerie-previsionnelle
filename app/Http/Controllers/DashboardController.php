<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Charge;
use App\Models\Client;
use App\Models\Alerte;
use App\Models\Tresorerie;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * index() — Prépare toutes les données pour le tableau de bord
     *
     * Cette méthode collecte 5 types de données :
     * 1. Les KPIs (indicateurs clés) du mois courant
     * 2. Les données des graphiques Chart.js (12 mois)
     * 3. Les alertes non résolues
     * 4. Les factures urgentes
     * 5. La répartition des charges par catégorie
     */
    public function index()
    {
        $annee = now()->year;
        $mois  = now()->month;

        // ── 1. RECALCUL DU PLAN ──────────────────────────────
        // On s'assure que le plan est à jour avant d'afficher
        Tresorerie::calculerPlan($annee, 15000);

        // ── 2. DONNÉES DU MOIS COURANT ───────────────────────
        // On récupère la ligne du mois courant dans tresorerie_mensuelle
        $moisCourant = Tresorerie::where('annee', $annee)
            ->where('mois', $mois)
            ->first();

        // Les 4 KPIs affichés en haut du dashboard
        $kpis = [
            // Trésorerie actuelle = solde cumulé du mois courant
            'tresorerie_actuelle'  => $moisCourant?->solde_cumule ?? 15000,

            // Entrées prévues ce mois = somme des factures en attente ce mois
            'entrees_mois'         => $moisCourant?->total_entrees ?? 0,

            // Sorties prévues ce mois = somme des charges ce mois
            'sorties_mois'         => $moisCourant?->total_sorties ?? 0,

            // Nombre de factures en retard (statut = 'en_retard')
            'factures_en_retard'   => Facture::where('statut', 'en_retard')->count(),

            // Nombre de clients actifs
            'nb_clients'           => Client::where('user_id', auth()->id())
                                           ->where('actif', true)->count(),
        ];

        // ── 3. DONNÉES GRAPHIQUES (12 MOIS) ──────────────────
        // On récupère les 12 mois pour les graphiques Chart.js
        // Ces données seront transformées en JSON pour JavaScript
        $planAnnuel = Tresorerie::where('annee', $annee)
            ->orderBy('mois')
            ->get();

        // Transformation en tableaux simples pour Chart.js
        // PHP → JSON → JavaScript
        $graphiqueData = [
            // Labels des mois pour l'axe X du graphique
            'labels'   => $planAnnuel->map(fn($m) =>
                Tresorerie::nomMois($m->mois)
            )->toArray(),

            // Entrées par mois (barres vertes)
            'entrees'  => $planAnnuel->pluck('total_entrees')->toArray(),

            // Sorties par mois (barres rouges)
            'sorties'  => $planAnnuel->pluck('total_sorties')->toArray(),

            // Solde cumulé par mois (courbe bleue)
            'cumules'  => $planAnnuel->pluck('solde_cumule')->toArray(),
        ];

        // ── 4. ALERTES ACTIVES ────────────────────────────────
        // Les 5 alertes les plus graves non résolues
        $alertes = Alerte::where('resolue', false)
            ->orderByRaw("FIELD(niveau, 'critique', 'warning', 'info')")
            ->limit(5)
            ->get();

        // ── 5. FACTURES URGENTES ─────────────────────────────
        // Factures en retard OU dont l'échéance arrive dans 7 jours
        $facturesUrgentes = Facture::with('client')
            ->whereHas('client', fn($q) =>
                $q->where('user_id', auth()->id())
            )
            ->where(function ($q) {
                $q->where('statut', 'en_retard')
                  ->orWhere(function ($q2) {
                      $q2->where('statut', 'en_attente')
                         ->where('date_echeance', '<=', now()->addDays(7));
                  });
            })
            ->orderBy('date_echeance')
            ->limit(5)
            ->get();

        // ── 6. RÉPARTITION CHARGES PAR CATÉGORIE ─────────────
        // Pour le graphique camembert (doughnut chart)
        // On groupe les charges du mois par catégorie et on somme
        $chargesParCategorie = Charge::where('user_id', auth()->id())
            ->whereYear('date_prevue', $annee)
            ->whereMonth('date_prevue', $mois)
            ->selectRaw('categorie, SUM(montant) as total')
            ->groupBy('categorie')
            ->get();

        // Transformation pour Chart.js
        $camembertData = [
            'labels'  => $chargesParCategorie->pluck('categorie')
                            ->map(fn($c) => ucfirst($c))->toArray(),
            'valeurs' => $chargesParCategorie->pluck('total')->toArray(),
        ];

        return view('dashboard.index', compact(
            'kpis',
            'graphiqueData',
            'alertes',
            'facturesUrgentes',
            'camembertData',
            'annee',
            'mois'
        ));
    }

    /**
     * exportPdf() — Exporte le dashboard en PDF
     * Utilise DomPDF (barryvdh/laravel-dompdf)
     */
    public function exportPdf()
    {
        $annee     = now()->year;
        $planAnnuel = Tresorerie::where('annee', $annee)
            ->orderBy('mois')->get();

        $kpis = [
            'tresorerie_actuelle' => $planAnnuel->last()?->solde_cumule ?? 0,
            'total_entrees'       => $planAnnuel->sum('total_entrees'),
            'total_sorties'       => $planAnnuel->sum('total_sorties'),
        ];

        // Génère la vue HTML du PDF
        $html = view('dashboard.pdf', compact('planAnnuel', 'kpis', 'annee'))
            ->render();

        // Retourne le HTML dans une nouvelle fenêtre (print-friendly)
        // Si DomPDF est installé : return Pdf::loadHTML($html)->download("dashboard_{$annee}.pdf");
        return response($html)->header('Content-Type', 'text/html');
    }
}