<?php

namespace App\Http\Controllers;

use App\Models\Tresorerie;
use App\Models\Alerte;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;  

class TresorerieController extends Controller
{
    /**
     * index() — affiche le plan de trésorerie sur 12 mois
     */
    public function index(Request $request)
    {
        $annee = $request->get('annee', now()->year);

        // Recalcul automatique avant affichage
        $soldeInitial = $request->get('solde_initial', 15000);
        Tresorerie::calculerPlan($annee, $soldeInitial);

        // Récupère les 12 mois calculés triés chronologiquement
        $plan = Tresorerie::where('annee', $annee)
            ->orderBy('mois')
            ->get();

        // Statistiques annuelles pour le résumé en haut de page
        $stats = [
            'total_entrees_annee' => $plan->sum('total_entrees'),
            'total_sorties_annee' => $plan->sum('total_sorties'),
            'solde_final'         => $plan->last()?->solde_cumule ?? 0,
            'mois_deficitaires'   => $plan->filter(fn($m) => $m->solde_cumule < 0)->count(),
            'mois_tension'        => $plan->filter(fn($m) => $m->estEnTension())->count(),
        ];

        // Alertes non résolues à afficher en haut
        $alertes = Alerte::where('resolue', false)
            ->orderByRaw("FIELD(niveau, 'critique', 'warning', 'info')")
            ->limit(5)
            ->get();

        return view('plan.index', compact('plan', 'stats', 'alertes', 'annee', 'soldeInitial'));
    }

    /**
     * exportPdf() — exporte le plan en PDF
     * ✅ CORRIGÉ : Génère un vrai fichier PDF avec DomPDF
     */
    public function exportPdf(Request $request)
    {
        $annee = $request->get('annee', now()->year);
        $soldeInitial = $request->get('solde_initial', 15000);
        
        // S'assurer que le plan est calculé avec le bon solde initial
        Tresorerie::calculerPlan($annee, $soldeInitial);
        
        $plan = Tresorerie::where('annee', $annee)
            ->orderBy('mois')
            ->get();
        
        // Statistiques annuelles
        $stats = [
            'total_entrees_annee' => $plan->sum('total_entrees'),
            'total_sorties_annee' => $plan->sum('total_sorties'),
            'solde_final'         => $plan->last()?->solde_cumule ?? 0,
            'mois_deficitaires'   => $plan->filter(fn($m) => $m->solde_cumule < 0)->count(),
            'mois_tension'        => $plan->filter(fn($m) => $m->estEnTension())->count(),
        ];
        
        // Générer le PDF à partir de la vue
        $pdf = Pdf::loadView('plan.pdf', compact('plan', 'stats', 'annee', 'soldeInitial'));
        
        // Télécharger le PDF
        return $pdf->download("plan_tresorerie_{$annee}.pdf");
    }
}