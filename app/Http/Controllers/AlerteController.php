<?php

namespace App\Http\Controllers;

use App\Models\Alerte;
use Illuminate\Http\Request;

class AlerteController extends Controller
{
    /**
     * index() — liste toutes les alertes
     * Triées par niveau de gravité : critique en premier
     */
    public function index(Request $request)
    {
        // Alertes actives (non résolues) triées par gravité
        $alertesActives = Alerte::nonResolues()
            ->orderByRaw("FIELD(niveau, 'critique', 'warning', 'info')")
            ->orderBy('created_at', 'desc')
            ->get();

        // Historique des alertes résolues (les 20 dernières)
        $alertesResolues = Alerte::where('resolue', true)
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        $stats = [
            'nb_critique' => $alertesActives->where('niveau', 'critique')->count(),
            'nb_warning'  => $alertesActives->where('niveau', 'warning')->count(),
            'nb_info'     => $alertesActives->where('niveau', 'info')->count(),
        ];

        return view('alertes.index', compact(
            'alertesActives', 'alertesResolues', 'stats'
        ));
    }

    /**
     * resoudre() — marque une alerte comme résolue
     * Appelé quand le gestionnaire clique "Résoudre"
     */
    public function resoudre(Alerte $alerte)
    {
        $alerte->marquerResolue();

        return back()->with('success', 'Alerte marquée comme résolue.');
    }
}