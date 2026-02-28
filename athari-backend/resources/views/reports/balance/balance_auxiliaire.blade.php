<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Balance Auxiliaire</title>
    <style>
        /* Configuration PDF */
        @page { margin: 1cm; size: a4 landscape; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 10px; margin: 0; }
        
        /* Table styling */
        .table-balance { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table-balance th, .table-balance td { border: 1px solid #444; padding: 6px 4px; word-wrap: break-word; }
        
        /* Header styling */
        .header-section { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; text-align: center; }
        .title-main { font-size: 16px; text-transform: uppercase; margin-bottom: 5px; font-weight: bold; }
        
        /* Alignment & Weight */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .align-middle { vertical-align: middle !important; }
        
        /* Backgrounds for readability */
        .bg-main-header { background-color: #343a40 !important; color: #ffffff !important; }
        .bg-chapitre { background-color: #e9ecef !important; color: #2c3e50; }
        .bg-total-ligne { background-color: #f8f9fa !important; font-style: italic; }
        .bg-grand-total { background-color: #212529 !important; color: #ffffff !important; }
        
        /* Spacing for totals */
        .spacer-row { height: 10px; border: none !important; }
        .spacer-row td { border: none !important; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="header-section">
        <h3 class="title-main">Balance Auxiliaire des Comptes</h3>
        <h4 style="margin: 5px 0;">
            Période : Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} 
            au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        </h4>
        <p style="margin: 2px 0;">
            AGENCE : <strong>{{ $agence_nom ?? 'TOUTES LES AGENCES' }}</strong> | 
            DEVISE : <strong>XAF (FRANC CFA)</strong>
        </p>
        <div style="font-size: 9px; margin-top: 5px; text-align: right; font-style: italic;">
            Édité le {{ now()->format('d/m/Y à H:i:s') }}
        </div>
    </div>

    <table class="table-balance">
        <thead>
            <tr class="bg-main-header text-center font-bold">
                <th rowspan="2" class="align-middle" style="width: 12%;">CODE</th>
                <th rowspan="2" class="align-middle" style="width: 24%;">INTITULÉ DU COMPTE</th>
                <th colspan="2" class="text-center">SOLDE OUVERTURE</th>
                <th colspan="2" class="text-center">MOUVEMENTS PÉRIODE</th>
                <th colspan="2" class="text-center">SOLDE CLÔTURE</th>
            </tr>
            <tr class="bg-main-header text-center font-bold">
                <th style="width: 11%;">DÉBIT</th>
                <th style="width: 11%;">CRÉDIT</th>
                <th style="width: 11%;">DÉBIT</th>
                <th style="width: 11%;">CRÉDIT</th>
                <th style="width: 11%;">DÉBIT</th>
                <th style="width: 11%;">CRÉDIT</th>
            </tr>
        </thead>
        
        <tbody>
            @foreach($donnees as $codeChapitre => $groupe)
                {{-- Ligne d'en-tête du Chapitre --}}
                <tr class="bg-chapitre font-bold">
                    <td colspan="8" style="padding: 8px; font-size: 11px;">
                        CHAPITRE {{ $groupe['code_chapitre'] ?? $codeChapitre }} : {{ strtoupper($groupe['libelle_chapitre'] ?? '') }}
                    </td>
                </tr>

                @foreach($groupe['comptes'] as $compte)
                <tr>
                    {{-- Utilisation des clés confirmées par vos logs --}}
                    <td class="text-center font-bold">{{ $compte['numero'] ?? $compte['numero_compte'] ?? '-' }}</td>
                    <td>{{ strtoupper($compte['intitule_client'] ?? $compte['libelle'] ?? 'SANS NOM') }}</td>
                    
                    {{-- Solde Ouverture (Reports) --}}
                    <td class="text-right">{{ (isset($compte['report_debit']) && $compte['report_debit'] > 0) ? number_format($compte['report_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ (isset($compte['report_credit']) && $compte['report_credit'] > 0) ? number_format($compte['report_credit'], 0, ',', ' ') : '-' }}</td>
                    
                    {{-- Mouvements Période --}}
                    <td class="text-right">{{ (isset($compte['periode_debit']) && $compte['periode_debit'] > 0) ? number_format($compte['periode_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ (isset($compte['periode_credit']) && $compte['periode_credit'] > 0) ? number_format($compte['periode_credit'], 0, ',', ' ') : '-' }}</td>
                    
                    {{-- Solde Clôture --}}
                    <td class="text-right font-bold">{{ (isset($compte['solde_debit']) && $compte['solde_debit'] > 0) ? number_format($compte['solde_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right font-bold">{{ (isset($compte['solde_credit']) && $compte['solde_credit'] > 0) ? number_format($compte['solde_credit'], 0, ',', ' ') : '-' }}</td>
                </tr>
                @endforeach

                {{-- Ligne de Sous-Total par Chapitre --}}
                <tr class="bg-total-ligne font-bold">
                    <td colspan="2" class="text-right" style="padding-right: 15px;">TOTAL CHAPITRE {{ $groupe['code_chapitre'] ?? $codeChapitre }}</td>
                    <td class="text-right">{{ number_format($groupe['total_report_debit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['total_report_credit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['total_debit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['total_credit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['total_solde_debit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['total_solde_credit'] ?? 0, 0, ',', ' ') }}</td>
                </tr>
                <tr class="spacer-row"><td colspan="8"></td></tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr class="bg-grand-total font-bold">
                <td colspan="2" class="text-center" style="font-size: 11px;">TOTAL GÉNÉRAL RÉCAPITULATIF</td>
                <td class="text-right">{{ number_format($stats['total_general_debit_report'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['total_general_credit_report'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['total_general_debit_periode'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['total_general_credit_periode'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['total_general_debit'] ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($stats['total_general_credit'] ?? 0, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>