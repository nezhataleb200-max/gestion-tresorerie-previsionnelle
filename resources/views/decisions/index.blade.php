@extends('layouts.app')
@section('title', 'Centre de décisions')
@section('page-title', 'Centre de décisions automatiques')

@section('content')

<div class="alert alert-info mb-4">
    <i class="bi bi-robot me-2"></i>
    <strong>Centre de décisions :</strong> L'application analyse votre situation financière
    et propose des actions concrètes. Les emails sont envoyés automatiquement chaque matin à 9h.
</div>

{{-- Solde actuel --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 text-center p-3"
             style="background:{{ $soldeActuel < 10000 ? '#fdecea' : '#e2f0ea' }}">
            <div class="small text-muted mb-1">Solde actuel</div>
            <div class="h4 mb-0 {{ $soldeActuel < 10000 ? 'text-danger' : 'text-success' }}">
                {{ number_format($soldeActuel, 0, ',', ' ') }} MAD
            </div>
            <div class="small text-muted mt-1">
                Seuil de sécurité : 10 000 MAD
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 text-center p-3 bg-light">
            <div class="small text-muted mb-1">Factures proches échéance</div>
            <div class="h4 text-warning mb-0">{{ $facturesProches->count() }}</div>
            <div class="small text-muted mt-1">Dans les 7 prochains jours</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 text-center p-3 bg-light">
            <div class="small text-muted mb-1">Factures en retard</div>
            <div class="h4 text-danger mb-0">{{ $facturesEnRetard->count() }}</div>
            <div class="small text-muted mt-1">
                {{ number_format($facturesEnRetard->sum('montant_ttc'), 0, ',', ' ') }} MAD à récupérer
            </div>
        </div>
    </div>
</div>

{{-- DÉCISION 1A : Rappels avant échéance --}}
@if($facturesProches->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <span class="fw-500">
            <i class="bi bi-envelope text-primary me-2"></i>
            Décision 1 — Rappels avant échéance ({{ $facturesProches->count() }} factures)
        </span>
    </div>
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Facture</th><th>Client</th>
                <th class="text-end">Montant</th>
                <th>Échéance</th><th>Jours restants</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($facturesProches as $f)
        <tr>
            <td class="fw-500">{{ $f->numero }}</td>
            <td>{{ $f->client->nom }}</td>
            <td class="text-end text-success">
                {{ number_format($f->montant_ttc, 2, ',', ' ') }} MAD
            </td>
            <td>{{ $f->date_echeance->format('d/m/Y') }}</td>
            <td>
                <span class="badge bg-warning text-dark">
                    {{ now()->diffInDays($f->date_echeance) }} jours
                </span>
            </td>
            <td>
                @if($f->client->email)
                <form method="POST" action="{{ route('decisions.rappel', $f) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-envelope me-1"></i> Envoyer rappel
                    </button>
                </form>
                @else
                    <span class="text-muted small">Pas d'email client</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- DÉCISION 1B : Avertissements retards --}}
@if($facturesEnRetard->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-500">
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            Décision 1B — Avertissements retards ({{ $facturesEnRetard->count() }} factures)
        </span>
    </div>
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Facture</th><th>Client</th>
                <th class="text-end">Montant</th>
                <th>Échéance</th><th>Retard</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($facturesEnRetard as $f)
        <tr class="table-danger">
            <td class="fw-500">{{ $f->numero }}</td>
            <td>{{ $f->client->nom }}</td>
            <td class="text-end text-danger fw-500">
                {{ number_format($f->montant_ttc, 2, ',', ' ') }} MAD
            </td>
            <td>{{ $f->date_echeance->format('d/m/Y') }}</td>
            <td>
                <span class="badge bg-danger">
                    {{ $f->date_echeance->diffInDays(now()) }} jours
                </span>
            </td>
            <td>
                @if($f->client->email)
                <form method="POST" action="{{ route('decisions.avertissement', $f) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-danger">
                        <i class="bi bi-envelope-exclamation me-1"></i> Avertissement
                    </button>
                </form>
                @else
                    <span class="text-muted small">Pas d'email</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- DÉCISION 2 : Reporter charges --}}
@if($chargesReportables->count() && $soldeActuel < 10000)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-500">
            <i class="bi bi-calendar-x text-warning me-2"></i>
            Décision 2 — Reporter charges non critiques
            ({{ $chargesReportables->count() }} charges —
            {{ number_format($chargesReportables->sum('montant'), 0, ',', ' ') }} MAD)
        </span>
        <form method="POST" action="{{ route('decisions.reporter-tout') }}">
            @csrf
            <button class="btn btn-sm btn-warning">
                <i class="bi bi-calendar-arrow-up me-1"></i>
                Reporter tout au mois prochain
            </button>
        </form>
    </div>
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Charge</th><th>Catégorie</th>
                <th class="text-end">Montant</th>
                <th>Date prévue</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($chargesReportables as $charge)
        <tr class="table-warning">
            <td>{{ $charge->libelle }}</td>
            <td><span class="badge bg-light text-dark border">{{ ucfirst($charge->categorie) }}</span></td>
            <td class="text-end text-danger fw-500">
                {{ number_format($charge->montant, 2, ',', ' ') }} MAD
            </td>
            <td>{{ $charge->date_prevue->format('d/m/Y') }}</td>
            <td>
                <form method="POST" action="{{ route('decisions.reporter', $charge) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-calendar-plus me-1"></i>
                        Reporter d'un mois
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- DÉCISION 3 : Suggestions si solde bas --}}
@if($soldeActuel < 10000 && count($suggestions) > 0)
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #C00000!important">
    <div class="card-header bg-white fw-500">
        <i class="bi bi-shield-exclamation text-danger me-2"></i>
        Décision 3 — Solde sous le seuil de sécurité — Actions recommandées
    </div>
    <div class="card-body">
    <div class="row g-3">
        @foreach($suggestions as $s)
        <div class="col-md-6">
            <div class="d-flex gap-3 p-3 rounded border"
                 style="background:var(--bs-{{ $s['couleur'] }}-bg-subtle, #f8f9fa)">
                <span style="font-size:24px">{{ $s['icon'] }}</span>
                <div>
                    <div class="fw-500">{{ $s['titre'] }}</div>
                    <div class="text-muted small">{{ $s['detail'] }}</div>
                    <div class="mt-2">
                        <span class="badge bg-{{ $s['couleur'] }}">
                            {{ $s['action'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    </div>
</div>
@endif

@if($soldeActuel >= 10000 && !$facturesProches->count() && !$facturesEnRetard->count())
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill me-2"></i>
    <strong>Situation financière saine.</strong>
    Aucune action urgente requise. Le système surveille automatiquement votre trésorerie.
</div>
@endif

@endsection