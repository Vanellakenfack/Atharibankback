<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal de Caisse</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .info-section { width: 100%; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; width: 33%; }
        
        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.main-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 8px; text-align: left; }
        table.main-table td { border: 1px solid #ccc; padding: 6px; }
        
        .total-row { background-color: #eee; font-weight: bold; }
        .solde-initial { color: #0056b3; font-weight: bold; background: #f9f9f9; }
        .solde-final { color: #28a745; font-weight: bold; background: #e9f5ec; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>JOURNAL DE CAISSE DETAILLED</h1>
        <p>Généré le {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td><strong>Agence :</strong> {{ $filtres['code_agence'] }}</td>
                <td><strong>Caisse :</strong> {{ $filtres['code_caisse'] }}</td>
                <td><strong>Période :</strong> Du {{ $filtres['date_debut'] }} au {{ $filtres['date_fin'] }}</td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Compte</th>
                <th>Client / Tiers</th>
                <th>Libellé de l'opération</th>
                <th class="text-right">Débit</th>
                <th class="text-right">Crédit</th>
            </tr>
        </thead>
        <tbody>
            <tr class="solde-initial">
                <td colspan="5">SOLDE D'OUVERTURE (REPORT)</td>
                <td colspan="2" class="text-right">{{ number_format($ouverture, 0, ',', ' ') }} FCFA</td>
            </tr>

            @php 
                $cumulDebit = 0; 
                $cumulCredit = 0; 
            @endphp

            @foreach($mouvements as $mvt)
                @php 
                    $cumulDebit += $mvt->montant_debit; 
                    $cumulCredit += $mvt->montant_credit; 
                @endphp
                <tr>
                    <td>{{ date('d/m/Y', strtotime($mvt->date_mouvement)) }}</td>
                    <td>{{ $mvt->reference_operation }}</td>
                    <td>{{ $mvt->numero_compte }}</td>
                    <td>{{ $mvt->tiers_nom }}</td>
                    <td>{{ $mvt->libelle_mouvement }}</td>
                    <td class="text-right">{{ number_format($mvt->montant_debit, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($mvt->montant_credit, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="5">TOTAL DES MOUVEMENTS DE LA PERIODE</td>
                <td class="text-right">{{ number_format($cumulDebit, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($cumulCredit, 0, ',', ' ') }}</td>
            </tr>

            <tr class="solde-final">
                <td colspan="5">SOLDE DE CLÔTURE AU {{ $filtres['date_fin'] }}</td>
                <td colspan="2" class="text-right">{{ number_format($cloture, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Document officiel - Signature du Caissier : _______________________ | Signature du Contrôleur : _______________________</p>
    </div>

</body>
</html>