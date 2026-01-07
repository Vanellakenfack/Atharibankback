<div class="header-container">
    {{-- Partie Gauche : Infos Institution --}}
    <div style="float: left; width: 30%;">
        <div style="font-weight: bold; font-size: 12px;">ATHARI BANK</div>
        <div>Direction de l'Exploitation</div>
        <div>Service Opérations</div>
    </div>

    {{-- Partie Centre : Titre du Document --}}
    <div style="float: left; width: 40%; text-align: center;">
        <h2 style="margin-bottom: 5px; text-decoration: underline;">JOURNAL DES OUVERTURES</h2>
        <div style="font-size: 10px;">
            Période du **{{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}** au **{{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}**
        </div>
    </div>

    {{-- Partie Droite : Infos Agence et Impression --}}
    <div style="float: right; width: 30%; text-align: right;">
        <div><strong>Agence :</strong> {{ $code_agence ?? 'Toutes' }}</div>
        <div><strong>Date édition :</strong> {{ now()->format('d/m/Y H:i') }}</div>
        <div><strong>Page :</strong> <span class="pagenum"></span></div>
    </div>
    
    <div style="clear: both;"></div>
</div>

<hr style="border: 0.5px solid #000; margin-top: 10px; margin-bottom: 20px;">
@php
    // Groupement par Chapitre (Type de compte)
    $chapitres = $donnees->groupBy(function($compte) {
        return $compte->typeCompte->libelle ?? 'DIVERS';
    });
    
    $grandTotalComptes = 0;
    $grandTotalMontant = 0;
@endphp

@foreach($chapitres as $nomChapitre => $comptes)

    <div style="background: #f0f0f0; padding: 5px; margin-top: 15px; font-weight: bold; border: 1px solid #000;">
        CHAPITRE : {{ strtoupper($nomChapitre) }}
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Date Ouv.</th>
                <th style="width: 15%;">Num. Client</th>
                <th style="width: 30%;">Nom du Client</th>
                <th style="width: 20%;">Numéro Compte</th>
                <th style="width: 20%;">Montant Initial</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comptes as $compte)
                @php
                    // On récupère le montant du dépôt initial s'il existe, sinon 0
                    $mvtInitial = $compte->mouvements->first();
                    $montant = $mvtInitial ? $mvtInitial->montant_debit : 0;
                    
                    $grandTotalComptes++;
                    $grandTotalMontant += $montant;
                @endphp
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($compte->date_ouverture)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $compte->client->num_client ?? 'N/A' }}</td>
                    <td>{{ $compte->client->nom_complet }} </td>
                    <td class="text-center">{{ $compte->numero_compte }}</td>
                    <td class="text-right">
                        {{ $montant > 0 ? number_format($montant, 0, '.', ' ') : '0' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- Synthèse en bas de page --}}
<div style="margin-top: 20px; width: 40%; float: right; border: 1px solid #000;">
    <div style="background: #333; color: #fff; padding: 5px; text-align: center; font-weight: bold;">
        RÉCAPITULATIF GÉNÉRAL
    </div>
    <div style="padding: 8px;">
        Nombre total de comptes : <strong>{{ $grandTotalComptes }}</strong><br>
        Volume total des dépôts : <strong>{{ number_format($grandTotalMontant, 0, '.', ' ') }} FCFA</strong>
    </div>
</div>