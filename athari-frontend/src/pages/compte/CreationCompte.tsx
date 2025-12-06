import React from 'react';
import { Box, Typography, Breadcrumbs, Link } from '@mui/material';
import { NavigateNext as NavigateNextIcon } from '@mui/icons-material';
import { Link as RouterLink } from 'react-router-dom';
import AccountForm from '../../components/compte/Formulaire';

const AccountCreatePage = () => {
  return (
    <Box>
      <Breadcrumbs separator={<NavigateNextIcon fontSize="small" />} sx={{ mb: 3 }}>
        <Link component={RouterLink} to="/accounts" color="inherit">
          Comptes
        </Link>
        <Typography color="text.primary">Créer un compte</Typography>
      </Breadcrumbs>

      <Typography variant="h4" gutterBottom>
        Création d'un nouveau compte
      </Typography>

      <AccountForm />
    </Box>
  );
};

export default AccountCreatePage;