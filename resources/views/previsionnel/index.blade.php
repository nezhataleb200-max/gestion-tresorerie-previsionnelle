@extends('layouts.app')
@section('title', 'Volet prévisionnel')
@section('page-title', 'Volet prévisionnel de liquidité')

@section('content')

{{-- ── INTRODUCTION ──────────────────────────────── --}}
<div class="alert alert-info mb-4">
    <i class="bi bi-lightbulb me-2"></i>
    <strong>Ce module analyse vos encaissements prévus et vous conseille
    sur quand et comment payer chaque charge.</strong>
    Il calcule automatiquement l'ordre optimal de paiement selon
    votre trésorerie disponible.
</div>

{{-- ── PARAMÈTRES ───────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-500">
        <i class="bi bi-sliders me-2"></i>Paramètres
    </div>
    <div class="card-body">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Solde actuel disponible</label>
            <div class="input-group">
                <input type="number" name="solde_actuel"
                       class="form-control"
                       value="{{ $soldeActuel }}"
                       step="100">
                <span class="input-group-text">MAD</span>
            </div>
            <div class="form-text">Trésorerie en caisse aujourd'hui</div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Année</label>
            <select name="annee" class="form-select">
                @foreach([2025, 2026, 2027] as $a)
                    <option value="{{ $a }}"
                        {{ $annee == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">
                <i class="bi bi-calculator me-1"></i> Calculer
            </button>
        </div>
    </form>
    </div>
</div>

{{-- ── STATISTIQUES GLOBALES ─────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Total charges à payer</div>
            <div class="h5 text-danger mb-0">
                {{ number_format($previsionnel['stats']['total_charges'], 0, ',', ' ') }} MAD
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Total encaissements prévus</div>
            <div class="h5 text-success mb-0">
                {{ number_format($previsionnel['stats']['total_encaissements'], 0, ',', ' ') }} MAD
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 text-center p-3" style="background:#e2f0ea">
            <div class="small text-success mb-1">Payables à temps</div>
            <div class="h5 text-success mb-0">
                {{ $previsionnel['stats']['payables_a_temps'] }}
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 text-center p-3" style="background:#fff3cd">
            <div class="small text-warning mb-1">Payables en retard</div>
            <div class="h5 text-warning mb-0">
                {{ $previsionnel['stats']['payables_en_retard'] }}
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 text-center p-3" style="background:#fdecea">
            <div class="small text-danger mb-1">Impossible sans crédit</div>
            <div class="h5 text-danger mb-0">
                {{ $previsionnel['stats']['impossible'] }}
            </div>
        </div>
    </div>
</div>

{{-- ── PLAN PRÉVISIONNEL ─────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-500">
            <i class="bi bi-calendar-check me-2"></i>
            Plan de paiement recommandé
        </span>
        <span class="badge bg-dark">{{ count($previsionnel['plan']) }} charges analysées</span>
    </div>
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Charge</th>
                <th>Catégorie</th>
                <th class="text-end">Montant</th>
                <th>Date prévue</th>
                <th>Date conseillée</th>
                <th>Conseil de l'application</th>
                <th class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse($previsionnel['plan'] as $item)
            @php
                // Couleur de la ligne selon le statut
                if (!$item['peut_payer'])
                    $rowClass = 'table-danger';
                elseif ($item['retard'])
                    $rowClass = 'table-warning';
                else
                    $rowClass = '';
            @endphp
            <tr class="{{ $rowClass }}">

                <td>
                    <div class="fw-500">{{ $item['charge']['label'] }}</div>
                    @if($item['priorite'] === 'obligatoire')
                        <span class="badge" style="background:#6f42c1;font-size:10px">Obligatoire</span>
                    @elseif($item['priorite'] === 'haute')
                        <span class="badge bg-warning text-dark" style="font-size:10px">Priorité haute</span>
                    @endif
                </td>

                <td>
                    <span class="badge bg-light text-dark border">
                        {{ ucfirst($item['charge']['categorie']) }}
                    </span>
                </td>

                <td class="text-end fw-500 text-danger">
                    {{ number_format($item['charge']['montant'], 2, ',', ' ') }} MAD
                </td>

                <td class="text-muted small">
                    {{ $item['charge']['date_label'] }}
                </td>

                {{-- Date conseillée par l'application --}}
                <td>
                    @if($item['peut_payer'])
                        <span class="{{ $item['retard'] ? 'text-warning fw-500' : 'text-success fw-500' }}">
                            {{ $item['date_paiement_label'] }}
                        </span>
                        @if($item['retard'])
                            <div class="text-danger" style="font-size:10px">
                                {{ $item['jours_retard'] }} jours de décalage
                            </div>
                        @endif
                    @else
                        <span class="text-danger fw-500">—</span>
                    @endif
                </td>

                {{-- Conseil de l'application --}}
                <td style="max-width:280px;font-size:12px">
                    {{ $item['conseil'] }}
                    @if($item['encaissement_lie'])
                        <div class="mt-1">
                            <span class="badge bg-success" style="font-size:10px">
                                <i class="bi bi-arrow-down-circle me-1"></i>
                                Lié à : {{ $item['encaissement_lie']['label'] }}
                            </span>
                        </div>
                    @endif
                </td>

                {{-- Statut --}}
                <td class="text-center">
                    @if(!$item['peut_payer'])
                        <span class="badge bg-danger">Impossible</span>
                    @elseif($item['retard'])
                        <span class="badge bg-warning text-dark">À décaler</span>
                    @else
                        <span class="badge bg-success">OK</span>
                    @endif
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    Aucune charge à analyser pour cette période.
                </td>
            </tr>
        @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="2" class="fw-500">Solde final estimé après tous les paiements</td>
                <td class="text-end fw-bold {{ $previsionnel['stats']['solde_final'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($previsionnel['stats']['solde_final'], 0, ',', ' ') }} MAD
                </td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

{{-- ── ENCAISSEMENTS PRÉVUS ─────────────────────── --}}
<div class="row g-3">
<div class="col-md-6">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-500">
        <i class="bi bi-arrow-down-circle text-success me-2"></i>
        Encaissements prévus ({{ $encaissements->count() }})
    </div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Facture</th>
                <th class="text-end">Montant</th>
                <th>Échéance</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse($encaissements as $enc)
        <tr>
            <td style="font-size:12px">{{ $enc['label'] }}</td>
            <td class="text-end text-success fw-500" style="font-size:12px">
                {{ number_format($enc['montant'], 0, ',', ' ') }} MAD
            </td>
            <td class="text-muted" style="font-size:12px">{{ $enc['date_label'] }}</td>
            <td>
                <span class="badge {{ $enc['statut'] === 'en_retard' ? 'bg-danger' : 'bg-warning text-dark' }}"
                      style="font-size:10px">
                    {{ $enc['statut'] === 'en_retard' ? 'En retard' : 'En attente' }}
                </span>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">Aucun encaissement prévu</td></tr>
        @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td class="fw-500">Total</td>
                <td class="text-end fw-bold text-success">
                    {{ number_format($encaissements->sum('montant'), 0, ',', ' ') }} MAD
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
</div>

{{-- Charges à payer --}}
<div class="col-md-6">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-500">
        <i class="bi bi-arrow-up-circle text-danger me-2"></i>
        Charges à payer ({{ $chargesAPayer->count() }})
    </div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Charge</th>
                <th class="text-end">Montant</th>
                <th>Date prévue</th>
                <th>Priorité</th>
            </tr>
        </thead>
        <tbody>
        @forelse($chargesAPayer as $charge)
        <tr>
            <td style="font-size:12px">{{ $charge['label'] }}</td>
            <td class="text-end text-danger fw-500" style="font-size:12px">
                {{ number_format($charge['montant'], 0, ',', ' ') }} MAD
            </td>
            <td class="text-muted" style="font-size:12px">{{ $charge['date_label'] }}</td>
            <td>
                @if(in_array($charge['categorie'], ['impots']))
                    <span class="badge" style="background:#6f42c1;font-size:10px">Obligatoire</span>
                @elseif(in_array($charge['categorie'], ['loyer','salaires']))
                    <span class="badge bg-warning text-dark" style="font-size:10px">Haute</span>
                @else
                    <span class="badge bg-light text-dark border" style="font-size:10px">Normale</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">Aucune charge à payer</td></tr>
        @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td class="fw-500">Total</td>
                <td class="text-end fw-bold text-danger">
                    {{ number_format($chargesAPayer->sum('montant'), 0, ',', ' ') }} MAD
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
</div>
</div>

@endsection