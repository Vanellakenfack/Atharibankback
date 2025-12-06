import { Account, AccountType } from '@/types/comptes';

type AccountStatus = 'active' | 'blocked' | 'closed' | 'pending' | 'suspended';

interface AccountSummary {
  age: string;
  formattedBalance: string;
  typeLabel: string;
  statusLabel: string;
  isActive: boolean;
  hasRestrictions: boolean;
}

export const formatAccountNumber = (accountNumber: string): string => {
  const cleaned = accountNumber.replace(/\D/g, '');
  if (cleaned.length === 11) {
    return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 9)}-${cleaned.slice(9)}`;
  }
  if (cleaned.length === 10) {
    return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 9)}-${cleaned.slice(9)}`;
  }
  return accountNumber;
};

export const formatBalance = (balance: number, currency: string): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(balance);
};

export const getAccountTypeLabel = (type: AccountType): string => {
  const labels: Record<AccountType, string> = {
    courant: 'Compte Courant',
    epargne: 'Compte Épargne',
    bloque: 'Compte Bloqué',
    mata_boost: 'MATA Boost',
    collecte_journaliere: 'Collecte Journalière',
    salaire: 'Compte Salaire',
    islamique: 'Compte Islamique',
    association: 'Compte Association',
    entreprise: 'Compte Entreprise',
  };
  return labels[type];
};

export const getStatusLabel = (status: AccountStatus): string => {
  const labels: Record<AccountStatus, string> = {
    active: 'Actif',
    blocked: 'Bloqué',
    closed: 'Fermé',
    pending: 'En attente',
    suspended: 'Suspendu',
  };
  return labels[status];
};

export const calculateAccountAge = (openingDate: string): string => {
  const opening = new Date(openingDate);
  
  if (isNaN(opening.getTime())) {
    return 'Date invalide';
  }
  
  const now = new Date();
  const diffMonths = 
    (now.getFullYear() - opening.getFullYear()) * 12 + 
    (now.getMonth() - opening.getMonth());
  
  if (diffMonths < 0) return 'Date future';
  if (diffMonths === 0) return "Moins d'un mois";
  if (diffMonths < 12) return `${diffMonths} mois`;
  
  const years = Math.floor(diffMonths / 12);
  const months = diffMonths % 12;
  
  const yearStr = `${years} an${years > 1 ? 's' : ''}`;
  return months === 0 ? yearStr : `${yearStr} et ${months} mois`;
};

export const generateAccountSummary = (account: Account): AccountSummary => {
  return {
    age: calculateAccountAge(account.openingDate),
    formattedBalance: formatBalance(account.balance, account.currency),
    typeLabel: getAccountTypeLabel(account.type),
    statusLabel: getStatusLabel(account.status as AccountStatus),
    isActive: account.status === 'active',
    hasRestrictions: Boolean(
      account.restrictions && 
      Object.values(account.restrictions).some(Boolean)
    ),
  };
};