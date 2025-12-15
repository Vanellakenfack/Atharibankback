import React from 'react';
<<<<<<< HEAD
import { useParams, Link as RouterLink } from 'react-router-dom';
=======
import { useParams } from 'react-router-dom';
import { Link as RouterLink } from 'react-router-dom';
>>>>>>> dev
import AccountView from '../../components/compte/CompteView';

const AccountDetailPage = () => {
  const { id } = useParams();

  return (
    <div>
<<<<<<< HEAD
      <nav className="text-sm text-gray-500 mb-3">
        <RouterLink to="/accounts" className="text-blue-600">Comptes</RouterLink>
        <span className="px-2">/</span>
        <span className="text-gray-800">Détails du compte</span>
=======
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 mb-6 text-sm">
        <RouterLink to="/accounts" className="text-indigo-600 hover:text-indigo-700">
          Comptes
        </RouterLink>
        <span className="text-gray-400">›</span>
        <span className="text-gray-700">Détails du compte</span>
>>>>>>> dev
      </nav>

      <AccountView accountId={id} />
    </div>
  );
};

export default AccountDetailPage;