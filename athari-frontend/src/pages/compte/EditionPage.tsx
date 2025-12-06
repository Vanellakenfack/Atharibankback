import React from 'react';
import { useParams } from 'react-router-dom';
import { Box, Typography, Breadcrumbs, Link } from '@mui/material';
import { NavigateNext as NavigateNextIcon } from '@mui/icons-material';
import { Link as RouterLink } from 'react-router-dom';
import AccountForm from '../../components/compte/Formulaire';

const AccountEditPage = () => {
  const { id } = useParams();

  return (
    <Box>
      <Breadcrumbs separator={<NavigateNextIcon fontSize="small" />} sx={{ mb: 3 }}>
        <Link component={RouterLink} to="/accounts" color="inherit">
          Comptes
        </Link>
        <Link component={RouterLink} to={`/accounts/${id}`} color="inherit">
          Détails
        </Link>
        <Typography color="text.primary">Modifier</Typography>
      </Breadcrumbs>

      <Typography variant="h4" gutterBottom>
        Modification du compte
      </Typography>

      <AccountForm accountId={id} />
    </Box>
  );
};

export default AccountEditPage;