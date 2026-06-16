<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard KRS Trésorerie — {{ $annee }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1F3864; font-size: 20px; margin-bottom: 4px; }
        .subtitle { color: #888; font-size: 12px; margin-bottom: 24px; }
        .kpi-grid { display: flex; gap: 16px; margin-bottom: 24px; }
        .kpi { background: #f8f9fa; padding: 12px 16px; border-radius: 6px; flex: 1; }
        .kpi-label { font-size: 10px; color: #888; margin-bottom: 4px; }
        .kpi-val { font-size: 18px; font-weight: bold; }
        .green { color: #1D7A4F; }
        .red   { color: #C00000; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #1F3864; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 7px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        .ok { color: #1D7A4F; }
        .danger { color: #C00000; background: #FFF0F0; }
        .warn { color: #7B4F00; background: #FFFBF0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .logo { font-size: 24px; font-weight: bold; color: #1F3864; }
        .print-btn {
            background:#1F3864;color:white;border:none;padding:8px 16px;
            border-radius:4px;cursor:pointer;font-size:13px;margin-bottom:16px;
        }
        @media print { .print-btn { display:none; } }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">
        Imprimer / Enregistrer en PDF
    </button>

    <div class="header">
        <div>
            <div class="logo">KRS Trésorerie</div>
            <h1>Dashboard — Plan de trésorerie {{ $annee }}</h1>
            <p class="subtitle">Généré le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-label">Trésorerie finale</div>
            <div class="kpi-val {{ $kpis['tresorerie_actuelle'] >= 0 ? 'green' : 'red' }}">
                {{ number_format($kpis['tresorerie_actuelle'], 2, ',', ' ') }} MAD
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Total entrées {{ $annee }}</div>
            <div class="kpi-val green">
                {{ number_format($kpis['total_entrees'], 2, ',', ' ') }} MAD
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Total sorties {{ $annee }}</div>
            <div class="kpi-val red">
                {{ number_format($kpis['total_sorties'], 2, ',', ' ') }} MAD
            </div>
        </div>
    </div>

    {{-- Tableau plan annuel --}}
    <table>
        <thead>
            <tr>
                <th>Mois</th>
                <th>Entrées prévues</th>
                <th>Sorties prévues</th>
                <th>Solde du mois</th>
                <th>Solde cumulé</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        @foreach($planAnnuel as $ligne)
            @php
                $classTr = $ligne->solde_cumule < 0 ? 'danger' : ($ligne->solde_mois < 0 ? 'warn' : '');
            @endphp
            <tr class="{{ $classTr }}">
                <td>{{ $ligne->labelMois() }}</td>
                <td class="ok">{{ number_format($ligne->total_entrees, 2, ',', ' ') }} MAD</td>
                <td class="red">{{ number_format($ligne->total_sorties, 2, ',', ' ') }} MAD</td>
                <td class="{{ $ligne->solde_mois >= 0 ? 'ok' : 'red' }}">
                    {{ $ligne->solde_mois >= 0 ? '+' : '' }}{{ number_format($ligne->solde_mois, 2, ',', ' ') }} MAD
                </td>
                <td class="{{ $ligne->solde_cumule >= 0 ? 'ok' : 'red' }}">
                    {{ $ligne->solde_cumule >= 0 ? '+' : '' }}{{ number_format($ligne->solde_cumule, 2, ',', ' ') }} MAD
                </td>
                <td>
                    @if($ligne->solde_cumule < 0) Déficit
                    @elseif($ligne->solde_mois < 0) Tension
                    @else OK @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p style="margin-top:20px;color:#888;font-size:10px">
        KRS Facilities — Document généré automatiquement par le système de gestion de trésorerie
    </p>

</body>
</html>