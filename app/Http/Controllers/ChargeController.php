<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Tresorerie;
use App\Http\Requests\StoreChargeRequest;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    /**
     * index() — affiche la liste des charges
     * Filtres disponibles : mois, catégorie, type
     */
    public function index(Request $request)
{
    // Récupérer les valeurs des filtres (avec valeurs par défaut)
    $mois = $request->input('mois', now()->month);
    $annee = $request->input('annee', now()->year);
    $categorie = $request->input('categorie', '');

    // Récupérer les charges de l'utilisateur connecté
    $query = Charge::where('user_id', auth()->id());

    // Appliquer les filtres
    if ($mois && $annee) {
        $query->whereYear('date_prevue', $annee)
              ->whereMonth('date_prevue', $mois);
    }

    if ($categorie) {
        $query->where('categorie', $categorie);
    }

    // Pagination (15 par page)
    $charges = $query->orderBy('date_prevue')->paginate(15);

    // Calculer le total du mois
    $totalMois = Charge::where('user_id', auth()->id())
        ->whereYear('date_prevue', $annee)
        ->whereMonth('date_prevue', $mois)
        ->sum('montant');

    // Retourner la vue avec TOUTES les variables
    return view('charges.index', compact('charges', 'mois', 'annee', 'totalMois'));
}

    /**
     * create() — affiche le formulaire de création
     */
    public function create()
    {
        return view('charges.create');
    }

    /**
     * store() — enregistre une nouvelle charge
     * Si elle est récurrente, génère toutes les occurrences automatiquement
     */
    public function store(StoreChargeRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['payee']   = false; // par défaut non payée

        // Crée la charge principale
        $charge = Charge::create($data);

        // Si récurrence configurée → génère les occurrences futures
        // Ex : loyer mensuel → crée automatiquement une charge par mois
        $charge->genererOccurrences();

        // Recalcule le plan de trésorerie (les sorties ont changé)
        Tresorerie::calculerPlan(now()->year, 15000);

        // Message de succès selon si récurrente ou non
        $message = $charge->estRecurrente()
            ? 'Charge créée avec récurrence automatique.'
            : 'Charge créée avec succès.';

        return redirect()->route('charges.index')
            ->with('success', $message);
    }

    /**
     * show() — fiche détaillée (optionnel pour ce module)
     */
    public function show(Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);
        return view('charges.show', compact('charge'));
    }

    /**
     * edit() — formulaire de modification
     */
    public function edit(Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);
        return view('charges.edit', compact('charge'));
    }

    /**
     * update() — met à jour une charge
     */
    public function update(StoreChargeRequest $request, Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);

        $charge->update($request->validated());

        // Recalcul après modification
        Tresorerie::calculerPlan(now()->year, 15000);

        return redirect()->route('charges.index')
            ->with('success', 'Charge mise à jour.');
    }

    /**
     * destroy() — supprime une charge
     */
    public function destroy(Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);

        $charge->delete();

        // Recalcul après suppression
        Tresorerie::calculerPlan(now()->year, 15000);

        return redirect()->route('charges.index')
            ->with('success', 'Charge supprimée.');
    }

    /**
     * marquerPayee() — marque une charge comme payée
     */
    public function marquerPayee(Charge $charge)
    {
        abort_if($charge->user_id !== auth()->id(), 403);

        $charge->update(['payee' => true]);

        return back()->with('success', 'Charge marquée comme payée.');
    }
}