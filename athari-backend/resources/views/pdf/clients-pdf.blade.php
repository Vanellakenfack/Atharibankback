<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Clients</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4361ee;
        }
        .header h1 {
            color: #4361ee;
            font-size: 24px;
            margin: 0 0 5px 0;
        }
        .header p {
            color: #666;
            margin: 5px 0;
            font-size: 11px;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .stat-box {
            text-align: center;
            flex: 1;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #4361ee;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #4361ee;
            color: white;
            font-weight: bold;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .client-type {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            color: white;
        }
        .type-particulier {
            background-color: #4361ee;
        }
        .type-entreprise {
            background-color: #10b981;
        }
        .photo-placeholder {
            width: 30px;
            height: 30px;
            background-color: #e0e0e0;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: #666;
        }
        .photo-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #999;
            font-size: 8px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .badge {
            background-color: #e0e7ff;
            color: #4361ee;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .info-label {
            color: #666;
            font-size: 8px;
        }
        .info-value {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-weight: bold; font-size: 12px;">ATHARI BANK</div>
        <h3> PORTEFEUILLE CLIENTS</h1>
        <p>Liste exhaustive des clients - Généré le {{ $stats['date_export'] }}</p>
        <p>Exporté par : {{ $stats['exported_by'] }}</p>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-value">{{ $stats['total_clients'] }}</div>
            <div class="stat-label">Total Clients</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $stats['total_physiques'] }}</div>
            <div class="stat-label">Particuliers</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $stats['total_morales'] }}</div>
            <div class="stat-label">Entreprises</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">N°</th>
                <th width="15%">Client / Raison Sociale</th>
                <th width="8%">Type</th>
                <th width="10%">N° Client</th>
                <th width="10%">CNI/RCCM</th>
                <th width="10%">NUI</th>
                <th width="10%">Contact</th>
                <th width="12%">Localisation</th>
                <th width="10%">Profession/Gérant</th>
                <th width="10%">Date création</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientsData as $index => $client)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>
                        @if(isset($client['nom_complet']))
                            {{ $client['nom_complet'] }}
                        @elseif(isset($client['raison_sociale']))
                            {{ $client['raison_sociale'] }}
                            @if(!empty($client['sigle']))
                                <br><small>({{ $client['sigle'] }})</small>
                            @endif
                        @endif
                    </strong>
                </td>
                <td>
                    <span class="client-type {{ $client['type_client'] == 'Particulier' ? 'type-particulier' : 'type-entreprise' }}">
                        {{ $client['type_client'] }}
                    </span>
                </td>
                <td><strong>{{ $client['num_client'] }}</strong></td>
                <td>
                    @if(isset($client['cni']))
                        {{ $client['cni'] }}
                    @elseif(isset($client['rccm']))
                        {{ $client['rccm'] }}
                    @else
                        <span class="info-label">Non spécifié</span>
                    @endif
                </td>
                <td>
                    @if(isset($client['nui']) && $client['nui'])
                        {{ $client['nui'] }}
                    @else
                        <span class="info-label">-</span>
                    @endif
                </td>
                <td>
                    {{ $client['telephone'] ?? '-' }}<br>
                    <small>{{ $client['email'] ?? '-' }}</small>
                </td>
                <td>
                    {{ $client['adresse'] ?? '-' }}<br>
                    @if(!empty($client['ville_activite']) || !empty($client['quartier_activite']))
                        <small>Act.: {{ $client['ville_activite'] }} {{ $client['quartier_activite'] }}</small>
                    @endif
                </td>
                <td>
                    @if(isset($client['profession']))
                        {{ $client['profession'] }}
                        @if(isset($client['employeur']))
                            <br><small>@ {{ $client['employeur'] }}</small>
                        @endif
                    @elseif(isset($client['nom_gerant']))
                        <strong>Gérant:</strong> {{ $client['nom_gerant'] }}<br>
                        @if(isset($client['nom_gerant2']))
                            <small>Gérant 2: {{ $client['nom_gerant2'] }}</small>
                        @endif
                    @endif
                </td>
                <td>{{ $client['date_creation'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($clientsData) == 0)
        <div style="text-align: center; padding: 50px; color: #999;">
            Aucun client trouvé dans la base de données
        </div>
    @endif

    <div class="footer">
        <p>Document généré automatiquement - {{ config('app.name') }} - Tous droits réservés</p>
        <p>Ce document contient {{ count($clientsData) }} client(s) - Export PDF le {{ $stats['date_export'] }}</p>
    </div>
</body>
</html>