<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Balance Générale</title>
    <style>
        /* Configuration pour PDF (DomPDF) */
        @page { margin: 1cm; size: a4 landscape; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 10px; margin: 0; }
        
        /* En-tête */
        .header-section { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #333; 
            padding-bottom: 10px; 
        }
        .title-report { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .subtitle-report { font-size: 13px; margin: 5px 0; }
        .info-detail { font-size: 11px; color: #555; }

        /* Table Style */
        .table-custom { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table-custom th, .table-custom td { border: 1px solid #444; padding: 5px 3px; word-wrap: break-word; }
        
        /* En-têtes de colonnes */
        .bg-header { background-color: #343a40 !important; color: #ffffff !important; }
        
        /* Lignes de Groupes (Classes) */
        .bg-groupe { background-color: #e9ecef !important; font-weight: bold; font-size: 11px; }
        
        /* Lignes de Sous-totaux */
        .bg-subtotal { background-color: #f8f9fa !important; font-weight: bold; font-style: italic; }
        
        /* Total Final */
        .bg-final-total { background-color: #212529 !important; color: #ffffff !important; font-weight: bold; }

        /* Utilitaires */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .spacer { height: 10px; border: none !important; }
        
        /* Alerte Déséquilibre */
        .alert-error { 
            margin-top: 15px; 
            padding: 10px; 
            border: 1px solid #dc3545; 
            color: #dc3545; 
            background-color: #f8d7da;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="header-section">
        <h2 class="title-report">Balance Générale des Comptes</h2>
        <h4 class="subtitle-report">
            Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} 
            au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        </h4>
        <p class="info-detail">
            AGENCE : <strong>{{ $agence_nom ?? 'TOUTES LES AGENCES' }}</strong> | 
            DEVISE : <strong>XAF (FRANC CFA)</strong>
        </p>
        <div style="text-align: right; font-size: 8px; font-style: italic;">
            Document généré le {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <table class="table-custom">
        <thead>
            <tr class="bg-header text-center">
                <th rowspan="2" style="width: 10%;">CODE</th>
                <th rowspan="2" style="width: 24%;">INTITULÉ DU COMPTE</th>
                <th colspan="2" style="width: 22%;">SOLDE OUVERTURE</th>
                <th colspan="2" style="width: 22%;">MOUVEMENTS PÉRIODE</th>
                <th colspan="2" style="width: 22%;">SOLDE CLÔTURE</th>
            </tr>
            <tr class="bg-header text-center">
                <th>DÉBIT</th>
                <th>CRÉDIT</th>
                <th>DÉBIT</th>
                <th>CRÉDIT</th>
                <th>DÉBIT</th>
                <th>CRÉDIT</th>
            </tr>
        </thead>
        
        <tbody>
            @foreach($donnees as $classeKey => $groupe)
                {{-- Ligne de Classe / Groupe --}}
                <tr class="bg-groupe">
                    {{-- Correction : On affiche la clé ou 'libelle' si présent pour éviter Undefined Label --}}
                    <td colspan="2">GROUPE / CLASSE : {{ $groupe['libelle'] ?? $classeKey }}</td>
                    <td colspan="6"></td>
                </tr>

                @foreach($groupe['comptes'] as $compte)
                <tr>
                    {{-- Correction : Accès en mode tableau $compte['...'] car les données sont des tableaux --}}
                    <td class="text-center font-bold">{{ $compte['code'] }}</td>
                    <td style="text-align: left;">{{ strtoupper($compte->intitule_client) }}</td>                    
                    {{-- Solde Ouverture (Report) --}}
                    <td class="text-right">{{ (isset($compte['report_debit']) && $compte['report_debit'] > 0) ? number_format($compte['report_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ (isset($compte['report_credit']) && $compte['report_credit'] > 0) ? number_format($compte['report_credit'], 0, ',', ' ') : '-' }}</td>
                    
                    {{-- Mouvements --}}
                    <td class="text-right">{{ (isset($compte['periode_debit']) && $compte['periode_debit'] > 0) ? number_format($compte['periode_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ (isset($compte['periode_credit']) && $compte['periode_credit'] > 0) ? number_format($compte['periode_credit'], 0, ',', ' ') : '-' }}</td>
                    
                    {{-- Solde Clôture --}}
                    <td class="text-right font-bold">{{ (isset($compte['solde_debit']) && $compte['solde_debit'] > 0) ? number_format($compte['solde_debit'], 0, ',', ' ') : '-' }}</td>
                    <td class="text-right font-bold">{{ (isset($compte['solde_credit']) && $compte['solde_credit'] > 0) ? number_format($compte['solde_credit'], 0, ',', ' ') : '-' }}</td>
                </tr>
                @endforeach

                {{-- Sous-total par Classe --}}
                <tr class="bg-subtotal">
                    <td colspan="2" class="text-right">SOUS-TOTAL {{ $groupe['libelle'] ?? $classeKey }}</td>
                    {{-- Calcul dynamique si les sommes ne sont pas déjà dans $groupe --}}
                    <td class="text-right">{{ number_format(collect($groupe['comptes'])->sum('report_debit'), 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format(collect($groupe['comptes'])->sum('report_credit'), 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format(collect($groupe['comptes'])->sum('periode_debit'), 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format(collect($groupe['comptes'])->sum('periode_credit'), 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['sous_total_debit'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($groupe['sous_total_credit'] ?? 0, 0, ',', ' ') }}</td>
                </tr>
                <tr class="spacer"><td colspan="8"></td></tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr class="bg-final-total">
                <td colspan="2" class="text-center">TOTAL GÉNÉRAL RÉCAPITULATIF</td>
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