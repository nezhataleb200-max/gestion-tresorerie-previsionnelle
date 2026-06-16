@extends('layouts.app')
@section('title', $client->nom)
@section('page-title', 'Fiche client')

@section('content')
<div class="row g-3">

    {{-- Colonne gauche : infos + stats --}}
    <div class="col-lg-4">

        {{-- Carte infos --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="mb-0">{{ $client->nom }}</h5>
                    <a href="{{ route('clients.edit', $client) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Type</td>
                        <td>{{ $client->type === 'societe' ? 'Société' : 'Particulier' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $client->email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Téléphone</td>
                        <td>{{ $client->telephone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Délai paiement</td>
                        <td>{{ $client->delai_paiement }} jours</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Client depuis</td>
                        <td>{{ $client->created_at->format('d/m/Y') }}</td>
                    </tr>
                </table>
                @if($client->notes)
                    <div class="mt-3 p-2 bg-light rounded small text-muted">
                        {{ $client->notes }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Stats financières --}}
        <div class="row g-2">
            <div class="col-6">
                <div class="card border-0 bg-light text-center p-2">
                    <div class="small text-muted">Total facturé</div>
                    <div class="fw-bold small">
                        {{ number_format($stats['total_facture'], 0, ',', ' ') }} MAD
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 text-center p-2" style="background:#d1fae5">
                    <div class="small text-muted">Encaissé</div>
                    <div class="fw-bold small text-success">
                        {{ number_format($stats['total_encaisse'], 0, ',', ' ') }} MAD
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 text-center p-2" style="background:#fef3c7">
                    <div class="small text-muted">En attente</div>
                    <div class="fw-bold small text-warning">
                        {{ number_format($stats['en_attente'], 0, ',', ' ') }} MAD
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 text-center p-2" style="background:#fee2e2">
                    <div class="small text-muted">En retard</div>
                    <div class="fw-bold small text-danger">
                        {{ $stats['nb_retard'] }} facture(s)
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne droite : historique factures --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Historique des factures</span>
                <a href="{{ route('factures.create', ['client_id' => $client->id]) }}"
                   class="btn btn-sm btn-dark">
                    <i class="bi bi-plus-lg"></i> Nouvelle facture
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>Montant TTC</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($factures as $f)
                        <tr>
                            <td>
                                <a href="{{ route('factures.show', $f) }}">
                                    {{ $f->numero }}
                                </a>
                            </td>
                            <td>{{ number_format($f->montant_ttc, 2) }} MAD</td>
                            <td>{{ $f->date_echeance->format('d/m/Y') }}</td>
                            <td>
                                @if($f->statut === 'payee')
                                    <span class="badge bg-success">Payée</span>
                                @elseif($f->statut === 'en_retard')
                                    <span class="badge bg-danger">En retard</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                Aucune facture pour ce client.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $factures->links() }}</div>
        </div>
    </div>

</div>

{{-- Bouton retour --}}
<div class="mt-3">
    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>
@endsection