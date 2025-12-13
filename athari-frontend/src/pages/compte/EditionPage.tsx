import React from 'react';
import { useParams, Link as RouterLink } from 'react-router-dom';
import AccountForm from '../../components/compte/Formulaire';

const AccountEditPage = () => {
  const { id } = useParams();

  return (
    <div>
      <nav className="text-sm text-gray-500 mb-3">
        <RouterLink to="/accounts" className="text-blue-600">Comptes</RouterLink>
        <span className="px-2">/</span>
        <RouterLink to={`/accounts/${id}`} className="text-blue-600">Détails</RouterLink>
        <span className="px-2">/</span>
        <span className="text-gray-800">Modifier</span>
      </nav>

      <h1 className="text-2xl font-semibold mb-4">Modification du compte</h1>

      <AccountForm accountId={id} />
    </div>
  );
};

export default AccountEditPage;