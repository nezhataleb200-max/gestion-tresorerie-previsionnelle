<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f8f9fa; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #7B4F00; color: white; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 28px 32px; }
        .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 16px 0; }
        .kpi { background: #f8f9fa; border-radius: 8px; padding: 14px; text-align: center; }
        .kpi-label { font-size: 12px; color: #888; margin-bottom: 4px; }
        .kpi-val { font-size: 20px; font-weight: bold; }
        .red { color: #C00000; }
        .green { color: #1D7A4F; }
        .suggestion { background: #f8f9fa; border-radius: 6px; padding: 12px 16px; margin: 8px 0; display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .suggestion-icon { font-size: 18px; }
        .footer { background: #f8f9fa; padding: 16px 32px; font-size: 12px; color: #888; text-align: center; }
        h3 { color: #1F3864; margin: 20px 0 8px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔴 Alerte Trésorerie</h1>
        <p>Solde en dessous du seuil de sécurité — Action requise</p>
    </div>
    <div class="body">
        <p>Bonjour,</p>
        <p>Le système de surveillance KRS Trésorerie a détecté que votre solde de trésorerie est passé en dessous du seuil de sécurité configuré.</p>

        <div class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Solde actuel</div>
                <div class="kpi-val red">{{ number_format($soldeActuel, 2, ',', ' ') }} MAD</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Seuil de sécurité</div>
                <div class="kpi-val green">{{ number_format($seuilSecurite, 2, ',', ' ') }} MAD</div>
            </div>
        </div>

        <h3>Actions recommandées par le système</h3>

        @foreach($suggestions as $suggestion)
        <div class="suggestion">
            <span class="suggestion-icon">{{ $suggestion['icon'] }}</span>
            <div>
                <strong>{{ $suggestion['titre'] }}</strong><br>
                <span style="color:#888;font-size:12px">{{ $suggestion['detail'] }}</span>
            </div>
        </div>
        @endforeach

        <p style="margin-top:20px;font-size:13px;color:#888;">
            Connectez-vous à l'application pour prendre les mesures nécessaires.
        </p>
    </div>
    <div class="footer">
        KRS Trésorerie — Alerte automatique générée le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>
</body>
</html>