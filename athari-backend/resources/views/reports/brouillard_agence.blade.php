<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Brouillard de Caisse - {{ $bilan->date_comptable->format('d/m/Y') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1a5276; padding-bottom: 10px; }
        .bank-name { font-size: 20px; font-weight: bold; color: #1a5276; text-transform: uppercase; }
        .report-title { font-size: 16px; margin-top: 5px; text-decoration: underline; }
        
        .info-section { width: 100%; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table td { border: 1px solid #ddd; padding: 8px; }
        
        .total-row { background-color: #f9f9f9; font-weight: bold; }
        .amount { text-align: right; font-family: 'Courier', monospace; }
        
        .footer-signatures { margin-top: 50px; width: 100%; }
        .signature-box { width: 33%; float: left; text-align: center; height: 100px; }
        .signature-line { margin-top: 60px; border-top: 1px solid #000; width: 80%; margin-left: 10%; }
        
        .page-break { page-break-after: always; }
        .text-success { color: #27ae60; }
        .text-danger { color: #c0392b; }
    </style>
</head>
<body>

    <div class="header">
        <div class="bank-name">Athari Bank</div>
        <div class="report-title">Brouillard de Caisse Journalier Consolidé</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Agence :</strong> {{ $agence }}</td>
            <td align="right"><strong>Date Comptable :</strong> {{ $bilan->date_comptable->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>ID Session :</strong> #{{ $bilan->jour_comptable_id }}</td>
            <td align="right"><strong>Édité le :</strong> {{ $edite_le }}</td>
        </tr>
    </table>

    <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">I. RÉSUMÉ DES FLUX DE TRÉSORERIE</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="amount">Montant (XAF)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total des Entrées (Versements & Approvisionnements)</td>
                <td class="amount text-success">+ {{ number_format($bilan->total_especes_entree, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Total des Sorties (Retraits & Envois de fonds)</td>
                <td class="amount text-danger">- {{ number_format($bilan->total_especes_sortie, 2, ',', ' ') }}</td>
            </tr>
            <tr class="total-row">
                <td>SOLDE THÉORIQUE DE FIN DE JOURNÉE</td>
                <td class="amount">{{ number_format($bilan->solde_theorique_global, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Solde Réel Inventorié (Physique)</td>
                <td class="amount">{{ number_format($bilan->solde_reel_global, 2, ',', ' ') }}</td>
            </tr>
            <tr class="total-row">
                <td>ÉCART GLOBAL CONSTATÉ</td>
                <td class="amount @if($bilan->ecart_global < 0) text-danger @endif">
                    {{ number_format($bilan->ecart_global, 2, ',', ' ') }}
                </td>
            </tr>
        </tbody>
    </table>

    <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 30px;">II. DÉTAIL PAR CAISSE / GUICHET</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Caisse</th>
                <th>Caissier</th>
                <th class="amount">Entrées</th>
                <th class="amount">Sorties</th>
                <th class="amount">Solde Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($caisses as $caisse)
            <tr>
                <td>{{ $caisse['code_caisse'] ?? 'N/A' }}</td>
                <td>{{ $caisse['nom_caissier'] ?? 'Agent' }}</td>
                <td class="amount">{{ number_format($caisse['entrees'] ?? 0, 2, ',', ' ') }}</td>
                <td class="amount">{{ number_format($caisse['sorties'] ?? 0, 2, ',', ' ') }}</td>
                <td class="amount"><strong>{{ number_format($caisse['solde'] ?? 0, 2, ',', ' ') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    

    <div class="footer-signatures">
        <div class="signature-box">
            <strong>Caissier Principal</strong>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>Contrôleur Interne</strong>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>Chef d'Agence</strong>
            <div class="signature-line"></div>
        </div>
    </div>

</body>
</html>