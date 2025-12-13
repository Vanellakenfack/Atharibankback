import React from 'react';
import { useParams, Link as RouterLink } from 'react-router-dom';
import AccountView from '../../components/compte/CompteView';

const AccountDetailPage = () => {
  const { id } = useParams();

  return (
    <div>
      <nav className="text-sm text-gray-500 mb-3">
        <RouterLink to="/accounts" className="text-blue-600">Comptes</RouterLink>
        <span className="px-2">/</span>
        <span className="text-gray-800">Détails du compte</span>
      </nav>

      <AccountView accountId={id} />
    </div>
  );
};

export default AccountDetailPage;