<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal de Caisse - {{ $code_caisse }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .info-section { width: 100%; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px; width: 33%; }
        
        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.main-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 9px; }
        table.main-table td { border: 1px solid #ccc; padding: 5px; word-wrap: break-word; }
        
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
        <h1>JOURNAL DE CAISSE DÉTAILLÉ ATHARIFINANCIAL</h1>
        <p style="font-size: 12px;">Extraction du Journal des Opérations</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td><strong>Agence :</strong> {{ $filtres['code_agence'] ?? 'N/A' }}</td>
                <td><strong>Caisse :</strong> {{ $code_caisse ?? 'N/A' }}</td>
                <td><strong>Période :</strong> Du {{ $filtres['date_debut'] }} au {{ $filtres['date_fin'] }}</td>
            </tr>
            <tr>
                <td><strong>Généré le :</strong> {{ date('d/m/Y H:i') }}</td>
                <td></td>
                <td class="text-right"><strong>Devise :</strong> FCFA</td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="8%">Date</th>
                <th width="12%">Référence</th>
                <th width="15%">Client / Tiers</th>
                <th width="35%">Libellé de l'opération</th>
                <th width="15%" class="text-right">Entrée (Débit)</th>
                <th width="15%" class="text-right">Sortie (Crédit)</th>
            </tr>
        </thead>
        <tbody>
            {{-- REPORT DU SOLDE D'OUVERTURE --}}
            <tr class="solde-initial">
                <td colspan="4">SOLDE D'OUVERTURE (REPORT SYSTÈME)</td>
                <td colspan="2" class="text-right">{{ number_format($ouverture, 0, ',', ' ') }}</td>
            </tr>

            @php 
                $grandTotalEntree = 0; 
                $grandTotalSortie = 0; 
            @endphp

            @foreach($groupes_mouvements as $type => $mouvements)
                @php 
                    $sousTotalEntree = $mouvements->sum('entree');
                    $sousTotalSortie = $mouvements->sum('sortie');
                    $grandTotalEntree += $sousTotalEntree;
                    $grandTotalSortie += $sousTotalSortie;
                @endphp

                {{-- Entête de groupe par type d'opération --}}
                <tr class="type-header">
                    <td colspan="6">TYPE : {{ $type }}</td>
                </tr>

                @foreach($mouvements as $mvt)
                    <tr>
                        <td class="text-center">{{ date('d/m/Y', strtotime($mvt->date)) }}</td>
                        <td class="text-bold">{{ $mvt->reference }}</td>
                        <td>{{ $mvt->tiers }}</td>
                        <td>{{ $mvt->libelle }}</td>
                        <td class="text-right" style="color: {{ $mvt->entree > 0 ? '#1e7e34' : '#333' }};">
                            {{ $mvt->entree > 0 ? number_format($mvt->entree, 0, ',', ' ') : '-' }}
                        </td>
                        <td class="text-right" style="color: {{ $mvt->sortie > 0 ? '#bd2130' : '#333' }};">
                            {{ $mvt->sortie > 0 ? number_format($mvt->sortie, 0, ',', ' ') : '-' }}
                        </td>
                    </tr>
                @endforeach

                {{-- Sous-total pour le type en cours --}}
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">Sous-total {{ $type }} :</td>
                    <td class="text-right">{{ number_format($sousTotalEntree, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($sousTotalSortie, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            {{-- TOTAUX GÉNÉRAUX --}}
            <tr class="total-row">
                <td colspan="4">TOTAL DES MOUVEMENTS DE LA PÉRIODE</td>
                <td class="text-right">{{ number_format($grandTotalEntree, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($grandTotalSortie, 0, ',', ' ') }}</td>
            </tr>

            {{-- SOLDE FINAL --}}
            <tr class="solde-final">
                <td colspan="4">SOLDE THÉORIQUE DE CLÔTURE (Ouverture + Flux)</td>
                <td colspan="2" class="text-right">
                    {{ number_format($ouverture + $grandTotalEntree - $grandTotalSortie, 0, ',', ' ') }} FCFA
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ZONE DE SIGNATURE --}}
    <table class="signature-box">
        <tr>
            <td class="text-center">
                <strong>Le Caissier</strong><br>
                <small>(Nom et Signature)</small>
                <div style="margin-top: 40px; border-bottom: 1px dashed #ccc; width: 150px; margin-left: auto; margin-right: auto;"></div>
            </td>
            <td class="text-center">
                <strong>Le Chef d'Agence / Contrôleur</strong><br>
                <small>(Nom, Signature et Cachet)</small>
                <div style="margin-top: 40px; border-bottom: 1px dashed #ccc; width: 150px; margin-left: auto; margin-right: auto;"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>AthariFinancial System - Document généré informatiquement - Page 1/1</p>
    </div>

</body>
</html>