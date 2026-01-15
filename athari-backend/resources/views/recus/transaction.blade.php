<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Caisse - {{ $transaction->reference_unique }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.4; color: #000; background: #fff; padding: 20px; }
        .receipt-container { max-width: 400px; margin: 0 auto; border: 1px solid #eee; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .details { margin-bottom: 15px; }
        .row { display: flex; justify-content: space-between; margin: 5px 0; }
        .amount { font-size: 18px; font-weight: bold; text-align: center; margin: 15px 0; border: 2px solid #000; padding: 10px; }
        .footer { margin-top: 30px; font-size: 11px; text-align: center; border-top: 1px dashed #000; padding-top: 10px; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; font-weight: bold; }
        .validation-box { font-size: 10px; border: 1px solid #ccc; padding: 8px; margin-top: 15px; background-color: #f9f9f9; }
        @media print { 
            .no-print { display: none; } 
            body { padding: 0; }
            .receipt-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">
            🖨️ IMPRIMER LE REÇU
        </button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <h2 style="margin: 0;">ATHARI BANK</h2>
            <p style="margin: 5px 0;">
                Agence: {{ $transaction->code_agence }}<br>
                Guichet: {{ $transaction->code_guichet }}
            </p>
            <h3 style="text-decoration: underline;">RECU DE {{ $transaction->type_flux }}</h3>
        </div>

        <div class="details">
            <div class="row"><span>Référence:</span> <strong>{{ $transaction->reference_unique }}</strong></div>
            <div class="row"><span>Date:</span> <span>{{ \Carbon\Carbon::parse($transaction->date_operation)->format('d/m/Y H:i') }}</span></div>
            <hr style="border: none; border-top: 1px solid #eee;">
            <div class="row"><span>Compte:</span> <strong>{{ $transaction->compte->numero_compte ?? 'N/A' }}</strong></div>
            <div class="row"><span>Titulaire:</span> <span>{{ $transaction->compte->client->nom_complet ?? 'Client Interne' }}</span></div>
            
            @if($transaction->tier)
                <div class="row"><span>Porteur (Tiers):</span> <span>{{ $transaction->tier->nom_complet }}</span></div>
                <div class="row"><span>Pièce ID:</span> <span>{{ $transaction->tier->type_piece }} - {{ $transaction->tier->numero_piece }}</span></div>
            @endif
        </div>

        <div class="amount">
            {{ number_format($transaction->montant_brut, 0, ',', ' ') }} FCFA
        </div>

        <div class="details">
            <p><i>Arrêté la présente somme à la valeur de :</i><br>
            <strong style="text-transform: uppercase;">
                @php
                    if (class_exists('NumberFormatter')) {
                        $f = new \NumberFormatter("fr", \NumberFormatter::SPELLOUT);
                        echo $f->format($transaction->montant_brut);
                    } else {
                        // Fallback manuel si l'extension INTL est absente
                        echo number_format($transaction->montant_brut, 0, ',', ' ');
                    }
                @endphp
                Francs CFA
            </strong></p>
        </div>

        @if($transaction->demandeValidation)
        <div class="validation-box">
            <strong>VÉRIFICATION SÉCURITÉ :</strong><br>
            Approuvé par : {{ $transaction->demandeValidation->assistant->name ?? 'SUP-CAISSE' }}<br>
            Code Auth : {{ $transaction->demandeValidation->code_validation }}
        </div>
        @endif

        <div class="signature">
            <span>Signature Client</span>
            <span>Le Caissier</span>
        </div>

        <div class="footer">
            <p>Caissier ID: {{ auth()->user()->name }}<br>
            <i>Merci de votre confiance. Athari Bank.</i></p>
        </div>
    </div>
</body>
</html>