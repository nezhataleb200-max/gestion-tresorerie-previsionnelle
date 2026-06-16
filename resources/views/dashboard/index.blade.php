@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')

{{-- Barre d'outils --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="text-muted small">
        {{ \Carbon\Carbon::create()->month($mois)->locale('fr')->monthName }}
        {{ $annee }}
    </span>
    <a href="{{ route('dashboard.export') }}" target="_blank" class="btn btn-dark btn-sm">
        <i class="bi bi-file-pdf me-1"></i> Exporter PDF
    </a>
</div>

{{-- Alertes critiques --}}
@if($alertes->where('niveau','critique')->count())
<div class="mb-4">
    @foreach($alertes->where('niveau','critique') as $alerte)
    <div class="alert alert-danger d-flex justify-content-between align-items-center py-2 mb-2">
        <div>
            <i class="bi bi-exclamation-octagon me-2"></i>
            <strong>Déficit prévu</strong> — {{ $alerte->message }}
        </div>
        <a href="{{ route('alertes.index') }}" class="btn btn-sm btn-outline-danger">Voir</a>
    </div>
    @endforeach
</div>
@endif

{{-- 4 KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="text-muted small mb-1">Trésorerie actuelle</div>
            <div class="h4 mb-0 {{ $kpis['tresorerie_actuelle'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($kpis['tresorerie_actuelle'], 0, ',', ' ') }} MAD
            </div>
            <div class="text-muted" style="font-size:11px">Solde cumulé {{ now()->format('M Y') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="text-muted small mb-1">Entrées ce mois</div>
            <div class="h4 text-success mb-0">
                {{ number_format($kpis['entrees_mois'], 0, ',', ' ') }} MAD
            </div>
            <div class="text-muted" style="font-size:11px">Factures échues</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="text-muted small mb-1">Sorties ce mois</div>
            <div class="h4 text-danger mb-0">
                {{ number_format($kpis['sorties_mois'], 0, ',', ' ') }} MAD
            </div>
            <div class="text-muted" style="font-size:11px">Charges prévues</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light text-center p-3">
            <div class="text-muted small mb-1">Factures en retard</div>
            <div class="h4 mb-0 {{ $kpis['factures_en_retard'] > 0 ? 'text-warning' : 'text-success' }}">
                {{ $kpis['factures_en_retard'] }}
            </div>
            <div class="text-muted" style="font-size:11px">
                <a href="{{ route('factures.index', ['statut' => 'en_retard']) }}" class="text-muted">
                    Voir les factures
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Graphiques --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-500">Entrées vs Sorties — {{ $annee }}</div>
            <div class="card-body">
                <canvas id="chartBarres" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-500">Charges par catégorie</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartCamembert" style="max-height:220px"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-500">Évolution du solde cumulé — {{ $annee }}</div>
    <div class="card-body">
        <canvas id="chartCourbe" height="80"></canvas>
    </div>
</div>

{{-- Factures urgentes --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-500">Factures urgentes</span>
        <a href="{{ route('factures.index') }}" class="btn btn-sm btn-link text-muted">Voir toutes</a>
    </div>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>N°</th><th>Client</th><th>Montant</th>
                <th>Échéance</th><th>Statut</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($facturesUrgentes as $f)
        <tr>
            <td class="fw-500">{{ $f->numero }}</td>
            <td>{{ $f->client->nom }}</td>
            <td>{{ number_format($f->montant_ttc, 2, ',', ' ') }} MAD</td>
            <td>{{ $f->date_echeance->format('d/m/Y') }}</td>
            <td>
                @if($f->statut === 'en_retard')
                    <span class="badge bg-danger">En retard</span>
                @else
                    <span class="badge bg-warning text-dark">Urgent</span>
                @endif
            </td>
            <td>
                <form method="POST" action="{{ route('factures.payer', $f) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-success">
                        <i class="bi bi-check-circle"></i> Payer
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-success py-3">
                <i class="bi bi-check-circle me-1"></i> Aucune facture urgente
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection

{{-- ════════════════════════════════════════════════════ --}}
{{-- SCRIPTS — Chart.js + données en bas de page         --}}
{{-- ════════════════════════════════════════════════════ --}}
@push('scripts')

{{-- Chart.js chargé depuis CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Données PHP → JSON → JavaScript
const LABELS   = @json($graphiqueData['labels']);
const ENTREES  = @json($graphiqueData['entrees']);
const SORTIES  = @json($graphiqueData['sorties']);
const CUMULES  = @json($graphiqueData['cumules']);
const CAM_LAB  = @json($camembertData['labels']);
const CAM_VAL  = @json($camembertData['valeurs']);

// Vérification debug (ouvre F12 → Console pour voir)
console.log('Chart.js version:', Chart.version);
console.log('Labels:', LABELS);
console.log('Entrées:', ENTREES);
console.log('Sorties:', SORTIES);
console.log('Cumulés:', CUMULES);

// ══ GRAPHIQUE 1 : BARRES GROUPÉES ═══════════════════════
const ctx1 = document.getElementById('chartBarres');
if (ctx1) {
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: LABELS,
            datasets: [
                {
                    label: 'Entrées (MAD)',
                    data: ENTREES,
                    backgroundColor: 'rgba(29, 158, 117, 0.75)',
                    borderColor: 'rgba(29, 158, 117, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Sorties (MAD)',
                    data: SORTIES,
                    backgroundColor: 'rgba(226, 75, 74, 0.75)',
                    borderColor: 'rgba(226, 75, 74, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.dataset.label + ' : ' +
                            new Intl.NumberFormat('fr-MA').format(ctx.raw) + ' MAD'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// ══ GRAPHIQUE 2 : COURBE SOLDE CUMULÉ ═══════════════════
const ctx2 = document.getElementById('chartCourbe');
if (ctx2) {
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: LABELS,
            datasets: [{
                label: 'Solde cumulé (MAD)',
                data: CUMULES,
                borderColor: 'rgba(56, 138, 221, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return 'rgba(56,138,221,0.1)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(29,158,117,0.2)');
                    gradient.addColorStop(1, 'rgba(226,75,74,0.1)');
                    return gradient;
                },
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: CUMULES.map(v =>
                    v >= 0 ? 'rgba(29,158,117,1)' : 'rgba(226,75,74,1)'
                ),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const s = ctx.raw >= 0 ? '+' : '';
                            return 'Solde cumulé : ' + s +
                                new Intl.NumberFormat('fr-MA').format(ctx.raw) + ' MAD';
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: v => {
                            const s = v >= 0 ? '+' : '';
                            return s + (Math.abs(v) >= 1000 ? (v/1000).toFixed(0)+'k' : v);
                        }
                    },
                    grid: {
                        color: ctx => ctx.tick.value === 0
                            ? 'rgba(0,0,0,0.25)'
                            : 'rgba(0,0,0,0.05)'
                    }
                }
            }
        }
    });
}

// ══ GRAPHIQUE 3 : CAMEMBERT CHARGES ═════════════════════
const ctx3 = document.getElementById('chartCamembert');
if (ctx3) {
    const couleurs = [
        'rgba(83,74,183,0.8)',
        'rgba(29,158,117,0.8)',
        'rgba(186,117,23,0.8)',
        'rgba(226,75,74,0.8)',
        'rgba(56,138,221,0.8)',
        'rgba(136,135,128,0.8)',
    ];

    // Si pas de données → afficher message
    const labels = CAM_LAB.length > 0 ? CAM_LAB : ['Aucune charge'];
    const valeurs = CAM_VAL.length > 0 ? CAM_VAL : [1];

    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valeurs,
                backgroundColor: couleurs.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            if (CAM_VAL.length === 0) return 'Aucune charge ce mois';
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct = ((ctx.raw / total) * 100).toFixed(1);
                            return ctx.label + ' : ' +
                                new Intl.NumberFormat('fr-MA').format(ctx.raw) +
                                ' MAD (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
}
</script>

@endpush