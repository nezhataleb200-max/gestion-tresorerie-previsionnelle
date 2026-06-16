@extends('layouts.app')

@section('title', 'Modifier la facture ' . $facture->numero)
@section('page-title', 'Modifier la facture ' . $facture->numero)

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-pencil-square me-2"></i>
            Facture n° {{ $facture->numero }}
        </span>
        <a href="{{ route('factures.show', $facture) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye"></i> Voir la facture
        </a>
    </div>
    <div class="card-body p-4">

    <form method="POST" action="{{ route('factures.update', $facture) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">

            {{-- Client (lecture seule car une facture ne change pas de client) --}}
            <div class="col-12">
                <label class="form-label">Client</label>
                <input type="text" class="form-control bg-light" 
                       value="{{ $facture->client->nom }}" readonly disabled>
                <input type="hidden" name="client_id" value="{{ $facture->client_id }}">
            </div>

            {{-- Numéro de facture (lecture seule) --}}
            <div class="col-md-4">
                <label class="form-label">Numéro de facture</label>
                <input type="text" class="form-control bg-light" 
                       value="{{ $facture->numero }}" readonly disabled>
            </div>

            {{-- Montant HT --}}
            <div class="col-md-4">
                <label class="form-label">Montant HT <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="montant_ht" id="montant_ht"
                           class="form-control @error('montant_ht') is-invalid @enderror"
                           value="{{ old('montant_ht', $facture->montant_ht) }}"
                           step="0.01" min="0.01" required>
                    <span class="input-group-text">MAD</span>
                </div>
                @error('montant_ht')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- TVA --}}
            <div class="col-md-4">
                <label class="form-label">TVA <span class="text-danger">*</span></label>
                <select name="tva" id="tva" class="form-select">
                    <option value="0"  {{ old('tva', $facture->tva) == 0 ? 'selected' : '' }}>0%</option>
                    <option value="10" {{ old('tva', $facture->tva) == 10 ? 'selected' : '' }}>10%</option>
                    <option value="20" {{ old('tva', $facture->tva) == 20 ? 'selected' : '' }}>20%</option>
                </select>
            </div>

            {{-- TTC (calculé automatiquement) --}}
            <div class="col-md-4">
                <label class="form-label">Montant TTC <span class="text-success">(calculé auto)</span></label>
                <div class="input-group">
                    <input type="text" id="ttc_display"
                           class="form-control bg-light fw-bold text-success"
                           value="{{ number_format($facture->montant_ttc, 2) }}"
                           readonly>
                    <span class="input-group-text">MAD</span>
                </div>
            </div>

            {{-- Date d'émission --}}
            <div class="col-md-6">
                <label class="form-label">Date d'émission <span class="text-danger">*</span></label>
                <input type="date" name="date_emission" id="date_emission"
                       class="form-control @error('date_emission') is-invalid @enderror"
                       value="{{ old('date_emission', $facture->date_emission->format('Y-m-d')) }}" required>
                @error('date_emission')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Date d'échéance --}}
            <div class="col-md-6">
                <label class="form-label">Date d'échéance <span class="text-danger">*</span></label>
                <input type="date" name="date_echeance" id="date_echeance"
                       class="form-control @error('date_echeance') is-invalid @enderror"
                       value="{{ old('date_echeance', $facture->date_echeance->format('Y-m-d')) }}" required>
                <div class="form-text">Délai de paiement du client : {{ $facture->client->delai_paiement }} jours</div>
                @error('date_echeance')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- Statut --}}
            <div class="col-md-6">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-select">
                    <option value="en_attente" {{ old('statut', $facture->statut) == 'en_attente' ? 'selected' : '' }}>
                        En attente
                    </option>
                    <option value="payee" {{ old('statut', $facture->statut) == 'payee' ? 'selected' : '' }}>
                        Payée
                    </option>
                    <option value="en_retard" {{ old('statut', $facture->statut) == 'en_retard' ? 'selected' : '' }}>
                        En retard
                    </option>
                </select>
                @if($facture->statut == 'payee' && $facture->date_paiement)
                    <div class="form-text text-success">
                        Payée le {{ $facture->date_paiement->format('d/m/Y') }}
                    </div>
                @endif
            </div>

            {{-- Date de paiement (si payée) --}}
            @if($facture->statut == 'payee')
            <div class="col-md-6">
                <label class="form-label">Date de paiement</label>
                <input type="date" name="date_paiement" class="form-control"
                       value="{{ old('date_paiement', $facture->date_paiement ? $facture->date_paiement->format('Y-m-d') : '') }}">
            </div>
            @endif

            {{-- Description --}}
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"
                    placeholder="Détails de la prestation...">{{ old('description', $facture->description) }}</textarea>
            </div>

            {{-- Boutons --}}
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('factures.show', $facture) }}" class="btn btn-outline-secondary">
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Enregistrer les modifications
                </button>
            </div>

        </div>
    </form>

    </div>
</div>

{{-- Zone de suppression (optionnelle) --}}
<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <div class="alert alert-danger mb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Zone dangereuse</strong> - La suppression est définitive
                </div>
                <form method="POST" action="{{ route('factures.destroy', $facture) }}"
                      onsubmit="return confirm('Supprimer définitivement la facture {{ $facture->numero }} ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Supprimer la facture
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
</div>
@endsection

@push('scripts')
<script>
// Calcul TTC en temps réel
function calculerTTC() {
    const ht = parseFloat(document.getElementById('montant_ht').value) || 0;
    const tva = parseFloat(document.getElementById('tva').value) || 0;
    const ttc = (ht * (1 + tva / 100)).toFixed(2);
    document.getElementById('ttc_display').value = ttc;
}

// Événements
document.getElementById('montant_ht').addEventListener('input', calculerTTC);
document.getElementById('tva').addEventListener('change', calculerTTC);

// Calcul initial (si les valeurs sont déjà chargées)
document.addEventListener('DOMContentLoaded', function() {
    calculerTTC();
});
</script>
@endpush