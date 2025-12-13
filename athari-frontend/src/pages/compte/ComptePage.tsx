import React, { useState } from 'react';
import Header from '../../components/layout/Header';
import Sidebar from '../../components/layout/Sidebar.tsx';
import {
  Box,
  Typography,
  Button,
  Paper,
} from '@mui/material';
import { Add as AddIcon } from '@mui/icons-material';
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
    <Box>
      <Header/>
    
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4">
        
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={handleCreateAccount}
        >
          Nouveau compte
        </Button>
      </Box>

      <AccountFilters
        filters={filters}
        onFilterChange={handleFilterChange}
        onReset={handleResetFilters}
      />

      <Paper sx={{ p: 2 }}>
        <AccountList filters={filters} />
      </Paper>
    </Box>
  );
};

export default AccountsPage;