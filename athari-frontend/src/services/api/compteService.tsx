// Service mock pour les comptes (à remplacer par des appels API réels)
const ACCOUNT_TYPES = [
  { code: '01', label: 'Compte collecte journalière Z1', number: '37224000' },
  { code: '02', label: 'Compte collecte journalière Z2', number: '37224001' },
  { code: '03', label: 'Compte collecte journalière Z3', number: '37224002' },
  { code: '04', label: 'Compte collecte journalière Z4', number: '37224003' },
  { code: '05', label: 'Compte collecte journalière Z5', number: '37224004' },
  { code: '06', label: 'Compte collecte journalière Z6', number: '37224005' },
  { code: '07', label: 'Epargne journalière bloquée 3 mois', number: '36100010' },
  { code: '08', label: 'Epargne journalière bloquée 6 mois', number: '36100020' },
  { code: '09', label: 'Epargne journalière bloquée 12 mois', number: '36100040' },
  { code: '10', label: 'Compte courant particulier', number: '37122000' },
  { code: '11', label: 'Compte courant entreprise', number: '37123000' },
  { code: '12', label: 'Compte épargne participative', number: '37321000' },
  { code: '13', label: 'Compte courant association', number: '37124000' },
  { code: '14', label: 'Compte courant islamique', number: '37125000' },
  { code: '15', label: 'Epargne Young', number: '37323000' },
  { code: '16', label: 'Epargne classique', number: '37324000' },
  { code: '17', label: 'DAT', number: '36100000' },
  { code: '18', label: 'DAT Solidaire', number: '' },
  { code: '19', label: 'Compte salaire', number: '37121000' },
  { code: '20', label: 'Compte épargne islamique', number: '37322000' },
  { code: '21', label: 'Compte épargne association', number: '37320000' },
  { code: '22', label: 'Compte mata boost bloqué', number: '37226000' },
  { code: '23', label: 'Compte mata journalier', number: '37225000' },
];

const AGENCIES = [
  { code: '001', name: 'ATHARI RÉUSSITE' },
  { code: '002', name: 'ATHARI AUDACE' },
  { code: '003', name: 'ATHARI SPEED' },
  { code: '004', name: 'ATHARI POWER' },
  { code: '005', name: 'ATHARI IMANI' },
];

// Données mock pour le développement
const mockAccounts = [
  {
    id: '1',
    accountNumber: '001-000001',
    clientNumber: '001000001',
    clientName: 'Jean Dupont',
    accountType: '10',
    agency: '001',
    balance: 1500000,
    currency: 'XAF',
    status: 'active',
    createdAt: '2024-01-15',
    minimumBalance: 50000,
    commissionRate: 0.02,
  },
  {
    id: '2',
    accountNumber: '001-000002',
    clientNumber: '001000002',
    clientName: 'Marie Curie',
    accountType: '23',
    agency: '001',
    balance: 750000,
    currency: 'XAF',
    status: 'active',
    createdAt: '2024-01-16',
    minimumBalance: 0,
    commissionRate: 0.01,
  },
  {
    id: '3',
    accountNumber: '002-000001',
    clientNumber: '002000001',
    clientName: 'SARL Tech Solutions',
    accountType: '11',
    agency: '002',
    balance: 5000000,
    currency: 'XAF',
    status: 'blocked',
    createdAt: '2024-01-10',
    minimumBalance: 100000,
    commissionRate: 0.03,
  },
];

const accountService = {
  // Récupérer tous les comptes
  getAllAccounts: async () => {
    // Simulation d'appel API
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(mockAccounts);
      }, 500);
    });
  },

  // Récupérer un compte par ID
  getAccountById: async (id) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        const account = mockAccounts.find(acc => acc.id === id);
        resolve(account || null);
      }, 300);
    });
  },

  // Créer un nouveau compte
  createAccount: async (accountData) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newAccount = {
          id: String(mockAccounts.length + 1),
          accountNumber: `${accountData.agency}-${String(mockAccounts.length + 1).padStart(6, '0')}`,
          ...accountData,
          createdAt: new Date().toISOString().split('T')[0],
          status: 'active',
        };
        mockAccounts.push(newAccount);
        resolve(newAccount);
      }, 500);
    });
  },

  // Mettre à jour un compte
  updateAccount: async (id, accountData) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        const index = mockAccounts.findIndex(acc => acc.id === id);
        if (index !== -1) {
          mockAccounts[index] = { ...mockAccounts[index], ...accountData };
          resolve(mockAccounts[index]);
        } else {
          resolve(null);
        }
      }, 500);
    });
  },

  // Supprimer un compte (soft delete)
  deleteAccount: async (id) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        const index = mockAccounts.findIndex(acc => acc.id === id);
        if (index !== -1) {
          mockAccounts[index].status = 'deleted';
          resolve(true);
        } else {
          resolve(false);
        }
      }, 500);
    });
  },

  // Obtenir les types de comptes
  getAccountTypes: () => {
    return ACCOUNT_TYPES;
  },

  // Obtenir les agences
  getAgencies: () => {
    return AGENCIES;
  },

  // Filtrer les comptes
  filterAccounts: async (filters) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        let filtered = [...mockAccounts];
        
        if (filters.status) {
          filtered = filtered.filter(acc => acc.status === filters.status);
        }
        
        if (filters.accountType) {
          filtered = filtered.filter(acc => acc.accountType === filters.accountType);
        }
        
        if (filters.agency) {
          filtered = filtered.filter(acc => acc.agency === filters.agency);
        }
        
        if (filters.search) {
          const searchLower = filters.search.toLowerCase();
          filtered = filtered.filter(acc => 
            acc.accountNumber.toLowerCase().includes(searchLower) ||
            acc.clientName.toLowerCase().includes(searchLower) ||
            acc.clientNumber.toLowerCase().includes(searchLower)
          );
        }
        
        resolve(filtered);
      }, 300);
    });
  },
};

export default accountService;