import React, { useEffect } from 'react';
import {
  useDispatch, useSelector
} from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchAccountById } from '../../store/compte/compteThunks';
import {
  selectSelectedAccount,
  selectIsLoading,
  selectError,
} from '../../store/compte/compteSelectors';

const AccountDetailPage: React.FC = () => {
  const { id } = useParams();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const account = useSelector(selectSelectedAccount);
  const isLoading = useSelector(selectIsLoading);
  const error = useSelector(selectError);

  useEffect(() => {
    if (id) {
      dispatch(fetchAccountById(id) as any);
    }
  }, [dispatch, id]);

  if (isLoading) {
    return <div className="flex justify-center items-center h-64">Chargement...</div>;
  }

  if (error) {
    return <div className="p-4 bg-red-100 text-red-700 rounded">{error}</div>;
  }

  if (!account) {
    return <div className="p-4 bg-blue-100 text-blue-700 rounded">Compte non trouvé</div>;
  }

  const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      courant: 'bg-blue-100 text-blue-800',
      epargne: 'bg-green-100 text-green-800',
      bloque: 'bg-yellow-100 text-yellow-800',
      mata_boost: 'bg-purple-100 text-purple-800',
      collecte_journaliere: 'bg-indigo-100 text-indigo-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      active: 'bg-green-100 text-green-800',
      blocked: 'bg-red-100 text-red-800',
      pending: 'bg-yellow-100 text-yellow-800',
      closed: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  return (
    <div>
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate('/accounts')}
            className="text-blue-600 hover:text-blue-800"
          >
            ← Retour
          </button>
          <h1 className="text-3xl font-bold">Détails du Compte</h1>
        </div>

        <div className="flex gap-2">
          <button className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            🖨️ Imprimer
          </button>
          <button className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ⬇️ Exporter
          </button>
          <button 
            onClick={() => navigate(`/accounts/${account.id}/edit`)}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            ✏️ Modifier
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Account Header Card */}
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="flex justify-between items-start mb-4">
              <div className="flex gap-4">
                <div className="text-4xl">💳</div>
                <div>
                  <h2 className="text-2xl font-bold">{account.accountNumber}</h2>
                  <p className="text-gray-600">{account.clientName}</p>
                </div>
              </div>
              <div className="flex gap-2">
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${getTypeColor(account.type)}`}>
                  {account.type.toUpperCase()}
                </span>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(account.status)}`}>
                  {account.status.toUpperCase()}
                </span>
              </div>
            </div>
            <hr className="my-4" />
          </div>

          {/* Financial Information */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-xl font-bold mb-4">Informations Financières</h3>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-gray-600">Solde Actuel</p>
                <p className="text-2xl font-bold text-blue-600">{account.balance?.toLocaleString()} {account.currency}</p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-gray-600">Solde Disponible</p>
                <p className="text-2xl font-bold text-green-600">{account.availableBalance?.toLocaleString()} {account.currency}</p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-gray-600">Taux d'Intérêt</p>
                <p className="text-2xl font-bold">{account.interestRate || 0}%</p>
              </div>
              <div className="p-4 border rounded-lg">
                <p className="text-sm text-gray-600">Frais Mensuels</p>
                <p className="text-2xl font-bold">{(account.monthlyFees || 0).toLocaleString()} {account.currency}</p>
              </div>
            </div>
          </div>

          {/* Limits and Restrictions */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-xl font-bold mb-4">Limites et Restrictions</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <div className="flex justify-between">
                  <span className="text-gray-600">Limite de retrait:</span>
                  <span className="font-medium">{account.withdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Limite journalière:</span>
                  <span className="font-medium">{account.dailyWithdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Solde minimum:</span>
                  <span className="font-medium">{account.minimumBalance?.toLocaleString() || 'Non défini'} {account.currency}</span>
                </div>
              </div>
              {account.restrictions && (
                <div className="p-4 border rounded-lg">
                  <p className="font-semibold mb-2">Restrictions</p>
                  <div className="space-y-1">
                    {account.restrictions.noDebit && <div className="text-sm bg-red-100 text-red-800 px-2 py-1 rounded inline-block">Pas de débit</div>}
                    {account.restrictions.noCredit && <div className="text-sm bg-red-100 text-red-800 px-2 py-1 rounded inline-block">Pas de crédit</div>}
                    {account.restrictions.noTransfer && <div className="text-sm bg-red-100 text-red-800 px-2 py-1 rounded inline-block">Pas de virement</div>}
                  </div>
                  {account.restrictions.reason && (
                    <p className="text-xs text-gray-600 mt-2">Raison: {account.restrictions.reason}</p>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Sub-accounts */}
          {account.subAccounts && (
            <div className="bg-white p-6 rounded-lg shadow">
              <h3 className="text-xl font-bold mb-4">Sous-comptes MATA Boost</h3>
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                {Object.entries(account.subAccounts).map(([key, value]) => (
                  <div key={key} className="p-4 border rounded-lg">
                    <p className="text-sm text-gray-600 capitalize">{key}</p>
                    <p className="text-xl font-bold">{(value as number).toLocaleString()} {account.currency}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Client Info */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-bold mb-4">👤 Informations Client</h3>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">ID Client:</span>
                <span className="font-medium text-sm">{account.clientId}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Nom:</span>
                <span className="font-medium text-sm">{account.clientName}</span>
              </div>
            </div>
          </div>

          {/* Branch Info */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-bold mb-4">🏢 Informations Agence</h3>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Agence:</span>
                <span className="font-medium text-sm">{account.branchName}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Code:</span>
                <span className="font-medium text-sm">{account.branchId}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Gestionnaire:</span>
                <span className="font-medium text-sm">{account.managerName || 'Non assigné'}</span>
              </div>
            </div>
          </div>

          {/* Dates */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-bold mb-4">📅 Dates Importantes</h3>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Ouverture:</span>
                <span className="font-medium text-sm">{new Date(account.openingDate).toLocaleDateString('fr-FR')}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600 text-sm">Dernière activité:</span>
                <span className="font-medium text-sm">
                  {account.lastActivityDate 
                    ? new Date(account.lastActivityDate).toLocaleDateString('fr-FR')
                    : 'Aucune'
                  }
                </span>
              </div>
              {account.maturityDate && (
                <div className="flex justify-between">
                  <span className="text-gray-600 text-sm">Échéance:</span>
                  <span className="font-medium text-sm">{new Date(account.maturityDate).toLocaleDateString('fr-FR')}</span>
                </div>
              )}
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h3 className="text-lg font-bold mb-4">💼 Actions</h3>
            <div className="space-y-2">
              <button className="w-full px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                📋 Historique
              </button>
              <button className="w-full px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                📄 Relevé
              </button>
              <button className="w-full px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                ❌ Clôturer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AccountDetailPage;
