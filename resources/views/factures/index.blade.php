
@extends('layouts.app')
@section('title', 'Factures')
@section('page-title', 'Gestion des factures')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <form method="GET" class="d-flex gap-2">
    <select name="statut" class="form-select form-select-sm" style="width:150px">
      <option value="">Tous statuts</option>
      <option value="en_attente" {{ request('statut')==='en_attente'?'selected':'' }}>En attente</option>
      <option value="en_retard" {{ request('statut')==='en_retard'?'selected':'' }}>En retard</option>
      <option value="payee" {{ request('statut')==='payee'?'selected':'' }}>Payées</option>
    </select>
    <select name="client_id" class="form-select form-select-sm" style="width:180px">
      <option value="">Tous clients</option>
      @foreach($clients as $c)
      <option value="{{ $c->id }}" {{ request('client_id')==$c->id?'selected':'' }}>{{ $c->nom }}</option>
      @endforeach
    </select>
    <button class="btn btn-sm btn-outline-secondary">Filtrer</button>
    <a href="{{ route('factures.index') }}" class="btn btn-sm btn-link text-muted">Effacer</a>
  </form>
  <a href="{{ route('factures.create') }}" class="btn btn-dark btn-sm">
    <i class="bi bi-plus-lg"></i> Nouvelle facture
  </a>
</div>

<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead class="table-light">
    <tr><th>N°</th><th>Client</th><th>Montant TTC</th><th>Émission</th><th>Échéance</th><th>Statut</th><th>Actions</th></tr>
  </thead>
  <tbody>
  @forelse($factures as $f)
  <tr>
    <td><a href="{{ route('factures.show', $f) }}" class="fw-500 text-decoration-none">{{ $f->numero }}</a></td>
    <td>{{ $f->client->nom }}</td>
    <td class="fw-500">{{ number_format($f->montant_ttc, 2) }} MAD</td>
    <td class="text-muted small">{{ $f->date_emission->format('d/m/Y') }}</td>
    <td>
      {{ $f->date_echeance->format('d/m/Y') }}
      @if($f->statut === 'en_retard')
        <div class="small text-danger">{{ $f->date_echeance->diffForHumans() }}</div>
      @endif
    </td>
    <td>
      @if($f->statut === 'payee') <span class="badge bg-success">Payée</span>
      @elseif($f->statut === 'en_retard') <span class="badge bg-danger">En retard</span>
      @else <span class="badge bg-warning text-dark">En attente</span>
      @endif
    </td>
    <td>
      @if($f->statut !== 'payee')
      <form method="POST" action="{{ route('factures.payer', $f) }}" class="d-inline">
        @csrf @method('PATCH')
        <button class="btn btn-sm btn-outline-success" title="Marquer payée">
          <i class="bi bi-check-circle"></i>
        </button>
      </form>
      @endif
      <a href="{{ route('factures.edit', $f) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
      <form method="POST" action="{{ route('factures.destroy', $f) }}" class="d-inline"
            onsubmit="return confirm('Supprimer la facture {{ $f->numero }} ?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
      </form>
    </td>
  </tr>
  @empty
  <tr><td colspan="7" class="text-center text-muted py-4">Aucune facture.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
</div>
<div class="mt-3">{{ $factures->withQueryString()->links() }}</div>
@endsection
