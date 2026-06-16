@extends('layouts.app')
@section('title', 'Plan de trésorerie')
@section('page-title', 'Plan de trésorerie prévisionnel')

@section('content')

{{-- Alertes actives --}}
@if($alertes->count())
<div class="mb-4">
    @foreach($alertes as $alerte)
    <div class="alert {{ $alerte->niveau === 'critique' ? 'alert-danger' : ($alerte->niveau === 'warning' ? 'alert-warning' : 'alert-info') }}
                d-flex justify-content-between align-items-start mb-2">
        <div>
            <strong>
                {{ $alerte->niveau === 'critique' ? 'Déficit prévu' : 'Tension de trésorerie' }} —
            </strong>
            {{ $alerte->message }}
        </div>
        <form method="POST" action="{{ route('alertes.resoudre', $alerte) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-outline-secondary ms-2">Résoudre</button>
        </form>
    </div>
    @endforeach
</div>
@endif

{{-- Barre d'outils --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <form method="GET" class="d-flex gap-2">
        <select name="annee" class="form-select form-select-sm" style="width:90px">
            @foreach([2025, 2026, 2027] as $a)
                <option value="{{ $a }}" {{ $annee==$a?'selected':'' }}>{{ $a }}</option>
            @endforeach
        </select>
        <input type="number" name="solde_initial" class="form-control form-control-sm"
               style="width:160px" value="{{ $soldeInitial }}"
               placeholder="Solde initial MAD">
        <button class="btn btn-sm btn-outline-secondary">Recalculer</button>
    </form>
    <a href="{{ route('plan.export', ['annee' => $annee]) }}"
       class="btn btn-dark btn-sm" target="_blank">
        <i class="bi bi-file-pdf me-1"></i> Exporter PDF
    </a>
</div>

{{-- Statistiques annuelles --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Total entrées {{ $annee }}</div>
            <div class="h5 text-success mb-0">
                {{ number_format($stats['total_entrees_annee'], 0, ',', ' ') }} MAD
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Total sorties {{ $annee }}</div>
            <div class="h5 text-danger mb-0">
                {{ number_format($stats['total_sorties_annee'], 0, ',', ' ') }} MAD
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Solde final décembre</div>
            <div class="h5 {{ $stats['solde_final'] >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                {{ number_format($stats['solde_final'], 0, ',', ' ') }} MAD
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="small text-muted mb-1">Mois à risque</div>
            <div class="h5 {{ $stats['mois_deficitaires'] > 0 ? 'text-danger' : 'text-success' }} mb-0">
                {{ $stats['mois_deficitaires'] }} déficit
                / {{ $stats['mois_tension'] }} tension
            </div>
        </div>
    </div>
</div>

{{-- Tableau du plan --}}
<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table align-middle mb-0" id="tablePlan">
    <thead class="table-light">
        <tr>
            <th>Mois</th>
            <th class="text-end">Entrées prévues</th>
            <th class="text-end">Sorties prévues</th>
            <th class="text-end">Solde du mois</th>
            <th class="text-end">Solde cumulé</th>
            <th class="text-center">Statut</th>
        </tr>
    </thead>
    <tbody>
    @forelse($plan as $ligne)
        {{-- Code couleur selon le statut --}}
        @php
            $rowClass = '';
            if ($ligne->estDeficitaire())  $rowClass = 'table-danger';
            elseif ($ligne->estEnTension()) $rowClass = 'table-warning';
        @endphp
        <tr class="{{ $rowClass }}">
            <td class="fw-500">
                {{ $ligne->labelMois() }}
                @if($ligne->mois === now()->month && $ligne->annee === now()->year)
                    <span class="badge bg-primary ms-1" style="font-size:10px">Mois courant</span>
                @endif
            </td>

            <td class="text-end text-success">
                {{ number_format($ligne->total_entrees, 2, ',', ' ') }} MAD
            </td>

            <td class="text-end text-danger">
                {{ number_format($ligne->total_sorties, 2, ',', ' ') }} MAD
            </td>

            <td class="text-end {{ $ligne->solde_mois >= 0 ? 'text-success' : 'text-danger' }} fw-500">
                {{ $ligne->solde_mois >= 0 ? '+' : '' }}{{ number_format($ligne->solde_mois, 2, ',', ' ') }} MAD
            </td>

            <td class="text-end {{ $ligne->solde_cumule >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                {{ $ligne->solde_cumule >= 0 ? '+' : '' }}{{ number_format($ligne->solde_cumule, 2, ',', ' ') }} MAD
            </td>

            <td class="text-center">
                @if($ligne->estDeficitaire())
                    <span class="badge bg-danger">Déficit</span>
                @elseif($ligne->estEnTension())
                    <span class="badge bg-warning text-dark">Tension</span>
                @else
                    <span class="badge bg-success">OK</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center text-muted py-4">
                Aucune donnée. Ajoutez des factures et des charges.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>
</div>
</div>

{{-- Légende --}}
<div class="d-flex gap-3 mt-3 small text-muted">
    <span class="d-flex align-items-center gap-1">
        <span style="width:14px;height:14px;background:#f8d7da;border:1px solid #f5c6cb;display:inline-block;border-radius:2px"></span>
        Déficit — solde cumulé négatif
    </span>
    <span class="d-flex align-items-center gap-1">
        <span style="width:14px;height:14px;background:#fff3cd;border:1px solid #ffc107;display:inline-block;border-radius:2px"></span>
        Tension — solde mensuel négatif mais cumulé encore positif
    </span>
</div>

@endsection