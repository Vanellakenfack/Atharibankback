<<<<<<< HEAD
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import accountService from '../../services/api/compteService';
import LoadingSpinner from '../common/LoadingSpinner';
=======
import React from 'react';
import { Edit2, Trash2, Download, Printer } from 'lucide-react';
>>>>>>> dev

interface CompteViewProps {
  compte?: any;
  onEdit?: () => void;
  onDelete?: () => void;
  onDownload?: () => void;
  onPrint?: () => void;
}

<<<<<<< HEAD
  useEffect(() => {
    loadAccount();
  }, [accountId]);

  const loadAccount = async () => {
    try {
      const data = await accountService.getAccountById(accountId);
      setAccount(data);
    } catch (error) {
      console.error('Error loading account:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = () => {
    navigate(`/accounts/${accountId}/edit`);
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

  if (!account) {
    return (
      <div className="p-3 text-center">
        <div className="text-red-600 font-medium">Compte non trouvé</div>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-3">
      <div className="bg-white border p-3 rounded">
        <div className="flex justify-between items-start mb-3">
          <div className="flex items-center gap-3">
            <div className="text-3xl">🏦</div>
            <div>
              <div className="text-xl font-semibold">{account.accountNumber}</div>
              <div className="text-sm text-gray-600">{getAccountTypeLabel(account.accountType)}</div>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusStyle(account.status)}`}>{account.status.toUpperCase()}</span>
            <button onClick={handleEdit} className="p-1 rounded hover:bg-gray-100">✏️</button>
            <button className="p-1 rounded hover:bg-gray-100">🖨️</button>
          </div>
        </div>

        <div className="border-t pt-3">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <h3 className="text-lg font-medium flex items-center gap-2">👤 Informations client</h3>
              <div className="text-sm text-gray-600">Nom du client: <span className="font-medium text-gray-800">{account.clientName}</span></div>
              <div className="text-sm text-gray-600">Numéro client: <span className="font-medium text-gray-800">{account.clientNumber}</span></div>
              <div className="text-sm text-gray-600">Agence: <span className="font-medium text-gray-800">{account.agency}</span></div>
              <div className="text-sm text-gray-600">Date de création: <span className="font-medium text-gray-800">{new Date(account.createdAt).toLocaleDateString('fr-FR')}</span></div>
            </div>

            <div className="space-y-2">
              <h3 className="text-lg font-medium flex items-center gap-2">💰 Informations financières</h3>
              <div className="text-sm">Solde actuel: <span className="font-medium text-blue-600">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: account.currency }).format(account.balance)}</span></div>
              <div className="text-sm">Solde minimum: <span className="font-medium">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: account.currency }).format(account.minimumBalance)}</span></div>
              <div className="text-sm">Taux de commission: <span className="font-medium">{(account.commissionRate * 100).toFixed(2)}%</span></div>
              <div className="text-sm">Devise: <span className="font-medium">{account.currency}</span></div>
            </div>
          </div>

          <div className="mt-4">
            <h3 className="text-lg font-medium">Paramètres du compte</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
              <div>
                <div className="text-sm text-gray-500">Notifications SMS</div>
                <div className="inline-block mt-1 px-2 py-1 rounded text-sm font-medium {account.sendSmsNotifications ? 'text-green-700' : 'text-gray-700'}">{account.sendSmsNotifications ? 'Activé' : 'Désactivé'}</div>
              </div>
              <div>
                <div className="text-sm text-gray-500">Découvert autorisé</div>
                <div className="inline-block mt-1 px-2 py-1 rounded text-sm font-medium">{account.allowOverdraft ? 'Oui' : 'Non'}</div>
              </div>
              {account.allowOverdraft && (
                <div>
                  <div className="text-sm text-gray-500">Limite de découvert</div>
                  <div className="font-medium mt-1">{new Intl.NumberFormat('fr-FR', { style: 'currency', currency: account.currency }).format(account.overdraftLimit)}</div>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="mt-3 flex justify-center gap-2">
          <button onClick={() => navigate(`/accounts/${accountId}/transactions`)} className="px-4 py-2 border rounded">Voir l'historique</button>
          <button onClick={handleEdit} className="px-4 py-2 bg-blue-600 text-white rounded">Modifier le compte</button>
        </div>
=======
const CompteView: React.FC<CompteViewProps> = ({ 
  compte, 
  onEdit, 
  onDelete, 
  onDownload, 
  onPrint 
}) => {
  if (!compte) {
    return <div className="p-4">Aucun compte sélectionné</div>;
  }

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-4">{compte.numero}</h2>
        
        <div className="grid grid-cols-2 gap-4 mb-6">
          <div>
            <p className="text-sm text-gray-600">Type</p>
            <p className="text-lg font-semibold text-gray-900">{compte.type}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Solde</p>
            <p className="text-lg font-semibold text-indigo-600">{compte.solde} €</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Devise</p>
            <p className="text-lg font-semibold text-gray-900">{compte.devise || 'EUR'}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Status</p>
            <p className="text-lg font-semibold text-green-600">Actif</p>
          </div>
        </div>

        <div className="border-t pt-4 mb-6">
          <p className="text-sm text-gray-600 mb-2">Détails supplémentaires</p>
          <p className="text-gray-700">{compte.description || 'Pas de description'}</p>
        </div>
      </div>

      <div className="flex gap-2">
        <button
          onClick={onEdit}
          className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Edit2 size={18} />
          Modifier
        </button>
        <button
          onClick={onDownload}
          className="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Download size={18} />
          Télécharger
        </button>
        <button
          onClick={onPrint}
          className="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Printer size={18} />
          Imprimer
        </button>
        <button
          onClick={onDelete}
          className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Trash2 size={18} />
          Supprimer
        </button>
>>>>>>> dev
      </div>
    </div>
  );
};

export default CompteView;
