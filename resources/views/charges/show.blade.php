@extends('layouts.app')

@section('title', 'Charge : ' . $charge->libelle)
@section('page-title', 'Détail de la charge')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">

    {{-- Carte principale --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-receipt me-2"></i>
                {{ $charge->libelle }}
            </span>
            <div>
                <a href="{{ route('charges.edit', $charge) }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="{{ route('charges.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Libellé :</td>
                            <td class="fw-bold">{{ $charge->libelle }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Catégorie :</td>
                            <td>
                                <span class="badge bg-secondary">{{ ucfirst($charge->categorie) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Type :</td>
                            <td>
                                <span class="badge {{ $charge->type === 'fixe' ? 'bg-info' : 'bg-warning' }}">
                                    {{ $charge->type === 'fixe' ? 'Fixe' : 'Variable' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Montant :</td>
                            <td class="fw-bold fs-5">{{ number_format($charge->montant, 2) }} MAD</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Date prévue :</td>
                            <td>{{ $charge->date_prevue->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Récurrence :</td>
                            <td>
                                @if($charge->recurrence === 'aucune')
                                    Ponctuelle
                                @else
                                    {{ ucfirst($charge->recurrence) }}
                                    @if($charge->date_fin_recurrence)
                                        (jusqu'au {{ $charge->date_fin_recurrence->format('d/m/Y') }})
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Statut :</td>
                            <td>
                                <span class="badge bg-{{ $charge->badgeColor() }} fs-6">
                                    {{ $charge->statutLabel() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Créée le :</td>
                            <td>{{ $charge->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Actions sur le statut --}}
            <div class="mt-3 pt-3 border-top">
                @if(!$charge->payee)
                <form method="POST" action="{{ route('charges.payer', $charge) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Marquer comme payée
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('charges.impayer', $charge) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-return-left me-1"></i>Marquer comme impayée
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Zone de suppression --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-danger mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Zone dangereuse</strong> - La suppression est définitive
                    </div>
                    <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                          onsubmit="return confirm('Supprimer définitivement cette charge ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
@endsection