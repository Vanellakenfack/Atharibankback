import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import AccountForm from '../../components/compte/Formulaire';

const AccountCreatePage = () => {
  return (
    <div>
      <nav className="text-sm text-gray-500 mb-3">
        <RouterLink to="/accounts" className="text-blue-600">Comptes</RouterLink>
        <span className="px-2">/</span>
        <span className="text-gray-800">Créer un compte</span>
      </nav>

      <h1 className="text-2xl font-semibold mb-4">Création d'un nouveau compte</h1>

      <AccountForm />
    </div>
  );
};

export default AccountCreatePage;