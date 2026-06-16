<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    // Affiche la liste des clients
    public function index(Request $request)
    {
        $query = Client::withCount('factures')
            ->where('user_id', auth()->id())
            ->where('actif', true);

        // Si l'utilisateur a tapé quelque chose dans la barre de recherche
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('nom', 'like', "%$term%")
                  ->orWhere('email', 'like', "%$term%");
            });
        }

        // Si l'utilisateur a sélectionné un type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // paginate(12) = 12 clients par page
        $clients = $query->orderBy('nom')->paginate(12);

        // Envoie les clients à la vue
        return view('clients.index', compact('clients'));
    }

    // Affiche le formulaire vide de création
    public function create()
    {
        return view('clients.create');
    }

    // Reçoit les données du formulaire et les enregistre
    public function store(StoreClientRequest $request)
    {
        // validated() retourne uniquement les champs validés
        $validated = $request->validated();

        // On ajoute l'id de l'utilisateur connecté
        $validated['user_id'] = auth()->id();

        // Création en base
        $client = Client::create($validated);

        // Redirection vers la fiche du client avec message de succès
        return redirect()->route('clients.show', $client)
            ->with('success', 'Client ' . $client->nom . ' créé avec succès.');
    }

    // Les autres méthodes (show, edit, update, destroy) → on les fait mercredi
    // Fiche détaillée d'un client
public function show(Client $client)
{
    // Sécurité : vérifie que ce client appartient bien à l'utilisateur connecté
    abort_if($client->user_id !== auth()->id(), 403);

    // Charge les factures paginées
    $factures = $client->factures()
        ->orderBy('date_echeance', 'desc')
        ->paginate(10);

    // Calcule les statistiques financières du client
    $stats = [
        'total_facture'  => $client->factures()->sum('montant_ttc'),
        'total_encaisse' => $client->factures()->where('statut', 'payee')->sum('montant_ttc'),
        'en_attente'     => $client->factures()->whereIn('statut', ['en_attente','en_retard'])->sum('montant_ttc'),
        'nb_retard'      => $client->factures()->where('statut', 'en_retard')->count(),
    ];

    return view('clients.show', compact('client', 'factures', 'stats'));
}

// Formulaire de modification
public function edit(Client $client)
{
    abort_if($client->user_id !== auth()->id(), 403);
    return view('clients.edit', compact('client'));
}

// Mise à jour en base
public function update(StoreClientRequest $request, Client $client)
{
    abort_if($client->user_id !== auth()->id(), 403);
    $client->update($request->validated());

    return redirect()->route('clients.show', $client)
        ->with('success', 'Client mis à jour avec succès.');
}

// Suppression ou archivage
public function destroy(Client $client)
{
    abort_if($client->user_id !== auth()->id(), 403);

    // Si le client a des factures, on archive au lieu de supprimer
    if ($client->factures()->exists()) {
        $client->update(['actif' => false]);
        return back()->with('success', 'Client archivé (il a des factures associées).');
    }

    $client->delete();
    return redirect()->route('clients.index')
        ->with('success', 'Client supprimé.');
}
}