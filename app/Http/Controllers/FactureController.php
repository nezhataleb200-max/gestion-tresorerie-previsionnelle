<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Client;
use App\Models\Tresorerie;
use App\Http\Requests\StoreFactureRequest;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $query = Facture::with('client')
            ->whereHas('client', fn($q) => $q->where('user_id', auth()->id()))
            ->orderBy('date_echeance');

        if ($request->filled('statut'))
            $query->where('statut', $request->statut);

        if ($request->filled('client_id'))
            $query->where('client_id', $request->client_id);

        $factures = $query->paginate(15);
        $clients = Client::actifs()->where('user_id', auth()->id())->orderBy('nom')->get();

        return view('factures.index', compact('factures', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = Client::actifs()->where('user_id', auth()->id())->orderBy('nom')->get();
        $clientId = $request->get('client_id');
        return view('factures.create', compact('clients', 'clientId'));
    }

    /**
     * store() — Crée une nouvelle facture
     * ✅ CORRIGÉ : Boucle sécurisée avec limite + vérification d'unicité
     */
    public function store(StoreFactureRequest $request)
    {
        $data = $request->validated();

        // Calcul du TTC
        $data['montant_ttc'] = Facture::calculerTTC($data['montant_ht'], $data['tva']);
        
        // ✅ Génération d'un numéro unique avec limite de sécurité (évite la boucle infinie)
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            $numero = Facture::genererNumero();
            $attempts++;
            
            if ($attempts > $maxAttempts) {
                return back()->with('error', 'Impossible de générer un numéro de facture unique. Veuillez réessayer ou contacter l\'administrateur.');
            }
            
        } while (Facture::where('numero', $numero)->exists());
        
        $data['numero'] = $numero;
        $data['statut'] = 'en_attente';

        $facture = Facture::create($data);

        // Recalcul automatique du plan de trésorerie
        Tresorerie::calculerPlan(now()->year, 15000);

        return redirect()->route('factures.show', $facture)
            ->with('success', 'Facture ' . $facture->numero . ' créée.');
    }

    public function show(Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);
        $facture->load('client');
        return view('factures.show', compact('facture'));
    }

    public function edit(Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);
        $clients = Client::actifs()->where('user_id', auth()->id())->get();
        return view('factures.edit', compact('facture', 'clients'));
    }

    public function update(StoreFactureRequest $request, Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);
        $data = $request->validated();
        $data['montant_ttc'] = Facture::calculerTTC($data['montant_ht'], $data['tva']);
        $facture->update($data);
        Tresorerie::calculerPlan(now()->year, 15000);
        return redirect()->route('factures.show', $facture)->with('success', 'Facture mise à jour.');
    }

    public function destroy(Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);
        $facture->delete();
        Tresorerie::calculerPlan(now()->year, 15000);
        return redirect()->route('factures.index')->with('success', 'Facture supprimée.');
    }

    /** Action : marquer une facture comme payée */
    public function marquerPayee(Request $request, Facture $facture)
    {
        abort_if($facture->client->user_id !== auth()->id(), 403);
        $facture->marquerPayee($request->date_paiement);
        Tresorerie::calculerPlan(now()->year, 15000);
        return back()->with('success', 'Facture marquée comme payée. Plan mis à jour.');
    }
}