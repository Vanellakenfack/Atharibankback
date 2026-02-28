<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; }
        
        /* En-tête */
        .header-table { width: 100%; border-bottom: 2px solid #004a99; margin-bottom: 20px; }
        .logo-text { font-size: 22pt; font-weight: bold; color: #004a99; }
        
        /* Layout des infos (Cards) */
        .layout-table { width: 100%; border-spacing: 10px 0; margin-left: -10px; }
        .card { 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            padding: 10px; 
            background-color: #fcfcfc;
            vertical-align: top;
            width: 50%; /* Force l'égalité des colonnes */
        }
        .info-title { 
            font-weight: bold; 
            color: #004a99; 
            border-bottom: 1px solid #eee; 
            margin-bottom: 8px; 
            padding-bottom: 3px;
            text-transform: uppercase;
            font-size: 9pt;
        }

        /* Tableau des mouvements */
        .mouvements-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .mouvements-table th { background-color: #004a99; color: white; padding: 8px; font-size: 8pt; }
        .mouvements-table td { padding: 7px; border-bottom: 1px solid #eee; font-size: 8pt; }
        .report-row { background-color: #eef4ff; font-weight: bold; } /* Ligne report à nouveau */
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .debit { color: #dc3545; }
        .credit { color: #28a745; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #777; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="padding-bottom: 10px;">
                <span class="logo-text">Athari Bank</span><br>
                <small>{{ $compte->client->agency->short_name ?? 'Agence Principale' }}</small>
            </td>
            <td class="text-right">
                <strong style="font-size: 12pt;">RELEVÉ DE COMPTE DÉTAILLÉ</strong><br>
                Période : {{ $date_debut }} au {{ $date_fin }}
            </td>
        </tr>
    </table>

    <table class="layout-table">
        <tr>
            <td class="card">
                <div class="info-title">Titulaire du compte</div>
                <strong>{{ $compte->client->nom_complet }}</strong><br>
                ID : {{ $compte->client->num_client }}<br>
                {{ $compte->client->adresse ?? 'Adresse non renseignée' }}<br>
                Tél : {{ $compte->client->telephone }}
            </td>
            <td class="card">
                <div class="info-title">Résumé du compte</div>
                N° : <span class="bold">{{ $compte->numero_compte }}</span><br>
                Produit : {{ $compte->typeCompte->libelle }}<br>
                Devise : {{ $compte->devise ?? 'XAF' }}<br>
                Solde au {{ $date_fin }} : <strong>{{ number_format($compte->solde, 0, ',', ' ') }}</strong>
            </td>
        </tr>
    </table>

    <table class="mouvements-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="15%">Référence</th>
                <th width="35%">Libellé de l'opération</th>
                <th width="12%" class="text-right">Débit</th>
                <th width="12%" class="text-right">Crédit</th>
                <th width="16%" class="text-right">Solde progressif</th>
            </tr>
        </thead>
        <tbody>
            <tr class="report-row">
                <td class="text-center">{{ $date_debut }}</td>
                <td class="text-center">-</td>
                <td>SOLDE INITIAL (REPORT À NOUVEAU)</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($historique['solde_initial'], 0, ',', ' ') }}</td>
            </tr>

            @foreach($historique['mouvements'] as $index => $mvt)
                <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
                    <td class="text-center">{{ \Carbon\Carbon::parse($mvt['date'])->format('d/m/Y') }}</td>
                    <td class="text-center" style="font-family: monospace; font-size: 7pt;">{{ $mvt['reference'] }}</td>
                    <td>{{ $mvt['libelle'] }}</td>
                    <td class="text-right debit">
                        {{ $mvt['debit'] > 0 ? number_format($mvt['debit'], 0, ',', ' ') : '' }}
                    </td>
                    <td class="text-right credit">
                        {{ $mvt['credit'] > 0 ? number_format($mvt['credit'], 0, ',', ' ') : '' }}
                    </td>
                    <td class="text-right bold">
                        {{ number_format($mvt['solde_apres'], 0, ',', ' ') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Athari Bank - Document confidentiel généré le {{ $date_gen }}<br>
        <strong>ATHARI FINANCIAL SYSTEM V1.0</strong>
    </div>

</body>
</html>