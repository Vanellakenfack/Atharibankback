<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>JOURNAL DES OD DÉTAILLÉ - {{ $filters['agence'] }}</title>
    <style>
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9px; 
            color: #333; 
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            padding-bottom: 8px; 
            border-bottom: 2px solid #000;
        }
        
        .title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 3px; 
            text-transform: uppercase;
        }
        
        .subtitle { 
            font-size: 11px; 
            color: #666; 
            margin-bottom: 3px;
        }
        
        .info-section { 
            margin-bottom: 15px; 
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        .info-table td { 
            padding: 3px 5px; 
        }
        
        .piece-container {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .piece-header {
            background-color: #e9ecef;
            border-left: 4px solid #0d6efd;
            padding: 8px 10px;
            margin: 10px 0 5px 0;
            font-weight: bold;
            border-radius: 3px 0 0 3px;
        }
        
        .piece-header .piece-numero {
            font-size: 12px;
            color: #0d6efd;
        }
        
        .piece-info {
            font-size: 9px;
            color: #495057;
            margin-top: 3px;
        }
        
        .piece-info span {
            margin-right: 15px;
        }
        
        .piece-info i {
            color: #6c757d;
            font-style: normal;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 5px 0;
            font-size: 8.5px;
        }
        
        th, td { 
            border: 1px solid #adb5bd; 
            padding: 4px 3px; 
            text-align: left; 
            vertical-align: top;
        }
        
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
            text-align: center;
        }
        
        .account-code {
            font-weight: bold;
            color: #0d6efd;
        }
        
        .account-libelle {
            font-size: 8px;
            color: #495057;
        }
        
        .amount-cell {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        
        .debit-cell {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .credit-cell {
            background-color: rgba(25, 135, 84, 0.05);
        }
        
        .tiers-info {
            font-size: 7.5px;
            color: #6c757d;
            font-style: italic;
            margin-top: 2px;
        }
        
        .piece-total {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #495057;
            border-bottom: 2px solid #495057;
        }
        
        .piece-total td {
            padding: 6px 3px;
            background-color: #e9ecef;
        }
        
        .final-total {
            background-color: #d3d3d3;
            font-weight: bold;
            font-size: 10px;
            border: 2px solid #212529;
        }
        
        .final-total td {
            padding: 8px 5px;
            background-color: #dee2e6;
        }
        
        .text-right { 
            text-align: right; 
        }
        
        .text-center { 
            text-align: center; 
        }
        
        .text-bold { 
            font-weight: bold; 
        }
        
        .footer { 
            position: fixed; 
            bottom: 0; 
            width: 100%; 
            text-align: center; 
            font-size: 7px; 
            padding: 8px 0;
            border-top: 1px solid #adb5bd; 
            color: #6c757d; 
            background-color: white;
        }
        
        .signature-box { 
            margin-top: 30px; 
            width: 100%; 
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .signature-box td { 
            width: 50%; 
            padding-top: 40px;
            vertical-align: top; 
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            width: 70%;
            margin: 5px auto 0;
            padding-top: 30px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 4px;
            font-size: 7px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e9ecef;
            color: #495057;
        }
        
        .badge-mata {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .badge-epargne {
            background-color: #d1e7dd;
            color: #0a3622;
        }
        
        .badge-charge {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .ref-cell {
            font-size: 7.5px;
            color: #495057;
        }
        
        tr:nth-child(even) { 
            background-color: #f8f9fa; 
        }
        
        .equilibre-ok {
            color: #198754;
            font-weight: bold;
        }
        
        .equilibre-ko {
            color: #dc3545;
            font-weight: bold;
        }
        
        .montant-euro {
            color: #6c757d;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <!-- En-tête principal -->
    <div class="header">
        <div class="title">JOURNAL DES OPÉRATIONS DIVERSES</div>
        <div class="subtitle">{{ $filters['agence'] }}</div>
        <div class="subtitle">
            PÉRIODE DU {{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }} 
            AU {{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }}
        </div>
        <div class="subtitle">Édité le {{ $generated_at }} par {{ $generated_by }}</div>
    </div>
    
    <!-- Section d'informations -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td><strong>Période :</strong> Du {{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }}</td>
                <td><strong>Agence :</strong> {{ $filters['agence'] }}</td>
                <td><strong>Type collecte :</strong> {{ $filters['type_collecte'] }}</td>
            </tr>
            <tr>
                <td><strong>Code opération :</strong> {{ $filters['code_operation'] }}</td>
                <td><strong>Nombre de pièces :</strong> {{ $nombrePieces }}</td>
                <td><strong>Nombre d'écritures :</strong> {{ $nombreEcritures }}</td>
            </tr>
            <tr>
                <td colspan="3"><strong>Devise :</strong> FCFA (Franc CFA) - Tous les montants sont en FCFA sauf indication</td>
            </tr>
        </table>
    </div>
    
    <!-- Contenu principal -->
    @if(empty($ecrituresParPiece))
    <div style="text-align: center; padding: 50px; color: #666; font-style: italic; font-size: 12px;">
        Aucune opération diverse trouvée pour les critères sélectionnés.
    </div>
    @else
        <!-- Boucle sur chaque pièce -->
        @foreach($ecrituresParPiece as $pieceIndex => $piece)
        <div class="piece-container">
            <!-- En-tête de la pièce -->
            <div class="piece-header">
                <div class="piece-numero">PIÈCE N° {{ $piece['numero_piece'] }}</div>
                <div class="piece-info">
                    <span><i>Date opération :</i> {{ $piece['date_operation'] }}</span>
                    <span><i>Date comptable :</i> {{ $piece['date_comptable'] }}</span>
                    <span><i>Type :</i> 
                        <span class="badge 
                            @if($piece['type_collecte'] == 'MATA_BOOST') badge-mata
                            @elseif($piece['type_collecte'] == 'EPARGNE_JOURNALIERE') badge-epargne
                            @elseif($piece['type_collecte'] == 'CHARGE') badge-charge
                            @endif">
                            {{ $piece['type_collecte'] }}
                        </span>
                    </span>
                    <span><i>Code op :</i> {{ $piece['code_operation'] }}</span>
                    @if($piece['numero_guichet'] != '-')
                    <span><i>Guichet :</i> {{ $piece['numero_guichet'] }}</span>
                    @endif
                    @if($piece['numero_bordereau'] != '-')
                    <span><i>Bordereau :</i> {{ $piece['numero_bordereau'] }}</span>
                    @endif
                </div>
                <div class="piece-info">
                    <span><i>Libellé :</i> {{ $piece['libelle_global'] }}</span>
                    @if($piece['nom_tiers'] != '-')
                    <span><i>Tiers :</i> {{ $piece['nom_tiers'] }}</span>
                    @endif
                    @if($piece['reference_client'] != '-')
                    <span><i>Réf. client :</i> {{ $piece['reference_client'] }}</span>
                    @endif
                </div>
            </div>
            
            <!-- Tableau des écritures -->
            <table>
                <thead>
                    <tr>
                        <th width="5%">N°</th>
                        <th width="10%">Compte</th>
                        <th width="25%">Libellé compte</th>
                        <th width="25%">Libellé opération</th>
                        <th width="10%">Débit</th>
                        <th width="10%">Crédit</th>
                        <th width="15%">Références</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ligneNumero = 1;
                    @endphp
                    
                    @foreach($piece['ecritures'] as $ecriture)
                    <tr>
                        <td class="text-center">{{ $ligneNumero++ }}</td>
                        <td>
                            <span class="account-code">{{ $ecriture['compte_code'] }}</span>
                        </td>
                        <td>
                            {{ $ecriture['compte_libelle'] }}
                            @if($ecriture['client_nom'])
                            <div class="tiers-info">Client: {{ $ecriture['client_nom'] }} ({{ $ecriture['client_compte'] ?? '' }})</div>
                            @endif
                        </td>
                        <td>
                            {{ $ecriture['libelle_ligne'] }}
                            @if($ecriture['type_ligne'] == 'DEBIT' && $ecriture['montant_debit'] > 0 && $ecriture['client_nom'])
                            <div class="tiers-info">Débit client</div>
                            @elseif($ecriture['type_ligne'] == 'CREDIT' && $ecriture['montant_credit'] > 0 && $ecriture['client_nom'])
                            <div class="tiers-info">Crédit client</div>
                            @endif
                        </td>
                        <td class="amount-cell debit-cell">
                            @if($ecriture['montant_debit'] > 0)
                                {{ number_format($ecriture['montant_debit'], 0, ',', ' ') }}
                            @endif
                        </td>
                        <td class="amount-cell credit-cell">
                            @if($ecriture['montant_credit'] > 0)
                                {{ number_format($ecriture['montant_credit'], 0, ',', ' ') }}
                            @endif
                        </td>
                        <td class="ref-cell">
                            @if($ecriture['numero_bordereau'] != '-')
                            <div>Bord: {{ $ecriture['numero_bordereau'] }}</div>
                            @endif
                            @if($ecriture['numero_guichet'] != '-')
                            <div>Guichet: {{ $ecriture['numero_guichet'] }}</div>
                            @endif
                            @if($ecriture['reference_client'] != '-')
                            <div>Réf: {{ $ecriture['reference_client'] }}</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    
                    <!-- Ligne de total pour la pièce -->
                    <tr class="piece-total">
                        <td class="text-center text-bold">{{ $piece['nombre_lignes'] }}</td>
                        <td colspan="3" class="text-bold">TOTAL PIÈCE N° {{ $piece['numero_piece'] }}</td>
                        <td class="amount-cell text-bold debit-cell">{{ number_format($piece['total_debit'], 0, ',', ' ') }}</td>
                        <td class="amount-cell text-bold credit-cell">{{ number_format($piece['total_credit'], 0, ',', ' ') }}</td>
                        <td class="text-center">
                            @if(abs($piece['total_debit'] - $piece['total_credit']) < 0.01)
                            <span class="equilibre-ok">✓ Équilibré</span>
                            @else
                            <span class="equilibre-ko">✗ Déséquilibré</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Saut de page après chaque pièce sauf la dernière -->
        @if(!$loop->last && $pieceIndex % 3 == 2)
        <div class="page-break"></div>
        @elseif(!$loop->last)
        <div style="margin: 10px 0; border-bottom: 1px dashed #adb5bd;"></div>
        @endif
        @endforeach
        
        <!-- Total général -->
        @if($totalGeneralDebit > 0 || $totalGeneralCredit > 0)
        <table style="margin-top: 20px;">
            <tr class="final-total">
                <td colspan="4" class="text-right text-bold">TOTAUX GÉNÉRAUX DU JOURNAL</td>
                <td class="amount-cell text-bold">{{ number_format($totalGeneralDebit, 0, ',', ' ') }} FCFA</td>
                <td class="amount-cell text-bold">{{ number_format($totalGeneralCredit, 0, ',', ' ') }} FCFA</td>
                <td class="text-center text-bold">{{ $nombrePieces }} pièce(s)</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td colspan="7" class="text-center">
                    @if(abs($totalGeneralDebit - $totalGeneralCredit) < 0.01)
                    <span style="color: #198754; font-weight: bold;">✓ JOURNAL ÉQUILIBRÉ - Total Débit = Total Crédit</span>
                    @else
                    <span style="color: #dc3545; font-weight: bold;">✗ JOURNAL DÉSÉQUILIBRÉ - Différence: {{ number_format(abs($totalGeneralDebit - $totalGeneralCredit), 0, ',', ' ') }} FCFA</span>
                    @endif
                </td>
            </tr>
        </table>
        @endif
        
        <!-- Récapitulatif par type -->
        @php
            $totauxParType = [];
            foreach($ecrituresParPiece as $piece) {
                $type = $piece['type_collecte'];
                if(!isset($totauxParType[$type])) {
                    $totauxParType[$type] = ['debit' => 0, 'credit' => 0, 'pieces' => 0];
                }
                $totauxParType[$type]['debit'] += $piece['total_debit'];
                $totauxParType[$type]['credit'] += $piece['total_credit'];
                $totauxParType[$type]['pieces']++;
            }
        @endphp
        
        @if(!empty($totauxParType))
        <div style="margin-top: 15px; padding: 8px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
            <div style="font-weight: bold; margin-bottom: 5px;">RÉCAPITULATIF PAR TYPE DE COLLECTE :</div>
            <table style="width: 60%; margin: 0 auto;">
                @foreach($totauxParType as $type => $totaux)
                <tr>
                    <td><strong>{{ $type }} :</strong></td>
                    <td class="amount-cell">{{ number_format($totaux['debit'], 0, ',', ' ') }} FCFA</td>
                    <td class="amount-cell">{{ number_format($totaux['credit'], 0, ',', ' ') }} FCFA</td>
                    <td class="text-center">{{ $totaux['pieces'] }} pièce(s)</td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif
        
        <!-- Zone de signature -->
        <table class="signature-box">
            <tr>
                <td class="text-center">
                    <strong>Le Chef Comptable</strong><br>
                    <small>Nom et Signature</small>
                    <div class="signature-line"></div>
                </td>
                <td class="text-center">
                    <strong>Le Directeur Général</strong><br>
                    <small>Nom, Signature et Cachet</small>
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="text-center" style="padding-top: 20px;">
                    <small>Document conforme au plan comptable - Arrêté à la date du {{ now()->format('d/m/Y') }}</small>
                </td>
            </tr>
        </table>
    @endif
    
    <!-- Pied de page -->
    <div class="footer">
        <div>Système de Gestion Financière AthariFinancial - Journal des Opérations Diverses détaillé</div>
        <div>Généré le {{ now()->format('d/m/Y à H:i:s') }} par {{ $generated_by }} | Page {PAGE_NUM}/{PAGE_COUNT}</div>
    </div>
</body>
</html>