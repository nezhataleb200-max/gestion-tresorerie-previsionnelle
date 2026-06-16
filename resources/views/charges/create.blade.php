@extends('layouts.app')
@section('title', 'Nouvelle charge')
@section('page-title', 'Nouvelle charge')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <i class="bi bi-cash-stack me-2"></i>Informations de la charge
    </div>
    <div class="card-body p-4">
    <form method="POST" action="{{ route('charges.store') }}" id="formCharge">
    @csrf

    <div class="row g-3">

        {{-- Libellé --}}
        <div class="col-12">
            <label class="form-label">Libellé <span class="text-danger">*</span></label>
            <input type="text" name="libelle"
                class="form-control @error('libelle') is-invalid @enderror"
                value="{{ old('libelle') }}"
                placeholder="Ex: Loyer bureau, Salaires, Facture internet..."
                required>
            @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Montant --}}
        <div class="col-md-6">
            <label class="form-label">Montant <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" name="montant" id="montant"
                    class="form-control @error('montant') is-invalid @enderror"
                    value="{{ old('montant') }}"
                    step="0.01" min="0.01" required>
                <span class="input-group-text">MAD</span>
            </div>
            @error('montant')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- Date prévue --}}
        <div class="col-md-6">
            <label class="form-label">Date prévue <span class="text-danger">*</span></label>
            <input type="date" name="date_prevue" id="date_prevue"
                class="form-control @error('date_prevue') is-invalid @enderror"
                value="{{ old('date_prevue', now()->toDateString()) }}"
                required>
            @error('date_prevue')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Catégorie --}}
        <div class="col-md-6">
            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
            <select name="categorie" class="form-select @error('categorie') is-invalid @enderror">
                <option value="">-- Choisir --</option>
                @foreach(['loyer'=>'Loyer','salaires'=>'Salaires','impots'=>'Impôts / Taxes','fournisseurs'=>'Fournisseurs','services'=>'Services','autre'=>'Autre'] as $val=>$lab)
                    <option value="{{ $val }}" {{ old('categorie')===$val?'selected':'' }}>{{ $lab }}</option>
                @endforeach
            </select>
            @error('categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Type --}}
        <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" id="type" class="form-select" onchange="toggleRecurrence()">
                <option value="variable" {{ old('type','variable')==='variable'?'selected':'' }}>Variable (ponctuelle)</option>
                <option value="fixe" {{ old('type')==='fixe'?'selected':'' }}>Fixe (récurrente)</option>
            </select>
        </div>

        {{-- Récurrence (visible seulement si type = fixe) --}}
        <div class="col-md-6" id="recurrenceBlock" style="display:none">
            <label class="form-label">Récurrence</label>
            <select name="recurrence" class="form-select">
                <option value="aucune">Aucune</option>
                <option value="mensuelle" {{ old('recurrence')==='mensuelle'?'selected':'' }}>Mensuelle</option>
                <option value="trimestrielle" {{ old('recurrence')==='trimestrielle'?'selected':'' }}>Trimestrielle</option>
                <option value="annuelle" {{ old('recurrence')==='annuelle'?'selected':'' }}>Annuelle</option>
            </select>
        </div>

        {{-- Date fin récurrence (visible seulement si type = fixe) --}}
        <div class="col-md-6" id="dateFinBlock" style="display:none">
            <label class="form-label">Fin de récurrence</label>
            <input type="date" name="date_fin_recurrence"
                class="form-control @error('date_fin_recurrence') is-invalid @enderror"
                value="{{ old('date_fin_recurrence') }}">
            <div class="form-text">Laisser vide = 1 an par défaut</div>
            @error('date_fin_recurrence')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Message informatif récurrence --}}
        <div class="col-12" id="recurrenceInfo" style="display:none">
            <div class="alert alert-info py-2 mb-0" id="recurrenceText"></div>
        </div>

        {{-- Boutons --}}
        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="{{ route('charges.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-check-lg me-1"></i>Enregistrer la charge
            </button>
        </div>

    </div>
    </form>
    </div>
</div>
</div>
</div>

@push('scripts')
<script>
// Affiche/masque les champs de récurrence selon le type choisi
function toggleRecurrence() {
    const type = document.getElementById('type').value;
    const show = type === 'fixe';
    document.getElementById('recurrenceBlock').style.display = show ? 'block' : 'none';
    document.getElementById('dateFinBlock').style.display    = show ? 'block' : 'none';
    document.getElementById('recurrenceInfo').style.display  = show ? 'block' : 'none';
    if (show) updateInfo();
}

// Met à jour le message d'information en bas du formulaire
function updateInfo() {
    const montant  = parseFloat(document.getElementById('montant').value) || 0;
    const rec      = document.querySelector('[name="recurrence"]').value;
    const dateFin  = document.querySelector('[name="date_fin_recurrence"]').value;
    const dateStart = document.getElementById('date_prevue').value;

    if (!montant || rec === 'aucune' || !dateStart) return;

    let nbOcc = 0;
    if (dateFin && dateStart) {
        const s = new Date(dateStart), f = new Date(dateFin);
        const diffMois = (f.getFullYear() - s.getFullYear()) * 12 + (f.getMonth() - s.getMonth());
        if      (rec === 'mensuelle')     nbOcc = diffMois;
        else if (rec === 'trimestrielle') nbOcc = Math.floor(diffMois / 3);
        else if (rec === 'annuelle')      nbOcc = Math.floor(diffMois / 12);
    }

    const infos = {
        'mensuelle':     'mensuelle',
        'trimestrielle': 'tous les 3 mois',
        'annuelle':      'annuelle',
    };

    document.getElementById('recurrenceText').innerHTML =
        `Cette charge sera répétée <strong>${infos[rec] || ''}</strong>.` +
        (nbOcc > 0 ? ` <strong>${nbOcc} occurrence(s)</strong> seront créées automatiquement.` : '');
}

// Écoute les changements pour mettre à jour le message
document.getElementById('montant')?.addEventListener('input', updateInfo);
document.querySelector('[name="recurrence"]')?.addEventListener('change', updateInfo);
document.querySelector('[name="date_fin_recurrence"]')?.addEventListener('change', updateInfo);

// Initialise à l'affichage si la page revient avec old()
document.addEventListener('DOMContentLoaded', toggleRecurrence);
</script>
@endpush
@endsection