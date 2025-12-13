import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
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
    return (
      <div className="w-full">
        <div className="h-1 bg-blue-600 animate-pulse" />
      </div>
    );
  }

  if (error) {
    return <div className="p-4 bg-red-50 border border-red-200 rounded text-red-800">{error}</div>;
  }

  if (!account) {
    return <div className="p-4 bg-blue-50 border border-blue-200 rounded text-blue-800">Compte non trouvé</div>;
  }

  const getTypeStyle = (type: string) => {
    switch (type) {
      case 'courant': return 'border-blue-300 text-blue-700';
      case 'epargne': return 'border-green-300 text-green-700';
      case 'bloque': return 'border-yellow-300 text-yellow-700';
      case 'mata_boost': return 'border-indigo-300 text-indigo-700';
      case 'collecte_journaliere': return 'border-gray-300 text-gray-700';
      default: return 'border-gray-300 text-gray-700';
    }
  };

  const getStatusStyle = (status: string) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'blocked': return 'bg-red-100 text-red-800';
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'closed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <button onClick={() => navigate('/accounts')} className="px-3 py-2 rounded border hover:bg-gray-50 flex items-center gap-2">
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Retour
          </button>
          <h1 className="text-2xl font-bold">Détails du Compte</h1>
        </div>

        <div className="flex items-center gap-2">
          <button className="px-3 py-2 border rounded">Imprimer</button>
          <button className="px-3 py-2 border rounded">Exporter</button>
          <button onClick={() => navigate(`/accounts/${account.id}/edit`)} className="px-3 py-2 bg-blue-600 text-white rounded">Modifier</button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-4">
          <div className="bg-white border rounded p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="text-3xl text-blue-600">🏦</div>
                <div>
                  <div className="text-xl font-bold">{account.accountNumber}</div>
                  <div className="text-sm text-gray-600">{account.clientName}</div>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <span className={`px-2 py-1 border rounded text-xs font-medium ${getTypeStyle(account.type)}`}>{account.type.toUpperCase()}</span>
                <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusStyle(account.status)}`}>{account.status.toUpperCase()}</span>
              </div>
            </div>

            <div className="border-t mt-4 pt-4">
              <h3 className="text-lg font-semibold mb-3">Informations Financières</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div className="border rounded p-3 bg-gray-50">
                  <div className="text-xs text-gray-500">Solde Actuel</div>
                  <div className="text-xl font-bold text-blue-600">{account.balance.toLocaleString()} {account.currency}</div>
                </div>
                <div className="border rounded p-3 bg-gray-50">
                  <div className="text-xs text-gray-500">Solde Disponible</div>
                  <div className="text-xl font-bold text-green-600">{account.availableBalance.toLocaleString()} {account.currency}</div>
                </div>
                <div className="border rounded p-3 bg-gray-50">
                  <div className="text-xs text-gray-500">Taux d'Intérêt</div>
                  <div className="text-xl font-bold">{account.interestRate || 0}%</div>
                </div>
                <div className="border rounded p-3 bg-gray-50">
                  <div className="text-xs text-gray-500">Frais Mensuels</div>
                  <div className="text-xl font-bold">{(account.monthlyFees || 0).toLocaleString()} {account.currency}</div>
                </div>
              </div>
            </div>

            <div className="mt-4">
              <h3 className="text-lg font-semibold mb-2">Limites et Restrictions</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <div className="flex justify-between"><div className="text-sm">Limite de retrait:</div><div className="font-medium">{account.withdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}</div></div>
                  <div className="flex justify-between"><div className="text-sm">Limite journalière:</div><div className="font-medium">{account.dailyWithdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}</div></div>
                  <div className="flex justify-between"><div className="text-sm">Solde minimum:</div><div className="font-medium">{account.minimumBalance?.toLocaleString() || 'Non défini'} {account.currency}</div></div>
                </div>

                <div>
                  {account.restrictions && (
                    <div className="border rounded p-3">
                      <div className="text-sm font-medium mb-2">Restrictions</div>
                      <div className="flex flex-col gap-1">
                        {account.restrictions.noDebit && (<span className="text-sm text-red-700">• Pas de débit</span>)}
                        {account.restrictions.noCredit && (<span className="text-sm text-red-700">• Pas de crédit</span>)}
                        {account.restrictions.noTransfer && (<span className="text-sm text-red-700">• Pas de virement</span>)}
                      </div>
                      {account.restrictions.reason && (<div className="text-xs text-gray-500 mt-2">Raison: {account.restrictions.reason}</div>)}
                    </div>
                  )}
                </div>
              </div>
            </div>

            {account.subAccounts && (
              <div className="mt-4">
                <h3 className="text-lg font-semibold mb-3">Sous-comptes MATA Boost</h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                  {Object.entries(account.subAccounts).map(([key, value]) => (
                    <div key={key} className="border rounded p-3 bg-white">
                      <div className="text-xs text-gray-500">{key.charAt(0).toUpperCase() + key.slice(1)}</div>
                      <div className="text-lg font-bold">{value.toLocaleString()} {account.currency}</div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="space-y-4">
          <div className="bg-white border rounded p-4">
            <div className="flex items-center gap-2 mb-3"><div className="text-lg">👤</div><div className="text-lg font-semibold">Informations Client</div></div>
            <div className="space-y-2">
              <div className="flex justify-between"><div className="text-sm text-gray-500">ID Client:</div><div className="font-medium">{account.clientId}</div></div>
              <div className="flex justify-between"><div className="text-sm text-gray-500">Nom:</div><div className="font-medium">{account.clientName}</div></div>
            </div>
          </div>

          <div className="bg-white border rounded p-4">
            <div className="flex items-center gap-2 mb-3"><div className="text-lg">📍</div><div className="text-lg font-semibold">Informations Agence</div></div>
            <div className="space-y-2">
              <div className="flex justify-between"><div className="text-sm text-gray-500">Agence:</div><div className="font-medium">{account.branchName}</div></div>
              <div className="flex justify-between"><div className="text-sm text-gray-500">Code Agence:</div><div className="font-medium">{account.branchId}</div></div>
              <div className="flex justify-between"><div className="text-sm text-gray-500">Gestionnaire:</div><div className="font-medium">{account.managerName || 'Non assigné'}</div></div>
            </div>
          </div>

          <div className="bg-white border rounded p-4">
            <div className="flex items-center gap-2 mb-3"><div className="text-lg">📅</div><div className="text-lg font-semibold">Dates Importantes</div></div>
            <div className="space-y-2">
              <div className="flex justify-between"><div className="text-sm text-gray-500">Date d'ouverture:</div><div className="font-medium">{new Date(account.openingDate).toLocaleDateString('fr-FR')}</div></div>
              <div className="flex justify-between"><div className="text-sm text-gray-500">Dernière activité:</div><div className="font-medium">{account.lastActivityDate ? new Date(account.lastActivityDate).toLocaleDateString('fr-FR') : 'Aucune'}</div></div>
              {account.maturityDate && (<div className="flex justify-between"><div className="text-sm text-gray-500">Date d'échéance:</div><div className="font-medium">{new Date(account.maturityDate).toLocaleDateString('fr-FR')}</div></div>)}
            </div>
          </div>

          <div className="bg-white border rounded p-4">
            <div className="flex items-center gap-2 mb-3"><div className="text-lg">💼</div><div className="text-lg font-semibold">Actions Rapides</div></div>
            <div className="flex flex-col gap-2">
              <button className="w-full border rounded py-2">Voir l'historique</button>
              <button className="w-full border rounded py-2">Générer un relevé</button>
              <button className="w-full bg-red-600 text-white rounded py-2">Clôturer le compte</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AccountDetailPage;