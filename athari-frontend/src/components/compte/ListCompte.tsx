import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState, AppDispatch } from '../../store';
import { selectAccounts, selectIsLoading, selectError } from '../../store/compte/compteSelectors';
import { fetchAccounts } from '../../store/compte/compteThunks';
import { Eye, Edit2, Trash2, TrendingUp } from 'lucide-react';

const ListCompte: React.FC = () => {
  const dispatch = useDispatch<AppDispatch>();
  const comptes = useSelector((state: RootState) => selectAccounts(state));
  const loading = useSelector((state: RootState) => selectIsLoading(state));
  const error = useSelector((state: RootState) => selectError(state));

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
                    </button>
                  </div>
                </td>
              </tr>
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
  );
};

export default ListCompte;
