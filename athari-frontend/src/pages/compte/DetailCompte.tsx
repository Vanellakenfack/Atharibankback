import React from 'react';
import { useParams } from 'react-router-dom';
import { Link as RouterLink } from 'react-router-dom';
import AccountView from '../../components/compte/CompteView';

const AccountDetailPage = () => {
  const { id } = useParams();

  return (
    <div>
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 mb-6 text-sm">
        <RouterLink to="/accounts" className="text-indigo-600 hover:text-indigo-700">
          Comptes
        </RouterLink>
        <span className="text-gray-400">›</span>
        <span className="text-gray-700">Détails du compte</span>
      </nav>

      <AccountView accountId={id} />
    </div>
  );
};

export default AccountDetailPage;