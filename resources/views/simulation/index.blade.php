@extends('layouts.app')
@section('title', 'Simulation de scénarios')
@section('page-title', 'Simulation de scénarios — What-If')

@section('content')

{{-- ── INTRODUCTION ──────────────────────────────────────── --}}
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Principe :</strong> La simulation calcule un plan alternatif
    <strong>sans modifier vos données réelles</strong>.
    Vous pouvez tester des hypothèses et voir l'impact immédiat sur votre trésorerie.
</div>

{{-- ── FORMULAIRE DE SIMULATION ────────────────────────────── --}}
<div class="row g-4">
<div class="col-lg-4">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-500">
        <i class="bi bi-sliders me-2"></i>Paramètres du scénario
    </div>
    <div class="card-body">
    <form method="POST" action="{{ route('simulation.simuler') }}" id="formSim">
    @csrf

    {{-- Année et solde initial --}}
    <div class="mb-3">
        <label class="form-label fw-500">Année simulée</label>
        <select name="annee" class="form-select">
            @foreach([2025, 2026, 2027] as $a)
                <option value="{{ $a }}"
                    {{ ($annee ?? now()->year) == $a ? 'selected' : '' }}>
                    {{ $a }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label fw-500">Solde initial (MAD)</label>
        <input type="number" name="solde_initial" class="form-control"
               value="{{ old('solde_initial', $solde_initial ?? 15000) }}"
               step="100" required>
        <div class="form-text">Trésorerie disponible au 1er janvier</div>
    </div>

    <hr>

    {{-- Type de scénario --}}
    <div class="mb-3">
        <label class="form-label fw-500">
            Type de scénario <span class="text-danger">*</span>
        </label>
        <select name="type_scenario" id="typeScenario"
                class="form-select @error('type_scenario') is-invalid @enderror"
                onchange="afficherScenario()">
            <option value="">-- Choisir un scénario --</option>
            <option value="retard_client"
                {{ old('type_scenario', $type ?? '') === 'retard_client' ? 'selected' : '' }}>
                Retard d'un client
            </option>
            <option value="baisse_activite"
                {{ old('type_scenario', $type ?? '') === 'baisse_activite' ? 'selected' : '' }}>
                Baisse d'activité
            </option>
            <option value="charge_exceptionnelle"
                {{ old('type_scenario', $type ?? '') === 'charge_exceptionnelle' ? 'selected' : '' }}>
                Charge exceptionnelle imprévue
            </option>
        </select>
        @error('type_scenario')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- ══ SCÉNARIO 1 : Retard client ══ --}}
    <div id="bloc_retard_client" style="display:none">
        <div class="p-3 rounded mb-3" style="background:#f8f0ff;border:1px solid #d4b8f0">
            <div class="small fw-500 mb-2" style="color:#4A2080">
                <i class="bi bi-person-x me-1"></i>
                Scénario : Un client tarde à payer
            </div>
            <p class="small text-muted mb-3">
                Les factures de ce client seront décalées de N mois dans le plan.
                L'argent arrivera plus tard — impact sur la trésorerie intermédiaire.
            </p>

            <div class="mb-2">
                <label class="form-label small">Client concerné *</label>
                <select name="client_id"
                        class="form-select form-select-sm @error('client_id') is-invalid @enderror">
                    <option value="">-- Choisir --</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}"
                            {{ old('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nom }}
                        </option>
                    @endforeach
                </select>
                @error('client_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="form-label small">Retard (en mois) *</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="retard_mois"
                           class="form-control @error('retard_mois') is-invalid @enderror"
                           value="{{ old('retard_mois', 1) }}"
                           min="1" max="12" placeholder="Ex: 2">
                    <span class="input-group-text">mois</span>
                </div>
                @error('retard_mois')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Ex: 2 = les factures d'avril seront reçues en juin
                </div>
            </div>
        </div>
    </div>

    {{-- ══ SCÉNARIO 2 : Baisse d'activité ══ --}}
    <div id="bloc_baisse_activite" style="display:none">
        <div class="p-3 rounded mb-3" style="background:#fff5f5;border:1px solid #f5b8b8">
            <div class="small fw-500 mb-2 text-danger">
                <i class="bi bi-graph-down me-1"></i>
                Scénario : Baisse d'activité
            </div>
            <p class="small text-muted mb-3">
                Toutes les rentrées d'argent sont réduites d'un pourcentage.
                Simule une période creuse, une perte de contrats, ou une crise.
            </p>

            <div class="mb-2">
                <label class="form-label small">Taux de réduction des entrées *</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="taux_reduction"
                           class="form-control @error('taux_reduction') is-invalid @enderror"
                           value="{{ old('taux_reduction', 30) }}"
                           min="1" max="100" placeholder="Ex: 30" id="tauxSlider">
                    <span class="input-group-text">%</span>
                </div>
                @error('taux_reduction')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <input type="range" class="form-range mt-2"
                       min="1" max="100" value="{{ old('taux_reduction', 30) }}"
                       oninput="document.getElementById('tauxSlider').value=this.value">
                <div class="form-text">
                    Ex: 30% → une facture de 10 000 MAD devient 7 000 MAD
                </div>
            </div>
        </div>
    </div>

    {{-- ══ SCÉNARIO 3 : Charge exceptionnelle ══ --}}
    <div id="bloc_charge_exceptionnelle" style="display:none">
        <div class="p-3 rounded mb-3" style="background:#fffbf0;border:1px solid #f0d080">
            <div class="small fw-500 mb-2" style="color:#7B4F00">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Scénario : Dépense imprévue
            </div>
            <p class="small text-muted mb-3">
                Une dépense exceptionnelle survient un mois précis.
                Simule une panne, des travaux, un procès, une amende fiscale.
            </p>

            <div class="mb-2">
                <label class="form-label small">Mois impacté *</label>
                <select name="mois_impact"
                        class="form-select form-select-sm @error('mois_impact') is-invalid @enderror">
                    <option value="">-- Choisir le mois --</option>
                    @foreach(['Janvier','Février','Mars','Avril','Mai','Juin',
                              'Juillet','Août','Septembre','Octobre','Novembre','Décembre']
                             as $idx => $nom)
                        <option value="{{ $idx + 1 }}"
                            {{ old('mois_impact') == ($idx+1) ? 'selected' : '' }}>
                            {{ $nom }}
                        </option>
                    @endforeach
                </select>
                @error('mois_impact')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="form-label small">Montant de la dépense *</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="montant_extra"
                           class="form-control @error('montant_extra') is-invalid @enderror"
                           value="{{ old('montant_extra') }}"
                           step="100" min="0" placeholder="Ex: 15000">
                    <span class="input-group-text">MAD</span>
                </div>
                @error('montant_extra')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="form-label small">Libellé (optionnel)</label>
                <input type="text" name="libelle_extra"
                       class="form-control form-control-sm"
                       value="{{ old('libelle_extra') }}"
                       placeholder="Ex: Réparation machine, Procès...">
            </div>
        </div>
    </div>

    {{-- Bouton simulation --}}
    <button type="submit" class="btn btn-dark w-100">
        <i class="bi bi-play-circle me-1"></i>
        Lancer la simulation
    </button>

    @if(isset($titreScenario))
    <a href="{{ route('simulation.index') }}" class="btn btn-outline-secondary w-100 mt-2">
        <i class="bi bi-arrow-counterclockwise me-1"></i>
        Réinitialiser
    </a>
    @endif

    </form>
    </div>
</div>
</div>

{{-- ── RÉSULTATS DE LA SIMULATION ───────────────────────────── --}}
<div class="col-lg-8">

    @if(!isset($planSimule))
    {{--
        Pas encore de simulation lancée.
        On affiche le plan réel en attendant.
    --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-500">
            Plan de trésorerie réel — {{ $annee }}
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mois</th>
                    <th class="text-end">Entrées</th>
                    <th class="text-end">Sorties</th>
                    <th class="text-end">Solde mois</th>
                    <th class="text-end">Solde cumulé</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
            @forelse($planReel as $ligne)
                @php
                    $rowClass = $ligne->solde_cumule < 0
                        ? 'table-danger'
                        : ($ligne->solde_mois < 0 ? 'table-warning' : '');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="fw-500">{{ $ligne->labelMois() }}</td>
                    <td class="text-end text-success">
                        {{ number_format($ligne->total_entrees, 2, ',', ' ') }} MAD
                    </td>
                    <td class="text-end text-danger">
                        {{ number_format($ligne->total_sorties, 2, ',', ' ') }} MAD
                    </td>
                    <td class="text-end {{ $ligne->solde_mois >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $ligne->solde_mois >= 0 ? '+' : '' }}{{ number_format($ligne->solde_mois, 2, ',', ' ') }} MAD
                    </td>
                    <td class="text-end {{ $ligne->solde_cumule >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                        {{ number_format($ligne->solde_cumule, 2, ',', ' ') }} MAD
                    </td>
                    <td class="text-center">
                        @if($ligne->solde_cumule < 0)
                            <span class="badge bg-danger">Déficit</span>
                        @elseif($ligne->solde_mois < 0)
                            <span class="badge bg-warning text-dark">Tension</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Aucune donnée. Ajoutez des factures et des charges.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @else
    {{--
        Simulation lancée.
        On affiche le résumé d'impact + la comparaison mois par mois.
    --}}

    {{-- Titre du scénario simulé --}}
    <div class="alert alert-primary d-flex align-items-center mb-3">
        <i class="bi bi-lightning-charge-fill me-2 fs-5"></i>
        <div>
            <strong>Scénario simulé :</strong> {{ $titreScenario }}
            <div class="small mt-1 text-muted">
                Les données réelles ne sont pas modifiées — simulation uniquement.
            </div>
        </div>
    </div>

    {{-- Résumé d'impact --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 text-center p-3"
                 style="background:{{ $impacts['diff_cumule_final'] >= 0 ? '#e2f0ea' : '#fdecea' }}">
                <div class="small text-muted mb-1">Impact solde final</div>
                <div class="h5 mb-0 {{ $impacts['diff_cumule_final'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $impacts['diff_cumule_final'] >= 0 ? '+' : '' }}{{ number_format($impacts['diff_cumule_final'], 0, ',', ' ') }} MAD
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-center p-3 bg-light">
                <div class="small text-muted mb-1">Mois dégradés</div>
                <div class="h5 mb-0 {{ $impacts['mois_degrades'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $impacts['mois_degrades'] }}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-center p-3 bg-light">
                <div class="small text-muted mb-1">Mois améliorés</div>
                <div class="h5 mb-0 text-success">{{ $impacts['mois_sauves'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-center p-3 bg-light">
                <div class="small text-muted mb-1">Diff. entrées total</div>
                <div class="h5 mb-0 {{ $impacts['diff_entrees_total'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $impacts['diff_entrees_total'] >= 0 ? '+' : '' }}{{ number_format($impacts['diff_entrees_total'], 0, ',', ' ') }} MAD
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau comparatif --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-500">Comparaison — Plan réel vs Plan simulé</span>
                <div class="d-flex gap-3 small">
                    <span><span style="display:inline-block;width:12px;height:12px;background:#e2f0ea;border:1px solid #1D7A4F;margin-right:4px"></span>Amélioré</span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#fdecea;border:1px solid #C00000;margin-right:4px"></span>Dégradé</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:12px">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="align-middle">Mois</th>
                    <th colspan="2" class="text-center border-start">Entrées (MAD)</th>
                    <th colspan="2" class="text-center border-start">Sorties (MAD)</th>
                    <th colspan="2" class="text-center border-start">Solde cumulé (MAD)</th>
                    <th class="text-center border-start">Statut simulé</th>
                </tr>
                <tr style="font-size:11px">
                    <th class="text-end border-start text-muted">Réel</th>
                    <th class="text-end text-primary">Simulé</th>
                    <th class="text-end border-start text-muted">Réel</th>
                    <th class="text-end text-primary">Simulé</th>
                    <th class="text-end border-start text-muted">Réel</th>
                    <th class="text-end text-primary">Simulé</th>
                    <th class="text-center border-start"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($planSimule as $i => $sim)
                @php
                    $reel      = $planReel[$i] ?? null;
                    $detail    = $impacts['details'][$i] ?? [];
                    $diffCumule= $detail['diff_cumule'] ?? 0;

                    // Couleur de la ligne selon l'impact
                    if ($sim['en_deficit'])      $rowStyle = 'background:#fdecea';
                    elseif ($sim['en_tension'])  $rowStyle = 'background:#fff8e1';
                    elseif ($diffCumule > 0)     $rowStyle = 'background:#f0fff4';
                    else                         $rowStyle = '';
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td class="fw-500">{{ $sim['label'] }}</td>

                    {{-- Entrées --}}
                    <td class="text-end text-muted border-start">
                        {{ number_format($reel?->total_entrees ?? 0, 0, ',', ' ') }}
                    </td>
                    <td class="text-end {{ ($detail['diff_entrees'] ?? 0) < 0 ? 'text-danger' : 'text-primary' }}">
                        {{ number_format($sim['total_entrees'], 0, ',', ' ') }}
                        @if(($detail['diff_entrees'] ?? 0) != 0)
                            <sup style="font-size:10px">
                                {{ ($detail['diff_entrees'] ?? 0) > 0 ? '+' : '' }}{{ number_format($detail['diff_entrees'] ?? 0, 0, ',', ' ') }}
                            </sup>
                        @endif
                    </td>

                    {{-- Sorties --}}
                    <td class="text-end text-muted border-start">
                        {{ number_format($reel?->total_sorties ?? 0, 0, ',', ' ') }}
                    </td>
                    <td class="text-end {{ ($detail['diff_sorties'] ?? 0) > 0 ? 'text-danger' : 'text-primary' }}">
                        {{ number_format($sim['total_sorties'], 0, ',', ' ') }}
                        @if(($detail['diff_sorties'] ?? 0) != 0)
                            <sup style="font-size:10px">
                                {{ ($detail['diff_sorties'] ?? 0) > 0 ? '+' : '' }}{{ number_format($detail['diff_sorties'] ?? 0, 0, ',', ' ') }}
                            </sup>
                        @endif
                    </td>

                    {{-- Solde cumulé --}}
                    <td class="text-end border-start {{ ($reel?->solde_cumule ?? 0) >= 0 ? 'text-muted' : 'text-danger' }}">
                        {{ number_format($reel?->solde_cumule ?? 0, 0, ',', ' ') }}
                    </td>
                    <td class="text-end fw-bold {{ $sim['solde_cumule'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($sim['solde_cumule'], 0, ',', ' ') }}
                        @if($diffCumule != 0)
                            <sup style="font-size:10px;color:{{ $diffCumule > 0 ? '#1D7A4F' : '#C00000' }}">
                                {{ $diffCumule > 0 ? '+' : '' }}{{ number_format($diffCumule, 0, ',', ' ') }}
                            </sup>
                        @endif
                    </td>

                    {{-- Statut --}}
                    <td class="text-center border-start">
                        @if($sim['en_deficit'])
                            <span class="badge bg-danger">Déficit</span>
                        @elseif($sim['en_tension'])
                            <span class="badge bg-warning text-dark">Tension</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

</div>
</div>

@push('scripts')
<script>
/**
 * afficherScenario() — affiche le bon bloc de paramètres
 * selon le scénario sélectionné dans le select.
 */
function afficherScenario() {
    const type = document.getElementById('typeScenario').value;

    // Cache tous les blocs de paramètres
    const blocs = [
        'bloc_retard_client',
        'bloc_baisse_activite',
        'bloc_charge_exceptionnelle'
    ];

    blocs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Affiche seulement le bloc correspondant au type choisi
    if (type) {
        const bloc = document.getElementById('bloc_' + type);
        if (bloc) bloc.style.display = 'block';
    }
}

// Initialise au chargement de la page
// (pour remettre le bon bloc si la page est rechargée après erreur)
document.addEventListener('DOMContentLoaded', afficherScenario);
</script>
@endpush

@endsection