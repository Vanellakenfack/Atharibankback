<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #1a4a7c; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 16px; font-weight: bold; text-decoration: underline; color: #1a4a7c; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .section-title { background-color: #1a4a7c; color: white; padding: 4px; font-weight: bold; margin-top: 15px; }
        .footer-sigs { margin-top: 40px; clear: both; }
        .sig-col { width: 25%; float: left; text-align: center; font-size: 9px; }
        .client-info { margin-bottom: 15px; }
        .info-row { margin-bottom: 5px; }
        .info-label { font-weight: bold; color: #1a4a7c; display: inline-block; width: 150px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 20px; font-weight: bold;">ATHARI FINANCIAL COOP</div>
        <div class="title">PROCES-VERBAL DE COMITE DE CREDIT</div>
        <p>Généré le : {{ $date_generation }}</p>
    </div>

    <div class="section-title">I. IDENTIFICATION DU DOSSIER</div>
    <table>
        <tr>
            <td><strong>N° Dossier :</strong> {{ $application->numero_demande ?? 'N/A' }}</td>
            <td><strong>Date de demande :</strong> {{ $application->created_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Activité :</strong> {{ $application->activite ?? 'Non renseignée' }}</td>
        </tr>
    </table>

    <!-- Section Informations Client depuis l'API -->
    <div class="section-title">II. INFORMATIONS DU CLIENT</div>
    <div class="client-info">
        @if(isset($clientData) && $clientData)
            <div class="info-row">
                <span class="info-label">Nom complet :</span> 
                {{ $clientData['nom'] ?? '' }} {{ $clientData['prenom'] ?? '' }}
            </div>
            <div class="info-row">
                <span class="info-label">Téléphone :</span> {{ $clientData['telephone'] ?? 'Non renseigné' }}
            </div>
            <div class="info-row">
                <span class="info-label">Adresse :</span> {{ $clientData['adresse'] ?? 'Non renseignée' }}
            </div>
            <div class="info-row">
                <span class="info-label">Type de pièce :</span> {{ $clientData['type_piece'] ?? 'Non renseigné' }}
            </div>
            <div class="info-row">
                <span class="info-label">Numéro pièce :</span> {{ $clientData['numero_piece'] ?? 'Non renseigné' }}
            </div>
            <div class="info-row">
                <span class="info-label">Activité :</span> {{ $clientData['activite'] ?? 'Non renseignée' }}
            </div>
        @else
            <div class="info-row">Informations client non disponibles</div>
        @endif
    </div>

    <div class="section-title">III. ANALYSE ET DÉCISION DU COMITÉ</div>
    <p><strong>Objet :</strong> Étude de la demande de {{ number_format($application->montant ?? 0, 0, ',', ' ') }} FCFA</p>
    
    <table>
        <thead>
            <tr>
                <th>Membre du Comité</th>
                <th>Rôle</th>
                <th>Avis</th>
                <th>Commentaires</th>
            </tr>
        </thead>
        <tbody>
            @forelse($avisComite as $avis)
            <tr>
                <td>{{ $avis->user->name ?? 'Utilisateur' }}</td>
                <td>{{ $avis->role ?? 'Membre' }}</td>
                <td><strong>{{ $avis->opinion }}</strong></td>
                <td>{{ $avis->commentaire ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="4">Aucun vote de comité enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">IV. CONDITIONS FINALES ACCORDÉES</div>
    <table>
        <tr>
            <th>Montant Validé</th>
            <td>{{ number_format($application->montant_accorde ?? 0, 0, ',', ' ') }} FCFA</td>
            <th>Durée</th>
            <td>{{ $application->duree_jours ?? 0 }} jours</td>
        </tr>
        <tr>
            <th>Échéance Journalière</th>
            <td>{{ number_format($application->mensualite ?? 0, 0, ',', ' ') }} FCFA</td>
            <th>Date Déblocage</th>
            <td>{{ isset($application->date_deblocage) ? Carbon\Carbon::parse($application->date_deblocage)->format('d/m/Y') : 'A définir' }}</td>
        </tr>
        <tr>
            <th>Garantie Retenue</th>
            <td colspan="3">{{ $application->garantie_demandee ?? 'Aucune' }}</td>
        </tr>
    </table>

    <div class="footer-sigs">
        <div class="sig-col">Chef d'Agence<br><br><br>__________</div>
        <div class="sig-col">Comptabilité<br><br><br>__________</div>
        <div class="sig-col">Agent Collecteur<br><br><br>__________</div>
        <div class="sig-col">Le Client<br><br><br>__________</div>
    </div>
</body>
</html>