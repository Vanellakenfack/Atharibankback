import React, { useState } from 'react';
<<<<<<< HEAD
import Header from '../../components/layout/Header';
import Sidebar from '../../components/layout/Sidebar';
=======
import {
  Box,
  Typography,
  Button,
  Paper,
} from '@mui/material';
import { Add as AddIcon } from '@mui/icons-material';
>>>>>>> 09f7f520819d17b8f5bd2c7cfcce97e473c264b0
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
<<<<<<< HEAD
    <div>
      <Header />

      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-800"></h1>
        <button
=======
    <Box>
    
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4">
        
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
>>>>>>> 09f7f520819d17b8f5bd2c7cfcce97e473c264b0
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