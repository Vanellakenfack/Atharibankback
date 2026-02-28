<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Brouillard de Caisse - {{ \Carbon\Carbon::parse($bilan->date_comptable)->format('d/m/Y') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a5276; padding-bottom: 10px; }
        .bank-name { font-size: 18px; font-weight: bold; color: #1a5276; text-transform: uppercase; }
        .report-title { font-size: 14px; margin-top: 5px; font-weight: bold; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 3px 0; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .data-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 10px; }
        .data-table td { border: 1px solid #eee; padding: 6px; }
        
        .section-title { 
            background-color: #1a5276; color: white; padding: 5px 10px; 
            font-size: 12px; margin-top: 25px; text-transform: uppercase; 
        }
        
        .total-row { background-color: #f9f9f9; font-weight: bold; border-top: 2px solid #333 !important; }
        .amount { text-align: right; font-family: 'Courier', monospace; white-space: nowrap; }
        
        .footer-signatures { margin-top: 40px; width: 100%; }
        .signature-box { width: 33%; float: left; text-align: center; }
        .signature-line { margin-top: 50px; border-top: 1px solid #000; width: 80%; margin-left: 10%; }
        
        .text-success { color: #27ae60; font-weight: bold; }
        .text-danger { color: #c0392b; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <div class="bank-name">Athari Bank</div>
        <div class="report-title">Brouillard de Caisse & Journalier Consolidé</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Agence :</strong> {{ $agence }}</td>
            <td align="right"><strong>Date Comptable :</strong> {{ \Carbon\Carbon::parse($bilan->date_comptable)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>ID Session :</strong> #{{ $bilan->jour_comptable_id }}</td>
            <td align="right"><strong>Édité le :</strong> {{ $edite_le }}</td>
        </tr>
    </table>

    <div class="section-title">I. Position de Trésorerie (Espèces)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Désignation des Flux Physique</th>
                <th class="amount">Montant (XAF)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total des Entrées (Versements, Appros, etc.)</td>
                <td class="amount text-success">+ {{ number_format($bilan->total_especes_entree, 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Total des Sorties (Retraits, Envois de fonds, etc.)</td>
                <td class="amount text-danger">- {{ number_format($bilan->total_especes_sortie, 0, ',', ' ') }}</td>
            </tr>
            <tr class="total-row">
                <td>SOLDE THÉORIQUE ATTENDU</td>
                <td class="amount">{{ number_format($bilan->solde_theorique_global, 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>SOLDE RÉEL INVENTORIÉ (PHYSIQUE)</td>
                <td class="amount">{{ number_format($bilan->solde_reel_global, 0, ',', ' ') }}</td>
            </tr>
            <tr class="total-row" style="background-color: #fceae9;">
                <td>ÉCART DE CAISSE GLOBAL</td>
                <td class="amount {{ $bilan->ecart_global < 0 ? 'text-danger' : ($bilan->ecart_global > 0 ? 'text-success' : '') }}">
                    {{ number_format($bilan->ecart_global, 0, ',', ' ') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">II. Détail par Guichet</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Code Caisse</th>
                <th>Caissier / Agent</th>
                <th class="amount">Entrées</th>
                <th class="amount">Sorties</th>
                <th class="amount">Solde Final</th>
                <th class="amount">Écart</th>
            </tr>
        </thead>
        <tbody>
            @foreach($caisses as $caisse)
            <tr>
                <td class="text-center">{{ $caisse['caisse_id'] }}</td>
                <td>{{ $caisse['libelle'] }}</td>
                <td class="amount">{{ number_format($caisse['entrees'] ?? 0, 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($caisse['sorties'] ?? 0, 0, ',', ' ') }}</td>
                <td class="amount"><strong>{{ number_format($caisse['solde'] ?? 0, 0, ',', ' ') }}</strong></td>
                <td class="amount {{ ($caisse['ecart'] ?? 0) < 0 ? 'text-danger' : '' }}">
                    {{ number_format($caisse['ecart'] ?? 0, 0, ',', ' ') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">III. Opérations Diverses & Scripturales (OD)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nature de l'Opération</th>
                <th class="amount">Total Débit</th>
                <th class="amount">Total Crédit</th>
                <th class="text-center">Volume</th>
            </tr>
        </thead>
        <tbody>
            @php $details = json_decode($bilan->details_operations, true) ?? []; @endphp
            @forelse($details as $op)
            <tr>
                <td>{{ $op['type_operation'] }}</td>
                <td class="amount">{{ number_format($op['total_debit'], 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($op['total_credit'], 0, ',', ' ') }}</td>
                <td class="text-center">{{ $op['nbr_transactions'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Aucune opération OD ou virement ce jour.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title" style="background-color: #444;">IV. Synthèse Compta-Caisse</div>
    <table class="data-table">
        <tr class="total-row">
            <td>CUMUL GÉNÉRAL DÉBIT (Flux de la journée)</td>
            <td class="amount">{{ number_format($bilan->total_debit_journalier, 0, ',', ' ') }} XAF</td>
        </tr>
        <tr class="total-row">
            <td>CUMUL GÉNÉRAL CRÉDIT (Flux de la journée)</td>
            <td class="amount">{{ number_format($bilan->total_credit_journalier, 0, ',', ' ') }} XAF</td>
        </tr>
        <tr>
            <td style="font-size: 12px;"><strong>CONFORMITÉ COMPTABLE</strong></td>
            <td class="amount" style="font-size: 12px;">
                @if(abs($bilan->total_debit_journalier - $bilan->total_credit_journalier) < 0.01)
                    <span class="text-success">ÉQUILIBRÉ ✓</span>
                @else
                    <span class="text-danger">DÉSÉQUILIBRÉ ✘ (Diff: {{ number_format($bilan->total_debit_journalier - $bilan->total_credit_journalier, 0) }})</span>
                @endif
            </td>
        </tr>
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