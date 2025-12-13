export const mockUsers = [
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    roles: ['Admin'],
    status: 'active',
    createdAt: '2024-01-15',
    lastLogin: '2024-03-20 14:30:00'
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    roles: ['Chef Comptable'],
    status: 'active',
    createdAt: '2024-01-20',
    lastLogin: '2024-03-19 10:15:00'
  },
  {
    id: 3,
    name: 'Robert Johnson',
    email: 'robert@example.com',
    roles: ['Chef d\'Agence (CA)'],
    status: 'inactive',
    createdAt: '2024-02-01',
    lastLogin: '2024-03-18 09:45:00'
  },
  {
    id: 4,
    name: 'Sarah Wilson',
    email: 'sarah@example.com',
    roles: ['Caissière'],
    status: 'active',
    createdAt: '2024-02-10',
    lastLogin: '2024-03-20 16:20:00'
  },
  {
    id: 5,
    name: 'Michael Brown',
    email: 'michael@example.com',
    roles: ['Assistant Juridique (AJ)'],
    status: 'active',
    createdAt: '2024-02-15',
    lastLogin: '2024-03-19 11:30:00'
  }
];

export const mockRoles = [
  {
    name: 'DG',
    permissions: [
      'gerer utilisateurs',
      'gerer roles et permissions',
      'consulter logs',
      'ouvrir compte',
      'ouvrir collecteur',
      'ouvrir liason',
      'cloturer compte',
      'supprimer compte',
      'gestion agence',
      'saisir depot retrait',
      'valider operation caisse',
      'saisir od',
      'edition du journal des od',
      'edition du journal de caisse',
      'parametage plan comptable',
      'valider les od',
      'valider credit:500k',
      'valider credit:2m',
      'valider credit:gros',
      'generer etats financiers',
      'ouverture/fermeture caisse',
      'ouverture/fermeture guichet',
      'ouverture/fermeture agence',
      'gestion des clients',
      'gestion des gestionnaires'
    ]
  },
  {
    name: 'Chef Comptable',
    permissions: [
      'generer etats financiers',
      'saisir od',
      'valider credit:2m',
      'valider credit:500k',
      'gerer utilisateurs',
      'cloturer compte'
    ]
  },
  {
    name: 'Chef d\'Agence (CA)',
    permissions: [
      'ouvrir compte',
      'valider operation caisse',
      'valider credit:500k',
      'ouverture/fermeture guichet',
      'ouverture/fermeture agence',
      'valider les od'
    ]
  },
  {
    name: 'Assistant Juridique (AJ)',
    permissions: [
      'valider credit:2m',
      'valider credit:500k',
      'ouvrir compte',
      'consulter logs'
    ]
  },
  {
    name: 'Assistant Comptable (AC)',
    permissions: ['saisir od', 'generer etats financiers']
  },
  {
    name: 'Caissière',
    permissions: ['saisir depot retrait']
  },
  {
    name: 'Agent de Crédit (AC)',
    permissions: ['consulter logs']
  },
  {
    name: 'Collecteur',
    permissions: ['saisir depot retrait']
  },
  {
    name: 'Audit/Contrôle (IV)',
    permissions: ['consulter logs']
  },
  {
    name: 'Admin',
    permissions: [
      'gerer utilisateurs',
      'gerer roles et permissions',
      'consulter logs',
      'ouvrir compte',
      'ouvrir collecteur',
      'ouvrir liason',
      'cloturer compte',
      'supprimer compte',
      'gestion agence',
      'saisir depot retrait',
      'valider operation caisse',
      'saisir od',
      'edition du journal des od',
      'edition du journal de caisse',
      'parametage plan comptable',
      'valider les od',
      'valider credit:500k',
      'valider credit:2m',
      'valider credit:gros',
      'generer etats financiers',
      'ouverture/fermeture caisse',
      'ouverture/fermeture guichet',
      'ouverture/fermeture agence',
      'gestion des clients',
      'gestion des gestionnaires'
    ]
  }
];

export const allPermissions = [
  'gerer utilisateurs',
  'gerer roles et permissions',
  'consulter logs',
  'ouvrir compte',
  'ouvrir collecteur',
  'ouvrir liason',
  'cloturer compte',
  'supprimer compte',
  'gestion agence',
  'saisir depot retrait',
  'valider operation caisse',
  'saisir od',
  'edition du journal des od',
  'edition du journal de caisse',
  'parametage plan comptable',
  'valider les od',
  'valider credit:500k',
  'valider credit:2m',
  'valider credit:gros',
  'generer etats financiers',
  'ouverture/fermeture caisse',
  'ouverture/fermeture guichet',
  'ouverture/fermeture agence',
  'gestion des clients',
  'gestion des gestionnaires'
];

export const permissionCategories = {
  'ACL & Utilisateurs': [
    'gerer utilisateurs',
    'gerer roles et permissions',
    'consulter logs'
  ],
  'Comptes': [
    'ouvrir compte',
    'ouvrir collecteur',
    'ouvrir liason',
    'cloturer compte',
    'supprimer compte',
    'gestion agence'
  ],
  'Caisse & Trésorerie': [
    'saisir depot retrait',
    'valider operation caisse',
    'saisir od',
    'edition du journal des od',
    'edition du journal de caisse',
    'parametage plan comptable',
    'valider les od'
  ],
  'Crédit': [
    'valider credit:500k',
    'valider credit:2m',
    'valider credit:gros'
  ],
  'Reporting': ['generer etats financiers'],
  'Ouverture/Fermeture': [
    'ouverture/fermeture caisse',
    'ouverture/fermeture guichet',
    'ouverture/fermeture agence'
  ],
  'Gestion Clients': ['gestion des clients'],
  'Gestion Gestionnaires': ['gestion des gestionnaires']
};