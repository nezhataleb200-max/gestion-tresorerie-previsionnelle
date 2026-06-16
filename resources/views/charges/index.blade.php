@extends('layouts.app')
@section('title', 'Charges')
@section('page-title', 'Gestion des charges')

@section('content')

{{-- Barre de filtres + bouton créer --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">

        {{-- Filtre mois/année --}}
        <select name="mois" class="form-select form-select-sm" style="width:120px">
            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ $mois == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->locale('fr')->monthName }}
                </option>
            @endforeach
        </select>

        <select name="annee" class="form-select form-select-sm" style="width:90px">
            @foreach([2025, 2026, 2027] as $a)
                <option value="{{ $a }}" {{ $annee == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>

        {{-- Filtre catégorie --}}
        <select name="categorie" class="form-select form-select-sm" style="width:150px">
            <option value="">Toutes catégories</option>
            @foreach(['loyer'=>'Loyer','salaires'=>'Salaires','impots'=>'Impôts','fournisseurs'=>'Fournisseurs','services'=>'Services','autre'=>'Autre'] as $val=>$lab)
                <option value="{{ $val }}" {{ request('categorie')===$val ? 'selected':'' }}>{{ $lab }}</option>
            @endforeach
        </select>

        <button class="btn btn-sm btn-outline-secondary">Filtrer</button>
        <a href="{{ route('charges.index') }}" class="btn btn-sm btn-link text-muted">Réinitialiser</a>
    </form>

    <a href="{{ route('charges.create') }}" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg"></i> Nouvelle charge
    </a>
</div>

{{-- Total du mois --}}
<div class="alert alert-light border d-flex justify-content-between mb-3">
    <span>Total des charges — {{ \Carbon\Carbon::create()->month($mois)->locale('fr')->monthName }} {{ $annee }}</span>
    <strong class="text-danger">{{ number_format($totalMois, 2, ',', ' ') }} MAD</strong>
</div>

{{-- Tableau --}}
<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th>Libellé</th>
            <th>Montant</th>
            <th>Date prévue</th>
            <th>Catégorie</th>
            <th>Type</th>
            <th>Récurrence</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse($charges as $charge)
        <tr>
            <td>
                {{ $charge->libelle }}
                @if($charge->estRecurrente())
                    <span class="badge bg-info bg-opacity-10 text-info ms-1" style="font-size:10px">
                        {{ $charge->labelRecurrence() }}
                    </span>
                @endif
            </td>
            <td class="fw-500 text-danger">{{ number_format($charge->montant, 2, ',', ' ') }} MAD</td>
            <td>{{ $charge->date_prevue->format('d/m/Y') }}</td>
            <td><span class="badge bg-light text-dark border">{{ $charge->labelCategorie() }}</span></td>
            <td><span class="text-muted small">{{ $charge->type === 'fixe' ? 'Fixe' : 'Variable' }}</span></td>
            <td><span class="text-muted small">{{ $charge->labelRecurrence() }}</span></td>
            <td>
                @if($charge->payee)
                    <span class="badge bg-success">Payée</span>
                @else
                    <span class="badge bg-warning text-dark">À payer</span>
                @endif
            </td>
            <td class="d-flex gap-1">
                {{-- Marquer payée --}}
                @if(!$charge->payee)
                <form method="POST" action="{{ route('charges.payer', $charge) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-success" title="Marquer payée">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </form>
                @endif

                {{-- Modifier --}}
                <a href="{{ route('charges.edit', $charge) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                </a>

                {{-- Supprimer --}}
                <form method="POST" action="{{ route('charges.destroy', $charge) }}" class="d-inline"
                      onsubmit="return confirm('Supprimer cette charge ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-4">
                Aucune charge pour ce mois.
                <a href="{{ route('charges.create') }}">Créer la première</a>
            </td>
        </tr>
    @endforelse
    </tbody>
</table>
</div>
</div>

<div class="mt-3">{{ $charges->withQueryString()->links() }}</div>

@endsection