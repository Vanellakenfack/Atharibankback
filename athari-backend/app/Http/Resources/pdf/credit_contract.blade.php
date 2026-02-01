<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contrat Crédit</title>
</head>
<body>
    <h1>Contrat de Crédit - {{ $credit->reference }}</h1>
    <p>Client: {{ $credit->client->nom }} {{ $credit->client->prenom }}</p>
    <p>Montant: {{ number_format($credit->montant, 2) }} FCFA</p>
    <p>Durée: {{ $credit->duree }} mois</p>
    <p>Produit: {{ $credit->product->nom }}</p>
    <p>Taux: {{ $credit->product->taux_interet ?? 0 }} %</p>
    <p>Frais d’étude: {{ $credit->product->frais_etude ?? 0 }} %</p>
    <p>Frais de mise en place: {{ $credit->product->frais_mise_en_place ?? 0 }} %</p>
    <p>Conditions acceptées par le client: {{ $credit->statut === 'ACCEPTE' ? 'Oui' : 'Non' }}</p>
</body>
</html>
