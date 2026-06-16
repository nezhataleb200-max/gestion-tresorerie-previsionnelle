<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f8f9fa; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #1F3864; color: white; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 6px 0 0; opacity: 0.8; font-size: 13px; }
        .body { padding: 28px 32px; }
        .badge { display: inline-block; background: #FEF3DA; color: #633806; border-radius: 6px; padding: 4px 12px; font-size: 13px; font-weight: 500; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .table td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .table td:first-child { color: #888; width: 40%; }
        .table td:last-child { font-weight: 500; }
        .amount { font-size: 22px; font-weight: bold; color: #1D7A4F; }
        .footer { background: #f8f9fa; padding: 16px 32px; font-size: 12px; color: #888; text-align: center; }
        .btn { display: inline-block; background: #1F3864; color: white; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-top: 16px; }
        .days-badge { background: #E6F1FB; color: #0C447C; border-radius: 6px; padding: 6px 14px; font-size: 15px; font-weight: 500; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>KRS Trésorerie</h1>
        <p>Rappel de paiement — Facture bientôt due</p>
    </div>
    <div class="body">
        <p>Bonjour <strong>{{ $facture->client->nom }}</strong>,</p>
        <p>Nous vous rappelons que la facture suivante arrive à échéance dans :</p>
        <p><span class="days-badge">{{ $joursRestants }} jour(s)</span></p>

        <table class="table">
            <tr><td>N° Facture</td><td>{{ $facture->numero }}</td></tr>
            <tr><td>Montant TTC</td><td><span class="amount">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} MAD</span></td></tr>
            <tr><td>Date d'émission</td><td>{{ $facture->date_emission->format('d/m/Y') }}</td></tr>
            <tr><td>Date d'échéance</td><td><strong style="color:#C55A11">{{ $facture->date_echeance->format('d/m/Y') }}</strong></td></tr>
            @if($facture->description)
            <tr><td>Description</td><td>{{ $facture->description }}</td></tr>
            @endif
        </table>

        <p>Pour éviter tout retard, nous vous remercions de procéder au règlement avant le <strong>{{ $facture->date_echeance->format('d/m/Y') }}</strong>.</p>

        <p style="margin-top: 24px; font-size: 13px; color: #888;">
            En cas de question, n'hésitez pas à nous contacter.
        </p>
    </div>
    <div class="footer">
        KRS Facilities — Ce message est envoyé automatiquement par le système de gestion de trésorerie.
    </div>
</div>
</body>
</html>