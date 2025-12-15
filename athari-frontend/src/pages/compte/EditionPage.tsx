import React from 'react';
<<<<<<< HEAD
import { useParams, Link as RouterLink } from 'react-router-dom';
=======
import { useParams } from 'react-router-dom';
import { Link as RouterLink } from 'react-router-dom';
>>>>>>> dev
import AccountForm from '../../components/compte/Formulaire';

const AccountEditPage = () => {
  const { id } = useParams();

  return (
    <div>
<<<<<<< HEAD
      <nav className="text-sm text-gray-500 mb-3">
        <RouterLink to="/accounts" className="text-blue-600">Comptes</RouterLink>
        <span className="px-2">/</span>
        <RouterLink to={`/accounts/${id}`} className="text-blue-600">Détails</RouterLink>
        <span className="px-2">/</span>
        <span className="text-gray-800">Modifier</span>
      </nav>

      <h1 className="text-2xl font-semibold mb-4">Modification du compte</h1>
=======
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 mb-6 text-sm">
        <RouterLink to="/accounts" className="text-indigo-600 hover:text-indigo-700">
          Comptes
        </RouterLink>
        <span className="text-gray-400">›</span>
        <RouterLink to={`/accounts/${id}`} className="text-indigo-600 hover:text-indigo-700">
          Détails
        </RouterLink>
        <span className="text-gray-400">›</span>
        <span className="text-gray-700">Modifier</span>
      </nav>

      <h1 className="text-3xl font-bold text-gray-800 mb-6">Modification du compte</h1>
>>>>>>> dev

      <AccountForm accountId={id} />
    </div>
  );
};

export default AccountEditPage;