@extends('layouts.app')
@section('title', 'Nouveau client')
@section('page-title', 'Nouveau client')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <i class="bi bi-person-plus me-2"></i>Informations du client
    </div>
    <div class="card-body p-4">

        <form method="POST" action="{{ route('clients.store') }}">
            @csrf

            <div class="row g-3">

                {{-- Nom --}}
                <div class="col-md-8">
                    <label class="form-label">
                        Raison sociale / Nom <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom') }}"
                           placeholder="Ex : SARL Idrissi Conseil"
                           required>
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Type --}}
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select">
                        <option value="societe"
                            {{ old('type','societe')==='societe' ? 'selected' : '' }}>
                            Société
                        </option>
                        <option value="particulier"
                            {{ old('type')==='particulier' ? 'selected' : '' }}>
                            Particulier
                        </option>
                    </select>
                </div>

                {{-- Email --}}
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="contact@entreprise.ma">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control"
                           value="{{ old('telephone') }}"
                           placeholder="0600 000 000">
                </div>

                {{-- Délai de paiement --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Délai de paiement <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" name="delai_paiement"
                               class="form-control @error('delai_paiement') is-invalid @enderror"
                               value="{{ old('delai_paiement', 30) }}"
                               min="1" max="365" required>
                        <span class="input-group-text">jours</span>
                    </div>
                    <div class="form-text">
                        Ce délai calcule automatiquement la date d'échéance des factures
                    </div>
                    @error('delai_paiement')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Notes --}}
                <div class="col-12">
                    <label class="form-label">Notes internes</label>
                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Remarques sur ce client...">{{ old('notes') }}</textarea>
                </div>

                {{-- Boutons --}}
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="{{ route('clients.index') }}"
                       class="btn btn-outline-secondary">Annuler</a>
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