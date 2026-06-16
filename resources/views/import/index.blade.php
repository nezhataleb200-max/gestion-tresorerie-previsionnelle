@extends('layouts.app')
@section('title', 'Import Excel')
@section('page-title', 'Import de factures depuis Excel')

@section('content')

{{-- Message de succès --}}
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

{{-- Erreurs de lignes --}}
@if(session('erreurs_import') && count(session('erreurs_import')) > 0)
<div class="alert alert-warning">
    <strong>Lignes ignorées :</strong>
    <ul class="mb-0 mt-1">
        @foreach(session('erreurs_import') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-4">

    {{-- CARTE 1 : Factures de ventes --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <span class="badge bg-success">Entrées</span>
                <span class="fw-500">Factures de ventes</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Importe les factures que tu envoies à tes clients.
                    Ces données alimentent les <strong>entrées d'argent</strong>
                    dans le plan de trésorerie.
                </p>

                {{-- Colonnes attendues --}}
                <div class="bg-light rounded p-3 mb-3">
                    <div class="small fw-500 mb-2">Colonnes requises dans ton Excel :</div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td class="text-muted pe-3">client_nom</td><td>Nom du client (ex: SARL Idrissi)</td></tr>
                            <tr><td class="text-muted pe-3">client_email</td><td>Email du client</td></tr>
                            <tr><td class="text-muted pe-3">montant_ht</td><td>Montant hors taxes (ex: 10000)</td></tr>
                            <tr><td class="text-muted pe-3">tva</td><td>Taux TVA : 0, 10 ou 20</td></tr>
                            <tr><td class="text-muted pe-3">date_emission</td><td>Format : JJ/MM/AAAA</td></tr>
                            <tr><td class="text-muted pe-3">date_echeance</td><td>Format : JJ/MM/AAAA</td></tr>
                            <tr><td class="text-muted pe-3">description</td><td>Optionnel</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Télécharger le modèle --}}
                <a href="{{ route('import.modele.ventes') }}"
                   class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-download me-1"></i>
                    Télécharger le fichier modèle
                </a>

                {{-- Formulaire d'upload --}}
                <form method="POST"
                      action="{{ route('import.ventes') }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-500">
                            Sélectionner ton fichier Excel
                        </label>
                        <input type="file"
                               name="fichier"
                               class="form-control @error('fichier') is-invalid @enderror"
                               accept=".xlsx,.xls,.csv">
                        @error('fichier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Formats acceptés : .xlsx, .xls, .csv — Max 5 Mo
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-upload me-1"></i>
                        Importer les factures de ventes
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- CARTE 2 : Factures d'achats --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <span class="badge bg-danger">Sorties</span>
                <span class="fw-500">Factures d'achats</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Importe les factures que tu reçois de tes fournisseurs.
                    Ces données alimentent les <strong>sorties d'argent</strong>
                    dans le plan de trésorerie.
                </p>

                <div class="bg-light rounded p-3 mb-3">
                    <div class="small fw-500 mb-2">Colonnes requises dans ton Excel :</div>
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td class="text-muted pe-3">libelle</td><td>Nom de la charge (ex: Loyer)</td></tr>
                            <tr><td class="text-muted pe-3">montant</td><td>Montant total (ex: 8000)</td></tr>
                            <tr><td class="text-muted pe-3">date_prevue</td><td>Format : JJ/MM/AAAA</td></tr>
                            <tr><td class="text-muted pe-3">categorie</td><td>loyer / salaires / impots / fournisseurs / services / autre</td></tr>
                            <tr><td class="text-muted pe-3">type</td><td>fixe ou variable</td></tr>
                            <tr><td class="text-muted pe-3">recurrence</td><td>aucune / mensuelle / trimestrielle / annuelle</td></tr>
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('import.modele.achats') }}"
                   class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-download me-1"></i>
                    Télécharger le fichier modèle
                </a>

                <form method="POST"
                      action="{{ route('import.achats') }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-500">
                            Sélectionner ton fichier Excel
                        </label>
                        <input type="file"
                               name="fichier"
                               class="form-control @error('fichier') is-invalid @enderror"
                               accept=".xlsx,.xls,.csv">
                        @error('fichier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Formats acceptés : .xlsx, .xls, .csv — Max 5 Mo
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-upload me-1"></i>
                        Importer les factures d'achats
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- Note importante --}}
<div class="alert alert-info mt-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Après chaque import</strong>, le plan de trésorerie est recalculé
    automatiquement. Va sur
    <a href="{{ route('plan.index') }}" class="alert-link">Plan trésorerie</a>
    pour voir l'impact immédiat sur tes prévisions.
</div>

@endsection