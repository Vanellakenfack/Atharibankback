import React, { useState } from 'react';
import Header from '../../components/layout/Header';
import Sidebar from '../../components/layout/Sidebar';
import { useNavigate } from 'react-router-dom';
import AccountList from '../../components/compte/ListCompte';
import AccountFilters from '../../components/compte/CompteFilters';

const AccountsPage = () => {
  const navigate = useNavigate();
  const [filters, setFilters] = useState({});

  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
  };

  const handleResetFilters = () => {
    setFilters({});
  };

  const handleCreateAccount = () => {
    navigate('/accounts/create');
  };

  return (
    <div>
      <Header />

      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-800"></h1>
        <button
          onClick={handleCreateAccount}
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium"
        >
          ➕ Nouveau compte
        </button>
      </div>

      <AccountFilters
        filters={filters}
        onFilterChange={handleFilterChange}
        onReset={handleResetFilters}
      />

      <div className="bg-white rounded-lg shadow p-4">
        <AccountList filters={filters} />
      </div>
    </div>
  );
};

export default AccountsPage;