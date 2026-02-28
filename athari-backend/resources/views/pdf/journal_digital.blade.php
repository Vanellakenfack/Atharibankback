<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal Digital - {{ $operateur ?? 'Tous' }}</title>
    <style>
        /* Reprise stricte de vos styles */
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .info-section { width: 100%; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px; width: 33%; }
        
        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.main-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 8px; }
        table.main-table td { border: 1px solid #ccc; padding: 5px; word-wrap: break-word; vertical-align: middle; }
        
        .type-header { background-color: #fcfcfc; font-weight: bold; color: #444; font-size: 10px; }
        .subtotal-row { background-color: #fafafa; font-style: italic; font-weight: bold; color: #555; }
        .total-row { background-color: #eee; font-weight: bold; font-size: 11px; }
        
        .solde-initial { color: #0056b3; font-weight: bold; background: #f0f7ff; }
        .solde-final { color: #28a745; font-weight: bold; background: #e9f5ec; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .footer { position: fixed; bottom: 30px; width: 100%; text-align: center; font-size: 9px; }
        .signature-box { margin-top: 20px; width: 100%; }
        .signature-box td { width: 50%; padding-top: 10px; height: 60px; vertical-align: top; }
    </style>
</head>
<body>

    <div class="header">
        <h1>JOURNAL DES OPÉRATIONS DIGITALES ATHARIFINANCIAL</h1>
        <p style="font-size: 12px;">Réconciliation des Flux Orange Money & Mobile Money</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td><strong>Agence :</strong> {{ $filtres['agence'] ?? 'Toutes' }}</td>
                <td><strong>Opérateur :</strong> {{ $operateur ?? 'Tous Operateurs' }}</td>
                <td><strong>Période :</strong> Du {{ $date_debut }} au {{ $date_fin }}</td>
            </tr>
            <tr>
                <td><strong>Généré le :</strong> {{ date('d/m/Y H:i') }}</td>
                <td><strong>Statut :</strong> VALIDE</td>
                <td class="text-right"><strong>Devise :</strong> FCFA</td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="10%">Date & Heure</th>
                <th width="12%">Référence Athari</th>
                <th width="10%">Opérateur</th>
                <th width="15%">Client / Téléphone</th>
                <th width="13%">Réf. Opérateur (SIM)</th>
                <th width="15%" class="text-right">Entrée (Dépôt Cash)</th>
                <th width="15%" class="text-right">Sortie (Retrait Cash)</th>
                <th width="10%" class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalEntree = 0; 
                $grandTotalSortie = 0; 
                $grandTotalComm = 0;
            @endphp

            @foreach($groupes as $typeFlux => $transactions)
                @php 
                    $sousTotalEntree = $transactions->where('type_flux', 'VERSEMENT')->sum('montant_brut');
                    $sousTotalSortie = $transactions->where('type_flux', 'RETRAIT')->sum('montant_brut');
                    $sousTotalComm = $transactions->sum('commissions');
                    
                    $grandTotalEntree += $sousTotalEntree;
                    $grandTotalSortie += $sousTotalSortie;
                    $grandTotalComm += $sousTotalComm;
                @endphp

                <tr class="type-header">
                    <td colspan="8">FLUX : {{ $typeFlux }}</td>
                </tr>

                @foreach($transactions as $t)
                    <tr>
                        <td class="text-center">{{ $t->date_operation->format('d/m/Y H:i') }}</td>
                        <td class="text-bold">{{ $t->reference_unique }}</td>
                        <td class="text-center">{{ $t->operateur }}</td>
                        <td>
                            {{ $t->compteBancaire ? $t->compteBancaire->client->nom_complet : 'PASSAGE' }}<br>
                            <small>Tel: {{ $t->telephone_client }}</small>
                        </td>
                        <td style="font-size: 8px;">{{ $t->reference_operateur }}</td>
                        <td class="text-right">{{ $t->type_flux == 'VERSEMENT' ? number_format($t->montant_brut, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $t->type_flux == 'RETRAIT' ? number_format($t->montant_brut, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ number_format($t->commissions, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach

                <tr class="subtotal-row">
                    <td colspan="5" class="text-right">Sous-total {{ $typeFlux }} :</td>
                    <td class="text-right">{{ number_format($sousTotalEntree, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($sousTotalSortie, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($sousTotalComm, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="5">TOTAL GÉNÉRAL DES FLUX DIGITAUX</td>
                <td class="text-right">{{ number_format($grandTotalEntree, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($grandTotalSortie, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($grandTotalComm, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ZONE DE SIGNATURE --}}
    <table class="signature-box">
        <tr>
            <td class="text-center">
                <strong>Le Responsable Digital</strong><br>
                <div style="margin-top: 40px; border-bottom: 1px dashed #ccc; width: 150px; margin-left: auto; margin-right: auto;"></div>
            </td>
            <td class="text-center">
                <strong>Le Contrôleur Interne</strong><br>
                <div style="margin-top: 40px; border-bottom: 1px dashed #ccc; width: 150px; margin-left: auto; margin-right: auto;"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>AthariFinancial System - Journal de Réconciliation Digital - Page 1/1</p>
    </div>

</body>
</html>