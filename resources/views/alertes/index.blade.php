@extends('layouts.app')
@section('title', 'Alertes')
@section('page-title', 'Centre des alertes')

@section('content')

{{-- Compteurs --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 text-center p-3" style="background:#fdecea">
            <div class="small text-danger mb-1">Alertes critiques</div>
            <div class="h4 text-danger mb-0">{{ $stats['nb_critique'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 text-center p-3" style="background:#fff3cd">
            <div class="small text-warning mb-1">Avertissements</div>
            <div class="h4 text-warning mb-0">{{ $stats['nb_warning'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 text-center p-3 bg-light">
            <div class="small text-muted mb-1">Informations</div>
            <div class="h4 text-muted mb-0">{{ $stats['nb_info'] }}</div>
        </div>
    </div>
</div>

{{-- Alertes actives --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-500">
        Alertes actives ({{ $alertesActives->count() }})
    </div>
    <div class="card-body p-0">
        @forelse($alertesActives as $alerte)
        <div class="d-flex justify-content-between align-items-start p-3
                    border-start border-4 border-{{ $alerte->classeBadge() }} mb-1"
             style="background:var(--bs-{{ $alerte->classeBadge() }}-bg, #fff)">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-{{ $alerte->classeBadge() }}">
                        {{ strtoupper($alerte->niveau) }}
                    </span>
                    <span class="small text-muted">
                        {{ $alerte->mois_concerne?->format('F Y') ?? 'Non daté' }}
                    </span>
                </div>
                <div>{{ $alerte->message }}</div>
            </div>
            <form method="POST" action="{{ route('alertes.resoudre', $alerte) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-secondary ms-3">
                    <i class="bi bi-check-lg"></i> Résoudre
                </button>
            </form>
        </div>
        @empty
        <div class="text-center text-success py-4">
            <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>
            Aucune alerte active — trésorerie en bonne santé !
        </div>
        @endforelse
    </div>
</div>

{{-- Historique --}}
@if($alertesResolues->count())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-500">
        Historique des alertes résolues ({{ $alertesResolues->count() }})
    </div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Message</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        @foreach($alertesResolues as $alerte)
        <tr class="text-muted">
            <td class="small">{{ $alerte->created_at->format('d/m/Y') }}</td>
            <td><span class="badge bg-secondary">{{ $alerte->type }}</span></td>
            <td class="small">{{ $alerte->message }}</td>
            <td><span class="badge bg-success">Résolue</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@endsection