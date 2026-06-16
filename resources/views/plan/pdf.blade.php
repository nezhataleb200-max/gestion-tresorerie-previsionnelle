<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plan de trésorerie - {{ $annee }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .warning {
            color: orange;
            font-weight: bold;
        }
        .danger {
            color: red;
            font-weight: bold;
        }
        .stats {
            margin-top: 20px;
            width: 100%;
        }
        .stats td {
            border: none;
            padding: 5px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <h1>PLAN DE TRÉSORERIE PRÉVISIONNEL</h1>
    <div class="subtitle">Année {{ $annee }} - Solde initial : {{ number_format($soldeInitial, 2, ',', ' ') }} MAD</div>
    
    <table class="stats">
        <tr>
            <td><strong>Total entrées {{ $annee }}</strong></td>
            <td>{{ number_format($stats['total_entrees_annee'], 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td><strong>Total sorties {{ $annee }}</strong></td>
            <td>{{ number_format($stats['total_sorties_annee'], 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td><strong>Solde final décembre</strong></td>
            <td class="{{ $stats['solde_final'] >= 0 ? 'success' : 'danger' }}">
                {{ number_format($stats['solde_final'], 2, ',', ' ') }} MAD
            </td>
        </tr>
        <tr>
            <td><strong>Mois à risque</strong></td>
            <td>
                {{ $stats['mois_deficitaires'] }} déficit / {{ $stats['mois_tension'] }} tension
            </td>
        </tr>
    </table>
    
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
            @foreach($plan as $ligne)
            <tr>
                <td>{{ $ligne->labelMois() }}</td>
                <td>{{ number_format($ligne->total_entrees, 2, ',', ' ') }} MAD</td>
                <td>{{ number_format($ligne->total_sorties, 2, ',', ' ') }} MAD</td>
                <td class="{{ $ligne->solde_mois >= 0 ? 'success' : 'danger' }}">
                    {{ $ligne->solde_mois >= 0 ? '+' : '' }}{{ number_format($ligne->solde_mois, 2, ',', ' ') }} MAD
                </td>
                <td class="{{ $ligne->solde_cumule >= 0 ? 'success' : 'danger' }}">
                    {{ $ligne->solde_cumule >= 0 ? '+' : '' }}{{ number_format($ligne->solde_cumule, 2, ',', ' ') }} MAD
                </td>
                <td>
                    @if($ligne->estDeficitaire())
                        <span class="danger">Déficit</span>
                    @elseif($ligne->estEnTension())
                        <span class="warning">Tension</span>
                    @else
                        <span class="success">OK</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}<br>
        KRS Trésorerie - Application de gestion de trésorerie prévisionnelle
    </div>
</body>
</html>