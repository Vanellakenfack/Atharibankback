import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import type { Compte, FlitrageCompte, PaginationCompte, StatistiquesCompte } from '../../types/comptes';

interface AccountState {
  accounts: Compte[];
  selectedAccount: Compte | null;
  isLoading: boolean;
  isSubmitting: boolean;
  error: string | null;
  filters: FlitrageCompte;
  pagination: {
    currentPage: number;
    totalPages: number;
    totalItems: number;
    itemsPerPage: number;
  };
  statistics: StatistiquesCompte | null;
}

const initialState: AccountState = {
  accounts: [],
  selectedAccount: null,
  isLoading: false,
  isSubmitting: false,
  error: null,
  filters: {},
  pagination: {
    currentPage: 1,
    totalPages: 0,
    totalItems: 0,
    itemsPerPage: 20,
  },
  statistics: null,
};

const accountSlice = createSlice({
  name: 'account',
  initialState,
  reducers: {
    // Actions synchrones
    setAccounts: (state, action: PayloadAction<Compte[]>) => {
      state.accounts = action.payload;
    },
    
    setSelectedAccount: (state, action: PayloadAction<Compte | null>) => {
      state.selectedAccount = action.payload;
    },
    
    setFilters: (state, action: PayloadAction<Partial<FlitrageCompte>>) => {
      state.filters = { ...state.filters, ...action.payload };
    },
    
    clearFilters: (state) => {
      state.filters = {};
    },
    
    setPagination: (state, action: PayloadAction<Partial<AccountState['pagination']>>) => {
      state.pagination = { ...state.pagination, ...action.payload };
    },
    
    setStatistics: (state, action: PayloadAction<StatistiquesCompte>) => {
      state.statistics = action.payload;
    },
    
    // Actions pour les états de chargement
    setLoading: (state, action: PayloadAction<boolean>) => {
      state.isLoading = action.payload;
    },
    
    setSubmitting: (state, action: PayloadAction<boolean>) => {
      state.isSubmitting = action.payload;
    },
    
    setError: (state, action: PayloadAction<string | null>) => {
      state.error = action.payload;
    },
    
    // Mise à jour optimiste
    addAccountOptimistic: (state, action: PayloadAction<Compte>) => {
      state.accounts.unshift(action.payload);
    },
    
    updateAccountOptimistic: (state, action: PayloadAction<Compte>) => {
      const index = state.accounts.findIndex(acc => acc.id === action.payload.id);
      if (index !== -1) {
        state.accounts[index] = action.payload;
      }
    },
    
    deleteAccountOptimistic: (state, action: PayloadAction<string>) => {
      state.accounts = state.accounts.filter(acc => acc.id !== action.payload);
    },
    
    // Réinitialisation
    resetAccountState: () => initialState,
  },
});

export const {
  setAccounts,
  setSelectedAccount,
  setFilters,
  clearFilters,
  setPagination,
  setStatistics,
  setLoading,
  setSubmitting,
  setError,
  addAccountOptimistic,
  updateAccountOptimistic,
  deleteAccountOptimistic,
  resetAccountState,
} = accountSlice.actions;

export default accountSlice.reducer;