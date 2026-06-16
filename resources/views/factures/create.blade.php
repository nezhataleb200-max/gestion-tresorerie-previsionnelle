@extends('layouts.app')
@section('title', 'Nouvelle facture')
@section('page-title', 'Nouvelle facture')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <i class="bi bi-receipt me-2"></i>Informations de la facture
    </div>
    <div class="card-body p-4">

    <form method="POST" action="{{ route('factures.store') }}">
    @csrf
    <div class="row g-3">

        {{-- Client --}}
        <div class="col-12">
            <label class="form-label">Client <span class="text-danger">*</span></label>
            <select name="client_id" id="client_id"
                    class="form-select @error('client_id') is-invalid @enderror" required>
                <option value="">-- Sélectionner un client --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}"
                            data-delai="{{ $c->delai_paiement }}"
                            {{ (old('client_id', $clientId) == $c->id) ? 'selected' : '' }}>
                        {{ $c->nom }} — délai {{ $c->delai_paiement }} jours
                    </option>
                @endforeach
            </select>
            @error('client_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Montant HT --}}
        <div class="col-md-4">
            <label class="form-label">Montant HT <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" name="montant_ht" id="montant_ht"
                       class="form-control @error('montant_ht') is-invalid @enderror"
                       value="{{ old('montant_ht') }}"
                       step="0.01" min="0.01" required>
                <span class="input-group-text">MAD</span>
            </div>
            @error('montant_ht')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        {{-- TVA --}}
        <div class="col-md-3">
            <label class="form-label">TVA <span class="text-danger">*</span></label>
            <select name="tva" id="tva" class="form-select">
                <option value="0"  {{ old('tva')==='0'  ? 'selected' : '' }}>0%</option>
                <option value="10" {{ old('tva')==='10' ? 'selected' : '' }}>10%</option>
                <option value="20" {{ old('tva','20')==='20' ? 'selected' : '' }}>20%</option>
            </select>
        </div>

        {{-- TTC calculé automatiquement --}}
        <div class="col-md-5">
            <label class="form-label">Montant TTC <span class="text-success">(calculé auto)</span></label>
            <div class="input-group">
                <input type="text" id="ttc_display"
                       class="form-control bg-light fw-bold text-success"
                       readonly placeholder="0.00">
                <span class="input-group-text">MAD</span>
            </div>
        </div>

        {{-- Date émission --}}
        <div class="col-md-6">
            <label class="form-label">Date d'émission <span class="text-danger">*</span></label>
            <input type="date" name="date_emission" id="date_emission"
                   class="form-control"
                   value="{{ old('date_emission', now()->toDateString()) }}" required>
        </div>

        {{-- Date échéance --}}
        <div class="col-md-6">
            <label class="form-label">Date d'échéance <span class="text-danger">*</span></label>
            <input type="date" name="date_echeance" id="date_echeance"
                   class="form-control @error('date_echeance') is-invalid @enderror"
                   value="{{ old('date_echeance') }}" required>
            <div class="form-text">Pré-remplie selon le délai du client</div>
            @error('date_echeance')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Description --}}
        <div class="col-12">
            <label class="form-label">Description de la prestation</label>
            <textarea name="description" class="form-control" rows="3"
                placeholder="Ex : Accompagnement juridique — dossier mars 2026">
                {{ old('description') }}
            </textarea>
        </div>

        {{-- Boutons --}}
        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="{{ route('factures.index') }}" class="btn btn-outline-secondary">
                Annuler
            </a>
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-check-lg me-1"></i>Créer la facture
            </button>
        </div>

    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
// ─── Calcul TTC en temps réel ───────────────────────────────────
function calculerTTC() {
    const ht  = parseFloat(document.getElementById('montant_ht').value) || 0;
    const tva = parseFloat(document.getElementById('tva').value) || 0;
    const ttc = (ht * (1 + tva / 100)).toFixed(2);
    document.getElementById('ttc_display').value = ht > 0 ? ttc : '';
}

// ─── Calcul date échéance automatique ───────────────────────────
function calculerEcheance() {
    const sel    = document.getElementById('client_id');
    const option = sel.options[sel.selectedIndex];

    // Récupère le délai depuis l'attribut data-delai de l'option
    const delai  = parseInt(option.dataset.delai) || 0;
    const em     = document.getElementById('date_emission').value;

    if (em && delai > 0) {
        const date = new Date(em);
        date.setDate(date.getDate() + delai);

        // Formate en YYYY-MM-DD pour l'input type="date"
        const annee = date.getFullYear();
        const mois  = String(date.getMonth() + 1).padStart(2, '0');
        const jour  = String(date.getDate()).padStart(2, '0');

        document.getElementById('date_echeance').value = `${annee}-${mois}-${jour}`;
    }
}

// ─── Événements ─────────────────────────────────────────────────
document.getElementById('montant_ht').addEventListener('input', calculerTTC);
document.getElementById('tva').addEventListener('change', calculerTTC);
document.getElementById('client_id').addEventListener('change', calculerEcheance);
document.getElementById('date_emission').addEventListener('change', calculerEcheance);
</script>
@endpush