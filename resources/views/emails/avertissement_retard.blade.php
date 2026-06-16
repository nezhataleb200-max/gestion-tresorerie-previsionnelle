<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f8f9fa; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #C00000; color: white; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 6px 0 0; opacity: 0.8; font-size: 13px; }
        .body { padding: 28px 32px; }
        .alert-box { background: #FDECEA; border-left: 4px solid #C00000; border-radius: 0 6px 6px 0; padding: 14px 18px; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .table td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .table td:first-child { color: #888; width: 40%; }
        .table td:last-child { font-weight: 500; }
        .amount { font-size: 22px; font-weight: bold; color: #C00000; }
        .retard-badge { background: #FDECEA; color: #C00000; border-radius: 6px; padding: 6px 14px; font-size: 15px; font-weight: bold; }
        .footer { background: #f8f9fa; padding: 16px 32px; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚠️ Avertissement de retard de paiement</h1>
        <p>KRS Facilities — Recouvrement</p>
    </div>
    <div class="body">
        <p>Bonjour <strong>{{ $facture->client->nom }}</strong>,</p>

        <div class="alert-box">
            <strong>Votre facture {{ $facture->numero }} est en retard de paiement.</strong><br>
            <span class="retard-badge">{{ $joursRetard }} jour(s) de retard</span>
        </div>

        <p>Malgré nos conditions de paiement convenues, nous n'avons pas encore reçu le règlement de la facture suivante :</p>

        <table class="table">
            <tr><td>N° Facture</td><td>{{ $facture->numero }}</td></tr>
            <tr><td>Montant dû</td><td><span class="amount">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} MAD</span></td></tr>
            <tr><td>Date d'échéance</td><td><strong style="color:#C00000">{{ $facture->date_echeance->format('d/m/Y') }}</strong></td></tr>
            <tr><td>Jours de retard</td><td><strong style="color:#C00000">{{ $joursRetard }} jours</strong></td></tr>
        </table>

        <p>Nous vous demandons de bien vouloir procéder au règlement de cette somme dans les <strong>plus brefs délais</strong>.</p>
        <p>Sans règlement sous 7 jours, nous nous verrons dans l'obligation d'appliquer des pénalités de retard conformément à nos conditions générales.</p>

        <p style="margin-top: 24px; color: #888; font-size: 13px;">
            Si vous avez déjà procédé au paiement, merci de nous faire parvenir votre justificatif.
        </p>
    </div>
    <div class="footer">
        KRS Facilities — Message automatique de relance — {{ now()->format('d/m/Y') }}
    </div>
</div>
</body>
</html>