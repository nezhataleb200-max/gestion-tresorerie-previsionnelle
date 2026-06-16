@extends('layouts.app')
@section('title', 'Clients')
@section('page-title', 'Gestion des clients')

@section('content')

{{-- Barre de recherche + bouton créer --}}
<div class="d-flex justify-content-between align-items-center mb-4">

    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Rechercher un client..."
               value="{{ request('search') }}" style="width:220px">

        <select name="type" class="form-select form-select-sm" style="width:140px">
            <option value="">Tous les types</option>
            <option value="societe"     {{ request('type')==='societe' ? 'selected' : '' }}>Société</option>
            <option value="particulier" {{ request('type')==='particulier' ? 'selected' : '' }}>Particulier</option>
        </select>

        <button class="btn btn-sm btn-outline-secondary">Filtrer</button>
        <a href="{{ route('clients.index') }}" class="btn btn-sm btn-link text-muted">Effacer</a>
    </form>

    <a href="{{ route('clients.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg"></i> Nouveau client
    </a>
</div>

{{-- Tableau des clients --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Email</th>
                    <th>Délai paiement</th>
                    <th>Factures</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <a href="{{ route('clients.show', $client) }}"
                           class="fw-500 text-decoration-none text-dark">
                            {{ $client->nom }}
                        </a>
                        @if($client->telephone)
                            <div class="text-muted small">{{ $client->telephone }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            {{ $client->type === 'societe' ? 'Société' : 'Particulier' }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $client->email ?? '—' }}</td>
                    <td>{{ $client->delai_paiement }} jours</td>
                    <td>
                        <span class="badge bg-secondary">{{ $client->factures_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('clients.edit', $client) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('clients.destroy', $client) }}"
                              class="d-inline"
                              onsubmit="return confirm('Supprimer {{ $client->nom }} ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Aucun client. <a href="{{ route('clients.create') }}">Créer le premier</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-3">
    {{ $clients->withQueryString()->links() }}
</div>

@endsection