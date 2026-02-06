<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Procès-Verbal de Crédit - {{ $pv->numero_pv }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section-title { background: #f2f2f2; padding: 6px; font-weight: bold; margin-top: 15px; border-left: 3px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f9f9f9; }
        .decision-favorable { color: green; font-weight: bold; }
        .decision-defavorable { color: red; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; }
        .signature-box { margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 18px;">PROCÈS-VERBAL DE DÉCISION DE CRÉDIT</h1>
        <h3 style="margin: 5px 0; color: #555;">RÉFÉRENCE : {{ $pv->numero_pv }}</h3>
    </div>

    <div class="section-title">1. INFORMATIONS SUR LE DOSSIER</div>
    <table>
        <tr>
            <td width="50%"><strong>Client :</strong> {{ $pv->creditApplication->user->name ?? 'Client inconnu' }}</td>
            <td width="50%"><strong>ID Demande :</strong> #{{ $pv->credit_application_id }}</td>
        </tr>
        <tr>
            <td><strong>Type de Crédit :</strong> {{ $pv->creditApplication->type_credit ?? 'Crédit Flash' }}</td>
            <td><strong>Date de Décision :</strong> {{ $pv->created_at->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="section-title">2. CONDITIONS APPROUVÉES</div>
    <table>
        <tr>
            <td width="33%"><strong>Montant :</strong> {{ number_format($pv->montant_approuvee, 0, ',', ' ') }} FCFA</td>
            <td width="33%"><strong>Durée :</strong> {{ $pv->duree_approuvee }} Jours</td>
            <td width="33%"><strong>Garantie :</strong> {{ $pv->nom_garantie ?? 'Non spécifiée' }}</td>
        </tr>
    </table>

    <div class="section-title">3. HISTORIQUE DES AVIS (DÉCISIONNEL)</div>
    <table>
        <thead>
            <tr>
                <th>Intervenant</th>
                <th>Niveau de Validation</th>
                <th>Avis</th>
                <th>Commentaires</th>
            </tr>
        </thead>
        <tbody>
            {{-- On boucle sur les vrais avis récupérés en base de données --}}
            @forelse($avisReels as $avis)
            <tr>
                <td><strong>{{ $avis->user->name ?? 'N/A' }}</strong></td>
                <td>{{ str_replace('_', ' ', $avis->niveau_avis) }}</td>
                <td class="{{ $avis->opinion == 'FAVORABLE' ? 'decision-favorable' : 'decision-defavorable' }}">
                    {{ $avis->opinion }}
                </td>
                <td>{{ $avis->commentaire }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center;">Aucun avis enregistré pour ce dossier.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">4. SYNTHÈSE DE LA DÉCISION</div>
    <div style="padding: 10px; border: 1px solid #ccc; margin-top: 5px; min-height: 60px;">
        {{ $pv->resume_decision }}
    </div>

    <div class="signature-box">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; width: 50%;">
                    <strong>Le Chef d'Agence</strong><br>
                    <span style="font-size: 9px;">(Signature et Cachet)</span>
                    <br><br><br><br>
                    __________________________
                </td>
                <td style="border: none; text-align: center; width: 50%;">
                    <strong>Le Président du Comité</strong><br>
                    <span style="font-size: 9px;">(Signature et Cachet)</span>
                    <br><br><br><br>
                    __________________________
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Document officiel généré par {{ $pv->generateur->name ?? 'Système' }} le {{ date('d/m/Y à H:i') }}<br>
        Athari Bank - Système de Gestion des Crédits
    </div>
</body>
</html>