@extends('layouts.app')
@section('title', 'Modifier — ' . $client->nom)
@section('page-title', 'Modifier le client')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between">
        <span><i class="bi bi-pencil me-2"></i>Modifier : {{ $client->nom }}</span>
        <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-link">
            Voir la fiche
        </a>
    </div>
    <div class="card-body p-4">

        {{-- Le formulaire utilise PUT pour la mise à jour --}}
        <form method="POST" action="{{ route('clients.update', $client) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $client->nom) }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="societe"
                            {{ old('type', $client->type) === 'societe' ? 'selected' : '' }}>
                            Société
                        </option>
                        <option value="particulier"
                            {{ old('type', $client->type) === 'particulier' ? 'selected' : '' }}>
                            Particulier
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $client->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control"
                           value="{{ old('telephone', $client->telephone) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Délai de paiement <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="delai_paiement" class="form-control"
                               value="{{ old('delai_paiement', $client->delai_paiement) }}"
                               min="1" required>
                        <span class="input-group-text">jours</span>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">
                        {{ old('notes', $client->notes) }}
                    </textarea>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="{{ route('clients.show', $client) }}"
                       class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection