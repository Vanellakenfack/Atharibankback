<?php

namespace App\Services;

use App\Models\OperationDiverse;
use App\Models\OdHistorique;
use App\Models\OdSignature;
use App\Models\MouvementComptable;
use App\Models\User;
use App\Models\PlanComptable;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OperationDiverseService
{
    /**
     * Créer une nouvelle OD
     */
    public function create(array $data, ?User $user = null, $justificatifFile = null): OperationDiverse
    {
        DB::beginTransaction();
        
        try {
            $user = $user ?? auth()->user();
            
            $od = OperationDiverse::create([
                'agence_id' => $data['agence_id'],
                'date_operation' => $data['date_operation'],
                'date_valeur' => $data['date_valeur'] ?? $data['date_operation'],
                'type_operation' => $data['type_operation'],
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'montant' => $data['montant'],
                'devise' => $data['devise'],
                'compte_debit_id' => $data['compte_debit_id'],
                'compte_credit_id' => $data['compte_credit_id'],
                'compte_client_debiteur_id' => $data['compte_client_debiteur_id'] ?? null,
                'compte_client_crediteur_id' => $data['compte_client_crediteur_id'] ?? null,
                'saisi_par' => $user->id,
                'statut' => 'BROUILLON',
                'justificatif_type' => $data['justificatif_type'] ?? null,
                'justificatif_numero' => $data['justificatif_numero'] ?? null,
                'justificatif_date' => $data['justificatif_date'] ?? null,
                'reference_client' => $data['reference_client'] ?? null,
                'nom_tiers' => $data['nom_tiers'] ?? null,
                'est_urgence' => $data['est_urgence'] ?? false,
            ]);

            // Upload du justificatif
            if ($justificatifFile) {
                $this->uploadJustificatif($od, $justificatifFile);
            }

            // Historique
            $this->enregistrerHistorique($od, 'CREATION', null, 'BROUILLON', $user);

            DB::commit();
            return $od->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour une OD
     */
    public function update(OperationDiverse $od, array $data, ?User $user = null): OperationDiverse
    {
        DB::beginTransaction();
        
        try {
            $user = $user ?? auth()->user();
            $ancienStatut = $od->statut;

            // Vérifier si l'OD peut être modifiée
            if (!$this->peutEtreModifiee($od)) {
                throw new \Exception("Cette OD ne peut pas être modifiée dans son état actuel.");
            }

            $od->update($data);

            // Historique
            $this->enregistrerHistorique($od, 'MODIFICATION', $ancienStatut, $od->statut, $user);

            DB::commit();
            return $od->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Valider une OD
     */
    public function valider(OperationDiverse $od, User $validateur, ?string $commentaire = null): bool
    {
        if (!$od->peutEtreValidee()) {
            throw new \Exception("Cette OD ne peut pas être validée.");
        }

        DB::beginTransaction();
        
        try {
            $ancienStatut = $od->statut;
            
            $od->update([
                'statut' => 'VALIDE',
                'valide_par' => $validateur->id,
                'date_validation' => now(),
            ]);

            // Historique
            $this->enregistrerHistorique($od, 'VALIDATION', $ancienStatut, 'VALIDE', $validateur);

            // Signature
            $this->creerSignature($od, $validateur, 'APPROUVE', $commentaire);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Rejeter une OD
     */
    public function rejeter(OperationDiverse $od, User $rejeteur, string $motif): bool
    {
        if (!in_array($od->statut, ['BROUILLON', 'SAISI'])) {
            throw new \Exception("Cette OD ne peut pas être rejetée.");
        }

        DB::beginTransaction();
        
        try {
            $ancienStatut = $od->statut;
            
            $od->update([
                'statut' => 'REJETE',
                'motif_rejet' => $motif,
            ]);

            // Historique
            $this->enregistrerHistorique($od, 'REJET', $ancienStatut, 'REJETE', $rejeteur);

            // Signature de rejet
            $this->creerSignature($od, $rejeteur, 'REJETE', $motif);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Comptabiliser une OD
     */
    public function comptabiliser(OperationDiverse $od, User $comptable): bool
    {
        if ($od->statut !== 'VALIDE' || $od->est_comptabilise) {
            throw new \Exception("Cette OD ne peut pas être comptabilisée.");
        }

        DB::beginTransaction();
        
        try {
            // Vérifier les comptes
            $compteDebit = PlanComptable::find($od->compte_debit_id);
            $compteCredit = PlanComptable::find($od->compte_credit_id);
            
            if (!$compteDebit || !$compteCredit) {
                throw new \Exception("Comptes débiteur ou créditeur invalides.");
            }

            // Créer le mouvement comptable
            MouvementComptable::create([
                'date_mouvement' => $od->date_operation,
                'date_valeur' => $od->date_valeur ?? $od->date_operation,
                'libelle_mouvement' => "OD {$od->numero_od}: {$od->libelle}",
                'description' => $od->description,
                'compte_debit_id' => $od->compte_debit_id,
                'compte_credit_id' => $od->compte_credit_id,
                'montant_debit' => $od->montant,
                'montant_credit' => $od->montant,
                'journal' => $this->determinerJournal($od->type_operation),
                'numero_piece' => $od->numero_piece ?? $od->numero_od,
                'reference_operation' => $od->numero_od,
                'statut' => 'COMPTABILISE',
                'est_pointage' => false,
                'created_by' => $comptable->id,
            ]);

            // Mettre à jour l'OD
            $od->update([
                'est_comptabilise' => true,
                'comptabilise_par' => $comptable->id,
                'date_comptabilisation' => now(),
            ]);

            // Historique
            $this->enregistrerHistorique($od, 'COMPTABILISATION', 'VALIDE', 'VALIDE', $comptable);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Annuler une OD
     */
    public function annuler(OperationDiverse $od, User $annulateur, string $motif): bool
    {
        if ($od->est_comptabilise) {
            throw new \Exception("Impossible d'annuler une OD déjà comptabilisée.");
        }

        DB::beginTransaction();
        
        try {
            $ancienStatut = $od->statut;
            
            $od->update([
                'statut' => 'ANNULE',
                'motif_rejet' => $motif,
            ]);

            // Historique
            $this->enregistrerHistorique($od, 'ANNULATION', $ancienStatut, 'ANNULE', $annulateur);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Uploader un justificatif
     */
    public function uploadJustificatif(OperationDiverse $od, $file, ?string $type = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = "justificatif_" . Str::uuid() . "." . $extension;
        
        $path = $file->storeAs(
            "justificatifs/od/{$od->id}",
            $fileName,
            'public'
        );

        $od->update([
            'justificatif_path' => $path,
            'justificatif_type' => $type ?? $od->justificatif_type,
        ]);

        return $path;
    }

    /**
     * Récupérer les OD en attente de validation
     */
    public function getEnAttenteValidation(?int $agenceId = null)
    {
        $query = OperationDiverse::with(['agence', 'saisiPar'])
            ->whereIn('statut', ['BROUILLON', 'SAISI'])
            ->orderBy('created_at', 'desc');

        if ($agenceId) {
            $query->where('agence_id', $agenceId);
        }

        return $query->get();
    }

    /**
     * Récupérer les OD à comptabiliser
     */
    public function getAComptabiliser(?int $agenceId = null)
    {
        $query = OperationDiverse::with(['agence', 'validePar', 'compteDebit', 'compteCredit'])
            ->where('statut', 'VALIDE')
            ->where('est_comptabilise', false)
            ->orderBy('date_validation', 'asc');

        if ($agenceId) {
            $query->where('agence_id', $agenceId);
        }

        return $query->get();
    }

    /**
     * Recherche avancée des OD
     */
    public function rechercheAvancee(array $filters)
    {
        $query = OperationDiverse::with([
            'agence', 
            'saisiPar', 
            'validePar', 
            'compteDebit', 
            'compteCredit'
        ]);

        // Filtres
        if (!empty($filters['agence_id'])) {
            $query->where('agence_id', $filters['agence_id']);
        }

        if (!empty($filters['date_debut'])) {
            $query->where('date_operation', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->where('date_operation', '<=', $filters['date_fin']);
        }

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['type_operation'])) {
            $query->where('type_operation', $filters['type_operation']);
        }

        if (!empty($filters['montant_min'])) {
            $query->where('montant', '>=', $filters['montant_min']);
        }

        if (!empty($filters['montant_max'])) {
            $query->where('montant', '<=', $filters['montant_max']);
        }

        if (!empty($filters['saisi_par'])) {
            $query->where('saisi_par', $filters['saisi_par']);
        }

        if (!empty($filters['valide_par'])) {
            $query->where('valide_par', $filters['valide_par']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('numero_od', 'like', "%{$search}%")
                  ->orWhere('libelle', 'like', "%{$search}%")
                  ->orWhere('nom_tiers', 'like', "%{$search}%")
                  ->orWhere('reference_client', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $filters['sort'] ?? 'date_operation';
        $sortDirection = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Statistiques des OD
     */
    public function getStatistiques(array $params = [])
    {
        $query = OperationDiverse::query();

        // Filtres par période
        if (!empty($params['date_debut'])) {
            $query->where('date_operation', '>=', $params['date_debut']);
        }
        
        if (!empty($params['date_fin'])) {
            $query->where('date_operation', '<=', $params['date_fin']);
        }

        // Filtre par agence
        if (!empty($params['agence_id'])) {
            $query->where('agence_id', $params['agence_id']);
        }

        // Statistiques par statut
        $parStatut = $query->selectRaw('statut, COUNT(*) as count, SUM(montant) as total')
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        // Statistiques par type d'opération
        $parType = $query->selectRaw('type_operation, COUNT(*) as count, SUM(montant) as total')
            ->groupBy('type_operation')
            ->get()
            ->keyBy('type_operation');

        // Total général
        $totalGeneral = $query->sum('montant');
        $countGeneral = $query->count();

        // OD en urgence
        $urgences = $query->where('est_urgence', true)->count();

        return [
            'total_general' => $totalGeneral,
            'nombre_total' => $countGeneral,
            'par_statut' => $parStatut,
            'par_type' => $parType,
            'urgences' => $urgences,
            'periode' => [
                'debut' => $params['date_debut'] ?? null,
                'fin' => $params['date_fin'] ?? null,
            ],
        ];
    }

    /**
     * Exporter les OD
     */
    public function export(array $filters): array
    {
        $query = OperationDiverse::with([
            'agence', 
            'saisiPar', 
            'validePar', 
            'compteDebit', 
            'compteCredit'
        ]);

        // Appliquer les filtres
        if (!empty($filters)) {
            $query = $this->appliquerFiltres($query, $filters);
        }

        $ods = $query->get();

        // Formater les données pour l'export
        $exportData = $ods->map(function ($od) {
            return [
                'Numéro OD' => $od->numero_od,
                'Date opération' => $od->date_operation->format('d/m/Y'),
                'Date valeur' => $od->date_valeur ? $od->date_valeur->format('d/m/Y') : '',
                'Type' => $od->type_operation,
                'Libellé' => $od->libelle,
                'Description' => $od->description,
                'Montant' => number_format($od->montant, 2, ',', ' ') . ' ' . $od->devise,
                'Compte débit' => $od->compteDebit ? $od->compteDebit->code . ' - ' . $od->compteDebit->libelle : '',
                'Compte crédit' => $od->compteCredit ? $od->compteCredit->code . ' - ' . $od->compteCredit->libelle : '',
                'Statut' => $this->traduireStatut($od->statut),
                'Urgence' => $od->est_urgence ? 'Oui' : 'Non',
                'Saisi par' => $od->saisiPar ? $od->saisiPar->name : '',
                'Validé par' => $od->validePar ? $od->validePar->name : '',
                'Date validation' => $od->date_validation ? Carbon::parse($od->date_validation)->format('d/m/Y H:i') : '',
                'Comptabilisé' => $od->est_comptabilise ? 'Oui' : 'Non',
                'Agence' => $od->agence ? $od->agence->nom : '',
            ];
        });

        return [
            'data' => $exportData,
            'total' => $ods->sum('montant'),
            'count' => $ods->count(),
            'date_export' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Enregistrer un historique
     */
    private function enregistrerHistorique(
        OperationDiverse $od, 
        string $action, 
        ?string $ancienStatut, 
        ?string $nouveauStatut,
        User $user
    ): void {
        $od->historique()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'description' => $this->getDescriptionHistorique($od, $action),
            'donnees_modifiees' => $od->getDirty(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Créer une signature
     */
    private function creerSignature(
        OperationDiverse $od, 
        User $validateur, 
        string $decision, 
        ?string $commentaire = null
    ): OdSignature {
        return $od->signatures()->create([
            'user_id' => $validateur->id,
            'niveau_validation' => 1,
            'role_validation' => $validateur->roles->first()->name ?? 'Validateur',
            'decision' => $decision,
            'commentaire' => $commentaire,
            'signature_date' => now(),
        ]);
    }

    /**
     * Vérifier si une OD peut être modifiée
     */
    private function peutEtreModifiee(OperationDiverse $od): bool
    {
        return in_array($od->statut, ['BROUILLON', 'SAISI', 'REJETE']);
    }

    /**
     * Déterminer le journal comptable
     */
    private function determinerJournal(string $typeOperation): string
    {
        $journaux = [
            'DEPOT' => 'CAISSE',
            'RETRAIT' => 'BANQUE',
            'VIREMENT' => 'VIREMENT',
            'FRAIS' => 'FRAIS',
            'COMMISSION' => 'COMMISSION',
            'REGULARISATION' => 'DIVERS',
            'AUTRE' => 'DIVERS',
        ];

        return $journaux[$typeOperation] ?? 'DIVERS';
    }

    /**
     * Traduire le statut
     */
    private function traduireStatut(string $statut): string
    {
        $traductions = [
            'BROUILLON' => 'Brouillon',
            'SAISI' => 'Saisi',
            'VALIDE' => 'Validé',
            'REJETE' => 'Rejeté',
            'ANNULE' => 'Annulé',
            'COMPTABILISE' => 'Comptabilisé',
        ];

        return $traductions[$statut] ?? $statut;
    }

    /**
     * Description pour l'historique
     */
    private function getDescriptionHistorique(OperationDiverse $od, string $action): string
    {
        switch ($action) {
            case 'CREATION':
                return "Création de l'OD {$od->numero_od}";
            case 'VALIDATION':
                return "Validation de l'OD {$od->numero_od}";
            case 'COMPTABILISATION':
                return "Comptabilisation de l'OD {$od->numero_od}";
            case 'REJET':
                return "Rejet de l'OD {$od->numero_od}";
            case 'ANNULATION':
                return "Annulation de l'OD {$od->numero_od}";
            default:
                return "Modification de l'OD {$od->numero_od}";
        }
    }

    /**
     * Appliquer les filtres pour l'export
     */
    private function appliquerFiltres($query, array $filters)
    {
        if (!empty($filters['agence_id'])) {
            $query->where('agence_id', $filters['agence_id']);
        }

        if (!empty($filters['date_debut'])) {
            $query->where('date_operation', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->where('date_operation', '<=', $filters['date_fin']);
        }

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['type_operation'])) {
            $query->where('type_operation', $filters['type_operation']);
        }

        if (!empty($filters['est_urgence'])) {
            $query->where('est_urgence', filter_var($filters['est_urgence'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['est_comptabilise'])) {
            $query->where('est_comptabilise', filter_var($filters['est_comptabilise'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }
}