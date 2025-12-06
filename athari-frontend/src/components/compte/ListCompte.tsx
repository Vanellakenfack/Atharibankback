import React, { useState, useEffect } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  IconButton,
  Chip,
  Tooltip,
  Typography,
  Box,
} from '@mui/material';
import {
  Visibility as ViewIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  AccountBalance as AccountIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import accountService from '../../services/api/compteService';
import ConfirmDialog from '../common/ConfirnDialog';
import LoadingSpinner from '../common/LoadingSpinner';

const AccountList = ({ filters = {} }) => {
  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [accountToDelete, setAccountToDelete] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    loadAccounts();
  }, [filters]);

  const loadAccounts = async () => {
    setLoading(true);
    try {
      const data = await accountService.filterAccounts(filters);
      setAccounts(data);
    } catch (error) {
      console.error('Error loading accounts:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleView = (id) => {
    navigate(`/accounts/${id}`);
  };

  const handleEdit = (id) => {
    navigate(`/accounts/${id}/edit`);
  };

  const handleDeleteClick = (account) => {
    setAccountToDelete(account);
    setDeleteDialogOpen(true);
  };

  const handleDeleteConfirm = async () => {
    if (accountToDelete) {
      try {
        await accountService.deleteAccount(accountToDelete.id);
        await loadAccounts();
      } catch (error) {
        console.error('Error deleting account:', error);
      }
    }
    setDeleteDialogOpen(false);
    setAccountToDelete(null);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'success';
      case 'blocked': return 'error';
      case 'pending': return 'warning';
      case 'closed': return 'default';
      default: return 'default';
    }
  };

  const getAccountTypeLabel = (typeCode) => {
    const accountTypes = accountService.getAccountTypes();
    const type = accountTypes.find(t => t.code === typeCode);
    return type ? type.label : typeCode;
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  if (accounts.length === 0) {
    return (
      <Box sx={{ textAlign: 'center', py: 4 }}>
        <AccountIcon sx={{ fontSize: 60, color: 'text.secondary', mb: 2 }} />
        <Typography variant="h6" color="text.secondary">
          Aucun compte trouvé
        </Typography>
      </Box>
    );
  }

  return (
    <>
      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Numéro de compte</TableCell>
              <TableCell>Client</TableCell>
              <TableCell>Type de compte</TableCell>
              <TableCell>Agence</TableCell>
              <TableCell align="right">Solde</TableCell>
              <TableCell>Statut</TableCell>
              <TableCell align="center">Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {accounts.map((account) => (
              <TableRow key={account.id}>
                <TableCell>
                  <Typography variant="body2" fontWeight="medium">
                    {account.accountNumber}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {account.clientNumber}
                  </Typography>
                </TableCell>
                <TableCell>{account.clientName}</TableCell>
                <TableCell>{getAccountTypeLabel(account.accountType)}</TableCell>
                <TableCell>{account.agency}</TableCell>
                <TableCell align="right">
                  <Typography fontWeight="medium">
                    {new Intl.NumberFormat('fr-FR', {
                      style: 'currency',
                      currency: account.currency,
                    }).format(account.balance)}
                  </Typography>
                </TableCell>
                <TableCell>
                  <Chip
                    label={account.status}
                    color={getStatusColor(account.status)}
                    size="small"
                  />
                </TableCell>
                <TableCell align="center">
                  <Tooltip title="Voir les détails">
                    <IconButton
                      size="small"
                      onClick={() => handleView(account.id)}
                      color="primary"
                    >
                      <ViewIcon />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Modifier">
                    <IconButton
                      size="small"
                      onClick={() => handleEdit(account.id)}
                      color="secondary"
                    >
                      <EditIcon />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Supprimer">
                    <IconButton
                      size="small"
                      onClick={() => handleDeleteClick(account)}
                      color="error"
                    >
                      <DeleteIcon />
                    </IconButton>
                  </Tooltip>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      <ConfirmDialog
        open={deleteDialogOpen}
        onClose={() => setDeleteDialogOpen(false)}
        onConfirm={handleDeleteConfirm}
        title="Confirmer la suppression"
        message={`Êtes-vous sûr de vouloir supprimer le compte ${accountToDelete?.accountNumber} ?`}
      />
    </>
  );
};

export default AccountList;