import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../..//store';
import type { Compte, TypeDeCompte } from '../../types/comptes';

// Selecteurs de base
export const selectAccounts = (state: RootState) => state.account.accounts;
export const selectSelectedAccount = (state: RootState) => state.account.selectedAccount;
export const selectIsLoading = (state: RootState) => state.account.isLoading;
export const selectIsSubmitting = (state: RootState) => state.account.isSubmitting;
export const selectError = (state: RootState) => state.account.error;
export const selectFilters = (state: RootState) => state.account.filters;
export const selectPagination = (state: RootState) => state.account.pagination;
export const selectStatistics = (state: RootState) => state.account.statistics;

// Selecteurs dérivés avec memoization
export const selectFilteredAccounts = createSelector(
  [selectAccounts, selectFilters],
  (accounts, filters) => {
    return accounts.filter(account => {
      // Filtre par type
      if (filters.type?.length && !filters.type.includes(account.type)) {
        return false;
      }
      
      // Filtre par statut
      if (filters.status?.length && !filters.status.includes(account.status)) {
        return false;
      }
      
      // Filtre par agence
      if (filters.branchId && account.branchId !== filters.branchId) {
        return false;
      }
      
      // Filtre par solde minimum
      if (filters.minBalance && account.balance < filters.minBalance) {
        return false;
      }
      
      // Filtre par solde maximum
      if (filters.maxBalance && account.balance > filters.maxBalance) {
        return false;
      }
      
      // Filtre par recherche
      if (filters.searchTerm) {
        const searchTerm = filters.searchTerm.toLowerCase();
        const matchesSearch = 
          account.accountNumber.toLowerCase().includes(searchTerm) ||
          account.clientName.toLowerCase().includes(searchTerm) ||
          account.clientId.toLowerCase().includes(searchTerm);
        
        if (!matchesSearch) return false;
      }
      
      return true;
    });
  }
);

export const selectAccountsByType = createSelector(
  [selectAccounts],
  (accounts) => {
    return accounts.reduce((acc, account) => {
      if (!acc[account.type]) {
        acc[account.type] = [];
      }
      acc[account.type].push(account);
      return acc;
    }, {} as Record<TypeDeCompte, Compte[]>);
  }
);

export const selectTotalBalance = createSelector(
  [selectAccounts],
  (accounts) => {
    return accounts.reduce((total, account) => total + account.balance, 0);
  }
);

export const selectActiveAccounts = createSelector(
  [selectAccounts],
  (accounts) => {
    return accounts.filter(account => account.status === 'active');
  }
);

export const selectAccountsSummary = createSelector(
  [selectAccounts],
  (accounts) => {
    const total = accounts.length;
    const active = accounts.filter(acc => acc.status === 'active').length;
    const blocked = accounts.filter(acc => acc.status === 'blocked').length;
    const totalBalance = accounts.reduce((sum, acc) => sum + acc.balance, 0);
    
    return {
      total,
      active,
      blocked,
      totalBalance,
      averageBalance: total > 0 ? totalBalance / total : 0,
    };
  }
);