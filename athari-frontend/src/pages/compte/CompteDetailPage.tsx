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
<<<<<<< HEAD
      <div className="w-full">
        <div className="h-1 bg-blue-600 animate-pulse" />
=======
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-600"></div>
>>>>>>> dev
      </div>
    );
  }

  if (error) {
<<<<<<< HEAD
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
=======
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg">
          <p className="font-bold">Erreur</p>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!account) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-600">Compte non trouvé</div>
      </div>
    );
  }

  const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      savings: 'bg-green-100 text-green-800 border-green-300',
      checking: 'bg-blue-100 text-blue-800 border-blue-300',
      loan: 'bg-orange-100 text-orange-800 border-orange-300',
    };
    return colors[type] || 'bg-gray-100 text-gray-800 border-gray-300';
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      active: 'bg-green-600 text-white',
      blocked: 'bg-red-600 text-white',
      pending: 'bg-yellow-600 text-white',
      closed: 'bg-gray-600 text-white',
    };
    return colors[status] || 'bg-gray-600 text-white';
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* En-tête avec boutons */}
        <div className="flex justify-between items-center mb-8">
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate('/accounts')}
              className="flex items-center gap-2 px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
            >
              ← Retour
            </button>
            <h1 className="text-3xl font-bold text-gray-800">Détails du Compte</h1>
          </div>

          <div className="flex gap-2">
            <button className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              🖨️ Imprimer
            </button>
            <button className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              ⬇️ Exporter
            </button>
            <button
              onClick={() => navigate(`/accounts/${account.id}/edit`)}
              className="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
            >
              ✏️ Modifier
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Colonne gauche - Informations principales */}
          <div className="lg:col-span-2">
            {/* Carte principale */}
            <div className="bg-white rounded-lg shadow p-6 mb-6">
              {/* En-tête du compte */}
              <div className="flex justify-between items-start mb-6 pb-6 border-b">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full text-xl">
                    🏦
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold text-gray-800">{account.accountNumber}</h2>
                    <p className="text-gray-600">{account.clientName}</p>
                  </div>
                </div>

                <div className="flex gap-2">
                  <span className={`px-3 py-1 rounded-full text-sm font-medium border ${getTypeColor(account.type)}`}>
                    {account.type?.toUpperCase()}
                  </span>
                  <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(account.status)}`}>
                    {account.status?.toUpperCase()}
                  </span>
                </div>
              </div>

              {/* Informations financières */}
              <div className="mb-8">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Informations Financières</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  {/* Solde */}
                  <div className="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
                    <p className="text-gray-600 text-sm mb-1">Solde Actuel</p>
                    <p className="text-xl font-bold text-indigo-700">
                      {account.balance?.toLocaleString()} {account.currency}
                    </p>
                  </div>

                  {/* Solde disponible */}
                  <div className="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                    <p className="text-gray-600 text-sm mb-1">Solde Disponible</p>
                    <p className="text-xl font-bold text-green-700">
                      {account.availableBalance?.toLocaleString()} {account.currency}
                    </p>
                  </div>

                  {/* Taux intérêt */}
                  <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                    <p className="text-gray-600 text-sm mb-1">Taux d'Intérêt</p>
                    <p className="text-xl font-bold text-blue-700">{account.interestRate || 0}%</p>
                  </div>

                  {/* Frais */}
                  <div className="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
                    <p className="text-gray-600 text-sm mb-1">Frais Mensuels</p>
                    <p className="text-xl font-bold text-orange-700">
                      {(account.monthlyFees || 0).toLocaleString()} {account.currency}
                    </p>
                  </div>
                </div>
              </div>

              {/* Limites et restrictions */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h4 className="font-semibold text-gray-800 mb-4">Limites</h4>
                  <div className="space-y-3">
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Limite de retrait:</span>
                      <span className="font-medium">{account.withdrawalLimit?.toLocaleString() || 'N/A'} {account.currency}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Limite journalière:</span>
                      <span className="font-medium">{account.dailyWithdrawalLimit?.toLocaleString() || 'N/A'} {account.currency}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Solde minimum:</span>
                      <span className="font-medium">{account.minimumBalance?.toLocaleString() || 'N/A'} {account.currency}</span>
                    </div>
                  </div>
                </div>

                {account.restrictions && (
                  <div>
                    <h4 className="font-semibold text-gray-800 mb-4">Restrictions</h4>
                    <div className="space-y-2">
                      {account.restrictions.noDebit && (
                        <div className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Pas de débit</div>
                      )}
                      {account.restrictions.noCredit && (
                        <div className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Pas de crédit</div>
                      )}
                      {account.restrictions.noTransfer && (
                        <div className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Pas de virement</div>
                      )}
                      {account.restrictions.reason && (
                        <p className="text-xs text-gray-600 mt-2">Raison: {account.restrictions.reason}</p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Sous-comptes */}
            {account.subAccounts && Object.keys(account.subAccounts).length > 0 && (
              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Sous-comptes MATA Boost</h3>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                  {Object.entries(account.subAccounts).map(([key, value]) => (
                    <div key={key} className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-600 text-sm mb-1">{key.charAt(0).toUpperCase() + key.slice(1)}</p>
                      <p className="text-lg font-bold text-gray-800">
                        {(value as number).toLocaleString()} {account.currency}
                      </p>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Colonne droite - Informations secondaires */}
          <div className="space-y-6">
            {/* Informations client */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">👤 Informations Client</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">ID Client:</span>
                  <span className="font-medium">{account.clientId}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Nom:</span>
                  <span className="font-medium">{account.clientName}</span>
                </div>
              </div>
            </div>

            {/* Informations agence */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">🏢 Informations Agence</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Agence:</span>
                  <span className="font-medium">{account.branchName}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Code:</span>
                  <span className="font-medium">{account.branchId}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Gestionnaire:</span>
                  <span className="font-medium">{account.managerName || 'N/A'}</span>
                </div>
              </div>
            </div>

            {/* Dates importantes */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">📅 Dates Importantes</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Ouverture:</span>
                  <span className="font-medium">{new Date(account.openingDate).toLocaleDateString('fr-FR')}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Dernière activité:</span>
                  <span className="font-medium">
                    {account.lastActivityDate ? new Date(account.lastActivityDate).toLocaleDateString('fr-FR') : 'Aucune'}
                  </span>
                </div>
                {account.maturityDate && (
                  <div className="flex justify-between">
                    <span className="text-gray-600">Échéance:</span>
                    <span className="font-medium">{new Date(account.maturityDate).toLocaleDateString('fr-FR')}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Actions rapides */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">💼 Actions Rapides</h3>
              <div className="space-y-2">
                <button className="w-full px-4 py-2 text-left text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200 text-sm font-medium">
                  📋 Voir l'historique
                </button>
                <button className="w-full px-4 py-2 text-left text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200 text-sm font-medium">
                  📊 Générer un relevé
                </button>
                <button className="w-full px-4 py-2 text-left text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                  🗑️ Clôturer le compte
                </button>
              </div>
>>>>>>> dev
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AccountDetailPage;
