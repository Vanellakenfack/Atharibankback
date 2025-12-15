<<<<<<< HEAD
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import accountService from '../../services/api/compteService';
import ConfirmDialog from '../common/ConfirnDialog';
import LoadingSpinner from '../common/LoadingSpinner';
=======
import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState, AppDispatch } from '../../store';
import { selectAccounts, selectIsLoading, selectError } from '../../store/compte/compteSelectors';
import { fetchAccounts } from '../../store/compte/compteThunks';
import { Eye, Edit2, Trash2, TrendingUp } from 'lucide-react';
>>>>>>> dev

const ListCompte: React.FC = () => {
  const dispatch = useDispatch<AppDispatch>();
  const comptes = useSelector((state: RootState) => selectAccounts(state));
  const loading = useSelector((state: RootState) => selectIsLoading(state));
  const error = useSelector((state: RootState) => selectError(state));

<<<<<<< HEAD
  useEffect(() => {
    loadAccounts();
  }, [filters]);

  const loadAccounts = async () => {
    setLoading(true);
    try {
      const data = await accountService.filterAccounts(filters);
      setAccounts(data);
    } catch (error) {
      console.error('Error loading accounts:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleView = (id) => {
    navigate(`/accounts/${id}`);
  };

  const handleEdit = (id) => {
    navigate(`/accounts/${id}/edit`);
  };

  const handleDeleteClick = (account) => {
    setAccountToDelete(account);
    setDeleteDialogOpen(true);
  };

  const handleDeleteConfirm = async () => {
    if (accountToDelete) {
      try {
        await accountService.deleteAccount(accountToDelete.id);
        await loadAccounts();
      } catch (error) {
        console.error('Error deleting account:', error);
      }
    }
    setDeleteDialogOpen(false);
    setAccountToDelete(null);
  };

  const getStatusStyle = (status) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'blocked': return 'bg-red-100 text-red-800';
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'closed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getAccountTypeLabel = (typeCode) => {
    const accountTypes = accountService.getAccountTypes();
    const type = accountTypes.find(t => t.code === typeCode);
    return type ? type.label : typeCode;
  };

  if (loading) return <LoadingSpinner />;

  if (accounts.length === 0) {
    return (
      <div className="text-center py-8">
        <svg className="mx-auto h-16 w-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h18v4H3zM3 11h18v10H3z"/></svg>
        <div className="mt-3 text-gray-600">Aucun compte trouvé</div>
      </div>
    );
  }

  return (
    <>
      <div className="overflow-x-auto bg-white border rounded">
        <table className="min-w-full divide-y">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-600">Numéro de compte</th>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-600">Client</th>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-600">Type de compte</th>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-600">Agence</th>
              <th className="px-4 py-3 text-right text-sm font-medium text-gray-600">Solde</th>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-600">Statut</th>
              <th className="px-4 py-3 text-center text-sm font-medium text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y">
            {accounts.map((account) => (
              <tr key={account.id}>
                <td className="px-4 py-3 align-top">
                  <div className="text-sm font-medium text-gray-900">{account.accountNumber}</div>
                  <div className="text-xs text-gray-500">{account.clientNumber}</div>
                </td>
                <td className="px-4 py-3 align-top">{account.clientName}</td>
                <td className="px-4 py-3 align-top">{getAccountTypeLabel(account.accountType)}</td>
                <td className="px-4 py-3 align-top">{account.agency}</td>
                <td className="px-4 py-3 text-right align-top">
                  <div className="text-sm font-medium">
                    {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: account.currency }).format(account.balance)}
                  </div>
                </td>
                <td className="px-4 py-3 align-top">
                  <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getStatusStyle(account.status)}`}>{account.status}</span>
                </td>
                <td className="px-4 py-3 text-center align-top">
                  <div className="inline-flex items-center gap-1">
                    <button title="Voir les détails" onClick={() => handleView(account.id)} className="p-1 rounded hover:bg-gray-100">
                      <svg className="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 11a4 4 0 110-8 4 4 0 010 8z"/></svg>
                    </button>
                    <button title="Modifier" onClick={() => handleEdit(account.id)} className="p-1 rounded hover:bg-gray-100">
                      <svg className="h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </button>
                    <button title="Supprimer" onClick={() => handleDeleteClick(account)} className="p-1 rounded hover:bg-gray-100">
                      <svg className="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M6 7h12v13a2 2 0 01-2 2H8a2 2 0 01-2-2V7zm3-4h6l1 1h3v2H2V4h3l1-1z"/></svg>
=======
  React.useEffect(() => {
    dispatch(fetchAccounts());
  }, [dispatch]);

  if (loading) {
    return <div className="p-8 text-center text-gray-500">Chargement...</div>;
  }

  if (error) {
    return <div className="p-8 text-red-600 bg-red-50 rounded-lg">Erreur: {error}</div>;
  }

  return (
    <div className="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="bg-gradient-to-r from-indigo-600 to-blue-600 border-b-4 border-indigo-700">
              <th className="px-8 py-5 text-left font-bold text-white">Numéro</th>
              <th className="px-8 py-5 text-left font-bold text-white">Type</th>
              <th className="px-8 py-5 text-left font-bold text-white">Solde</th>
              <th className="px-8 py-5 text-center font-bold text-white">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {comptes && comptes.length > 0 ? comptes.map((compte, idx) => (
              <tr 
                key={compte.id} 
                className={`transition-all duration-200 hover:shadow-md hover:bg-indigo-50/50 border-l-4 border-l-indigo-500 ${idx % 2 === 0 ? 'bg-gray-50/50' : 'bg-white'}`}
              >
                <td className="px-8 py-5 font-bold text-gray-900">{compte.numero}</td>
                <td className="px-8 py-5 text-gray-700 font-medium">
                  <div className="flex items-center gap-2">
                    <TrendingUp size={18} className="text-indigo-600" />
                    {compte.type}
                  </div>
                </td>
                <td className="px-8 py-5 font-bold text-lg">
                  <span className="bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
                    {compte.solde}
                  </span>
                </td>
                <td className="px-8 py-5">
                  <div className="flex justify-center items-center gap-2">
                    <button className="p-3 rounded-lg bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition-all hover:scale-110 shadow-sm" title="Voir">
                      <Eye size={20} />
                    </button>
                    <button className="p-3 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-all hover:scale-110 shadow-sm" title="Modifier">
                      <Edit2 size={20} />
                    </button>
                    <button className="p-3 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-all hover:scale-110 shadow-sm" title="Supprimer">
                      <Trash2 size={20} />
>>>>>>> dev
                    </button>
                  </div>
                </td>
              </tr>
<<<<<<< HEAD
            ))}
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        open={deleteDialogOpen}
        onClose={() => setDeleteDialogOpen(false)}
        onConfirm={handleDeleteConfirm}
        title="Confirmer la suppression"
        message={`Êtes-vous sûr de vouloir supprimer le compte ${accountToDelete?.accountNumber} ?`}
      />
    </>
=======
            )) : (
              <tr>
                <td colSpan="4" className="px-8 py-16 text-center text-gray-500">
                  <p className="text-lg">Aucun compte trouvé</p>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
>>>>>>> dev
  );
};

export default ListCompte;
