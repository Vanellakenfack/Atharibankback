import React, { useState } from 'react';
import Header from '../../components/layout/Header';
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

      <div className="flex justify-between items-center mb-3">
        <h2 className="text-2xl font-semibold">Comptes</h2>
        <button onClick={handleCreateAccount} className="px-3 py-2 bg-blue-600 text-white rounded">Nouveau compte</button>
      </div>

      <AccountFilters filters={filters} onFilterChange={handleFilterChange} onReset={handleResetFilters} />

      <div className="p-2 bg-white border rounded">
        <AccountList filters={filters} />
      </div>
    </div>
  );
};

export default AccountsPage;