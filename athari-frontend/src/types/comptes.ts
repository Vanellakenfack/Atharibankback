// Types d'énumération
export type TypeDeCompte = 
  | 'courant'
  | 'epargne'
  | 'bloque'
  | 'mata_boost'
  | 'collecte_journaliere'
  | 'salaire'
  | 'islamique'
  | 'association'
  | 'entreprise';

export type StatusDuCompte = 
  | 'active'
  | 'blocked'
  | 'closed'
  | 'pending'
  | 'suspended';

export type Currency = 'XAF' | 'EUR' | 'USD';

export type CategoriesDuCompte = 
  | 'particulier'
  | 'entreprise'
  | 'association'
  | 'gouvernemental';

// Interface principale du compte
export interface Compte {
  id: string;
  accountNumber: string;
  clientId: string;
  clientName: string;
  type: TypeDeCompte;
  status: StatusDuCompte;
  balance: number;
  availableBalance: number;
  currency: Currency;
  
  // Informations spécifiques
  interestRate?: number;
  monthlyFees?: number;
  minimumBalance?: number;
  withdrawalLimit?: number;
  dailyWithdrawalLimit?: number;
  
  // Dates importantes
  openingDate: string;
  lastActivityDate?: string;
  maturityDate?: string;
  
  // Métadonnées
  branchId: string;
  branchName: string;
  managerId?: string;
  managerName?: string;
  createdBy: string;
  createdAt: string;
  updatedAt: string;
  
  // Sous-comptes pour MATA Boost
  subAccounts?: {
    business: number;
    education: number;
    health: number;
    celebration: number;
    supplies: number;
    realEstate: number;
  };
  
  // Restrictions
  restrictions?: {
    noDebit: boolean;
    noCredit: boolean;
    noTransfer: boolean;
    reason?: string;
  };
  
  // Paramètres spécifiques
  parameters?: {
    commissionRate: number;
    smsAlert: boolean;
    emailAlert: boolean;
    autoRenew: boolean;
  };
}

// Interface pour la création d'un compte
export interface CreationCompte {
  clientId: string;
  clientName: string;
  type: TypeDeCompte;
  currency: Currency;
  initialBalance: number;
  category: CategoriesDuCompte;
  
  // Paramètres optionnels
  interestRate?: number;
  monthlyFees?: number;
  minimumBalance?: number;
  withdrawalLimit?: number;
  
  // MATA Boost spécifique
  subAccounts?: {
    business: number;
    education: number;
    health: number;
    celebration: number;
    supplies: number;
    realEstate: number;
  };
}

// Interface pour la mise à jour d'un compte
export interface ModificationCompte {
  id: string;
  status?: StatusDuCompte;
  monthlyFees?: number;
  withdrawalLimit?: number;
  interestRate?: number;
  restrictions?: {
    noDebit?: boolean;
    noCredit?: boolean;
    noTransfer?: boolean;
    reason?: string;
  };
}

// Interface pour les filtres
export interface FlitrageCompte {
  type?: TypeDeCompte[];
  status?: StatusDuCompte[];
  branchId?: string;
  minBalance?: number;
  maxBalance?: number;
  startDate?: string;
  endDate?: string;
  searchTerm?: string;
}

// Interface pour la pagination
export interface PaginationCompte {
  data: Compte[];
  pagination: {
    currentPage: number;
    totalPages: number;
    totalItems: number;
    itemsPerPage: number;
  };
}

// Interface pour les statistiques
export interface StatistiquesCompte {
  totalAccounts: number;
  totalBalance: number;
  byType: Record<TypeDeCompte, { count: number; balance: number }>;
  byStatus: Record<StatusDuCompte, number>;
  dailyAverage: number;
  monthlyGrowth: number;
}