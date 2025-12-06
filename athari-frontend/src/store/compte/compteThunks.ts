import { createAsyncThunk } from '@reduxjs/toolkit';
import type { Compte, CreationCompte, ModificationCompte, FlitrageCompte } from '../../types/comptes';
//import { accountApi } from '@/api/accountApi';
//import { AppDispatch, RootState } from '@/store';

// Thunks asynchrones
export const fetchAccounts = createAsyncThunk(
  'account/fetchAccounts',
  async (_, { dispatch, getState, rejectWithValue }) => {
    try {
      const state = getState() as RootState;
      const { filters, pagination } = state.account;
      
      const response = await accountApi.getAccounts({
        ...filters,
        page: pagination.currentPage,
        limit: pagination.itemsPerPage,
      });
      
      return response;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de chargement des comptes');
    }
  }
);

export const fetchAccountById = createAsyncThunk(
  'account/fetchAccountById',
  async (accountId: string, { rejectWithValue }) => {
    try {
      return await accountApi.getAccountById(accountId);
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de chargement du compte');
    }
  }
);

export const createAccount = createAsyncThunk(
  'account/createAccount',
  async (accountData: CreationCompte, { dispatch, rejectWithValue }) => {
    try {
      dispatch(setSubmitting(true));
      const response = await accountApi.createAccount(accountData);
      
      // Mise à jour optimiste
      dispatch(addAccountOptimistic(response));
      
      return response;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de création du compte');
    } finally {
      dispatch(setSubmitting(false));
    }
  }
);

export const updateAccount = createAsyncThunk(
  'account/updateAccount',
  async (updateData: ModificationCompte, { dispatch, rejectWithValue }) => {
    try {
      dispatch(setSubmitting(true));
      const response = await accountApi.updateAccount(updateData);
      
      // Mise à jour optimiste
      dispatch(updateAccountOptimistic(response));
      
      return response;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de mise à jour du compte');
    } finally {
      dispatch(setSubmitting(false));
    }
  }
);

export const deleteAccount = createAsyncThunk(
  'account/deleteAccount',
  async (accountId: string, { dispatch, rejectWithValue }) => {
    try {
      await accountApi.deleteAccount(accountId);
      
      // Mise à jour optimiste
      dispatch(deleteAccountOptimistic(accountId));
      
      return accountId;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de suppression du compte');
    }
  }
);

export const fetchAccountStatistics = createAsyncThunk(
  'account/fetchStatistics',
  async (_, { rejectWithValue }) => {
    try {
      return await accountApi.getStatistics();
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Erreur de chargement des statistiques');
    }
  }
);

// Import des actions depuis le slice
import {
  setSubmitting,
  addAccountOptimistic,
  updateAccountOptimistic,
  deleteAccountOptimistic,
} from './compteSlice';