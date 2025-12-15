import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import AccountForm from '../../components/compte/Formulaire';

const AccountCreatePage = () => {
  return (
    <div>
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 mb-6 text-sm">
        <RouterLink to="/accounts" className="text-indigo-600 hover:text-indigo-700">
          Comptes
        </RouterLink>
        <span className="text-gray-400">›</span>
        <span className="text-gray-700">Créer un compte</span>
      </nav>

      <h1 className="text-3xl font-bold text-gray-800 mb-6">Création d'un nouveau compte</h1>

      <AccountForm />
    </div>
  );
};

export default AccountCreatePage;