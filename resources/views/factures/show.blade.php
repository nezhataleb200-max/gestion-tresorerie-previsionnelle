
@extends('layouts.app')
@section('title', $facture->numero)
@section('page-title', 'Détail facture')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-bold">{{ $facture->numero }}</span>
    <div class="d-flex gap-2">
      <a href="{{ route('factures.edit', $facture) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil"></i> Modifier
      </a>
      @if($facture->statut !== 'payee')
      <form method="POST" action="{{ route('factures.payer', $facture) }}">
        @csrf @method('PATCH')
        <input type="date" name="date_paiement" class="form-control form-control-sm d-inline"
               value="{{ now()->toDateString() }}" style="width:160px">
        <button class="btn btn-sm btn-success">
          <i class="bi bi-check-circle"></i> Marquer payée
        </button>
      </form>
      @endif
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <table class="table table-sm table-borderless">
          <tr><td class="text-muted">Client</td>
            <td><a href="{{ route('clients.show', $facture->client) }}">{{ $facture->client->nom }}</a></td></tr>
          <tr><td class="text-muted">Montant HT</td><td>{{ number_format($facture->montant_ht,2) }} MAD</td></tr>
          <tr><td class="text-muted">TVA</td><td>{{ $facture->tva }}%</td></tr>
          <tr><td class="text-muted">Montant TTC</td>
            <td><strong class="text-success fs-5">{{ number_format($facture->montant_ttc,2) }} MAD</strong></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm table-borderless">
          <tr><td class="text-muted">Date émission</td><td>{{ $facture->date_emission->format('d/m/Y') }}</td></tr>
          <tr><td class="text-muted">Date échéance</td><td>{{ $facture->date_echeance->format('d/m/Y') }}</td></tr>
          <tr><td class="text-muted">Date paiement</td>
            <td>{{ $facture->date_paiement ? $facture->date_paiement->format('d/m/Y') : '—' }}</td></tr>
          <tr><td class="text-muted">Statut</td><td>
            @if($facture->statut==='payee') <span class="badge bg-success">Payée</span>
            @elseif($facture->statut==='en_retard') <span class="badge bg-danger">En retard</span>
            @else <span class="badge bg-warning text-dark">En attente</span>
            @endif
          </td></tr>
        </table>
      </div>
      @if($facture->description)
      <div class="col-12">
        <div class="bg-light p-3 rounded small text-muted">
          <strong>Prestation :</strong> {{ $facture->description }}
        </div>
      </div>
      @endif
    </div>
  </div>
</div>

<a href="{{ route('factures.index') }}" class="btn btn-outline-secondary btn-sm">
  <i class="bi bi-arrow-left"></i> Retour à la liste
</a>
</div></div>
@endsection
