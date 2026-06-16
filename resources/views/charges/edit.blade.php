@extends('layouts.app')
@section('title', 'Modifier — ' . $charge->libelle)
@section('page-title', 'Modifier la charge')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between">
        <span><i class="bi bi-pencil me-2"></i>{{ $charge->libelle }}</span>
        <a href="{{ route('charges.index') }}" class="btn btn-sm btn-link">Retour</a>
    </div>
    <div class="card-body p-4">
    <form method="POST" action="{{ route('charges.update', $charge) }}">
    @csrf @method('PUT')

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Libellé <span class="text-danger">*</span></label>
            <input type="text" name="libelle" class="form-control @error('libelle') is-invalid @enderror"
                value="{{ old('libelle', $charge->libelle) }}" required>
            @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Montant <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" name="montant" class="form-control"
                    value="{{ old('montant', $charge->montant) }}" step="0.01" min="0.01" required>
                <span class="input-group-text">MAD</span>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Date prévue <span class="text-danger">*</span></label>
            <input type="date" name="date_prevue" class="form-control"
                value="{{ old('date_prevue', $charge->date_prevue->format('Y-m-d')) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Catégorie</label>
            <select name="categorie" class="form-select">
                @foreach(['loyer'=>'Loyer','salaires'=>'Salaires','impots'=>'Impôts','fournisseurs'=>'Fournisseurs','services'=>'Services','autre'=>'Autre'] as $val=>$lab)
                    <option value="{{ $val }}" {{ old('categorie', $charge->categorie)===$val?'selected':'' }}>{{ $lab }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="variable" {{ old('type',$charge->type)==='variable'?'selected':'' }}>Variable</option>
                <option value="fixe"     {{ old('type',$charge->type)==='fixe'?'selected':'' }}>Fixe</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Récurrence</label>
            <select name="recurrence" class="form-select">
                @foreach(['aucune'=>'Aucune','mensuelle'=>'Mensuelle','trimestrielle'=>'Trimestrielle','annuelle'=>'Annuelle'] as $val=>$lab)
                    <option value="{{ $val }}" {{ old('recurrence',$charge->recurrence)===$val?'selected':'' }}>{{ $lab }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Fin de récurrence</label>
            <input type="date" name="date_fin_recurrence" class="form-control"
                value="{{ old('date_fin_recurrence', $charge->date_fin_recurrence?->format('Y-m-d')) }}">
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="{{ route('charges.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-check-lg me-1"></i>Enregistrer
            </button>
        </div>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection