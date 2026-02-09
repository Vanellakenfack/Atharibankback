<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\AvisCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CreditReviewController extends Controller
{
    /**
     * ÉTAPE 1 : Pré-avis (Analyse technique avant Comité)
     * Rôle : Assistant Comptable & Chef d'Agence
     */
    public function donnerPreAvis(Request $request, $id)
    {
        $request->validate([
            'opinion' => 'required|in:FAVORABLE,DEFAVORABLE',
            'commentaire' => 'nullable|string',
        ]);

        $application = CreditApplication::findOrFail($id);
        $user = Auth::user();

        if (!in_array($user->name, ['Chef d\'Agence', 'Assistant Comptable'])) {
            return response()->json(['message' => 'Seul le Chef d\'Agence ou l\'Assistant Comptable peuvent donner un pré-avis.'], 403);
        }

        return DB::transaction(function () use ($request, $application, $user) {
            AvisCredit::updateOrCreate(
                [
                    'credit_application_id' => $application->id,
                    'user_id' => $user->id,
                    'niveau_avis' => 'ANALYSE'
                ],
                [
                    'role' => $user->name,
                    'opinion' => $request->opinion,
                    'commentaire' => $request->commentaire,
                    'date_avis' => now(),
                ]
            );

            // Si les 2 acteurs ont donné leur pré-avis, le dossier passe en Comité
            $nbAvisAnalyse = AvisCredit::where('credit_application_id', $application->id)
                ->where('niveau_avis', 'ANALYSE')
                ->count();

            if ($nbAvisAnalyse >= 2) {
                $application->update(['statut' => 'EN_COMITE']);
            }

            return response()->json([
                'message' => 'Pré-avis enregistré avec succès.',
                'statut_dossier' => $application->statut
            ]);
        });
    }

    /**
     * ÉTAPE 2 : Vote en Comité & Génération du PV
     * Rôle : Membres du comité + Vote final du Chef d'Agence
     */
    public function voterComite(Request $request, $id)
{
    $request->validate([
        'opinion' => 'required|in:FAVORABLE,DEFAVORABLE',
        'commentaire' => 'nullable|string',
        'montant_accorde' => 'required_if:opinion,FAVORABLE|numeric',
        'date_deblocage' => 'required_if:opinion,FAVORABLE|date',
    ]);

    $application = CreditApplication::findOrFail($id);
    $user = Auth::user();

    // 1. SÉCURITÉ : Vérifier si l'utilisateur a déjà voté
    $dejaVote = AvisCredit::where('credit_application_id', $id)
        ->where('user_id', $user->id)
        ->where('niveau_avis', 'COMITE')
        ->exists();

    if ($dejaVote) {
        return response()->json(['message' => 'Vous avez déjà enregistré votre vote pour ce dossier.'], 403);
    }

    // 2. SÉCURITÉ : Vérifier si le dossier n'est pas déjà clôturé
    if (in_array($application->statut, ['APPROUVE', 'REJETE'])) {
        return response()->json(['message' => 'Ce dossier est déjà clôturé (Approuvé ou Rejeté).'], 422);
    }

    if ($application->statut !== 'EN_COMITE') {
        return response()->json(['message' => 'Le dossier n\'est pas en phase comité.'], 422);
    }

    return DB::transaction(function () use ($request, $application, $user) {
        // Enregistrement du vote
        AvisCredit::create([
            'credit_application_id' => $application->id,
            'user_id' => $user->id,
            'niveau_avis' => 'COMITE',
            'role' => $user->name,
            'opinion' => $request->opinion,
            'commentaire' => $request->commentaire,
            'date_avis' => now(),
        ]);

        // Logique de décision finale par le Chef d'Agence
        if ($user->name === 'Chef d\'Agence') {
            if ($request->opinion === 'FAVORABLE') {
                $dateDeblocage = \Carbon\Carbon::parse($request->date_deblocage);
                $duree = $application->duree_jours;
                $montant = $request->montant_accorde;

                $application->update([
                    'statut' => 'APPROUVE',
                    'montant_accorde' => $montant,
                    'date_deblocage' => $dateDeblocage,
                    'date_fin_remboursement' => $dateDeblocage->copy()->addDays($duree),
                    'nombre_echeances' => $duree,
                    // Calcul de la mensualité (remboursement journalier)
                    'mensualite' => $duree > 0 ? ($montant / $duree) : 0 
                ]);

            } else {
                $application->update(['statut' => 'REJETE']);
            }
        }

        return response()->json([
            'message' => 'Vote enregistré avec succès.',
            'statut_dossier' => $application->statut,
            'can_download' => in_array($application->statut, ['APPROUVE', 'REJETE'])
        ]);
    });
}
    /**
     * ÉTAPE 3 : Mise en place finale (Déblocage des fonds)
     * Rôle : Assistant Comptable (<=500k) ou Chef Comptable (>500k)
     */
public function finaliserMiseEnPlace(Request $request)
{
    // 1. PRÉ-TRAITEMENT : On tente de trouver l'ID technique si seul le numéro de compte est fourni
    if (!$request->compte_credit_id && $request->compte_credit) {
        $compteId = DB::table('comptes')
            ->where('numero_compte', $request->compte_credit)
            ->value('id');
            
        if ($compteId) {
            $request->merge(['compte_credit_id' => $compteId]);
        }
    }

    // 2. VALIDATION : On retire 'compte_credit_id' des champs obligatoires ici
    $request->validate([
        'application_id' => 'required',
        'montant'        => 'required|numeric|min:0',
    ]);

    try {
        return DB::transaction(function () use ($request) {
            
            // 3. Récupération des infos de la demande (Table : credit_applications)
            $application = DB::table('credit_applications')
                ->where('id', $request->application_id)
                ->first();

            if (!$application) {
                return response()->json(['status' => 'error', 'message' => 'Dossier introuvable'], 404);
            }

            // Sécurité : On vérifie si on a bien récupéré un ID compte avant d'insérer le mouvement
            $idCompteFinal = $request->compte_credit_id ?? $application->compte_id ?? null;

            if (!$idCompteFinal) {
                throw new \Exception("Impossible de déterminer l'ID du compte à créditer.");
            }

            $numDemande = $application->numero_demande ?? 'REF-' . $application->id;

            // 4. Insertion du mouvement comptable
            DB::table('mouvements_comptables')->insert([
                'compte_id'           => 10, 
                'date_mouvement'      => now(),
                'libelle_mouvement'   => "DÉCAISSEMENT CRÉDIT " . $numDemande,
                'compte_debit_id'     => 1287, 
                'compte_credit_id'    => 1287, 
                'montant_debit'       => $request->montant,
                'montant_credit'      => $request->montant,
                'journal'             => 'CREDIT',
                'reference_operation' => 'MISE-PLACE-' . $numDemande,
                'statut'              => 'COMPTABILISE',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // 5. Mise à jour du statut
            DB::table('credit_applications')
                ->where('id', $request->application_id)
                ->update([
                    'statut' => 'MIS_EN_PLACE',
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Mise en place effectuée avec succès'
            ]);
        });
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur : ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Fonction privée pour générer le PV PDF
     */
    /**
 * Fonction privée pour générer le PV PDF selon le template ATHARI FINANCIAL COOP
 */
// app/Http/Controllers/Api/CreditReviewController.php

public function downloadPV($id)
{
    try {
        // 1. Récupération de l'application avec les avis
        $application = CreditApplication::with(['avisCredits.user'])->findOrFail($id);

        // 2. Préparation des données client
        // On décode le champ 'client_info' s'il est stocké en JSON, 
        // ou on le récupère via une relation si vous en avez une.
        $clientData = is_string($application->client_info) 
            ? json_decode($application->client_info, true) 
            : $application->client_info;

        // 3. Filtrage des avis
        $avisComite = $application->avisCredits->where('niveau_avis', 'COMITE');
        $avisAnalyse = $application->avisCredits->where('niveau_avis', 'ANALYSE');

        $data = [
            'application'     => $application,
            'avisComite'      => $avisComite,
            'avisAnalyse'     => $avisAnalyse,
            'clientData'      => $clientData, // Variable indispensable pour votre template
            'date_generation' => now()->format('d/m/Y H:i'),
        ];

        // 4. Génération du PDF
        $pdf = Pdf::loadView('pdf.pv_comite', $data)
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'isHtml5ParserEnabled' => true, 
                      'isRemoteEnabled' => true,
                      'defaultFont' => 'DejaVu Sans' // Important pour les symboles comme FCFA
                  ]);

        return $pdf->download("PV_CREDIT_{$application->numero_demande}.pdf");

    } catch (\Exception $e) {
        // En cas d'erreur, on logue pour le débuggage
        \Log::error("Erreur PDF : " . $e->getMessage());
        return response()->json(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
    }
}
}