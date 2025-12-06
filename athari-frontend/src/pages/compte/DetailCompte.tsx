import React from 'react';
import { useParams } from 'react-router-dom';
import { Box, Typography, Breadcrumbs, Link } from '@mui/material';
import { NavigateNext as NavigateNextIcon } from '@mui/icons-material';
import { Link as RouterLink } from 'react-router-dom';
import AccountView from '../../components/compte/CompteView';

const AccountDetailPage = () => {
  const { id } = useParams();

  return (
    <Box>
      <Breadcrumbs separator={<NavigateNextIcon fontSize="small" />} sx={{ mb: 3 }}>
        <Link component={RouterLink} to="/accounts" color="inherit">
          Comptes
        </Link>
        <Typography color="text.primary">Détails du compte</Typography>
      </Breadcrumbs>

      <AccountView accountId={id} />
    </Box>
  );
};

export default AccountDetailPage;