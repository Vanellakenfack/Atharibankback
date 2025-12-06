import React, { useState, useEffect } from 'react';
import {
  Paper,
  Typography,
  Grid,
  Box,
  Chip,
  Divider,
  Button,
  Card,
  CardContent,
  List,
  ListItem,
  ListItemText,
  IconButton,
  Tooltip,
} from '@mui/material';
import {
  Edit as EditIcon,
  Print as PrintIcon,
  History as HistoryIcon,
  AccountBalance as AccountIcon,
  Person as PersonIcon,
  LocationOn as LocationIcon,
  AttachMoney as MoneyIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import accountService from '../../services/api/compteService';
import LoadingSpinner from '../common/LoadingSpinner';

const AccountView = ({ accountId }) => {
  const navigate = useNavigate();
  const [account, setAccount] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadAccount();
  }, [accountId]);

  const loadAccount = async () => {
    try {
      const data = await accountService.getAccountById(accountId);
      setAccount(data);
    } catch (error) {
      console.error('Error loading account:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = () => {
    navigate(`/accounts/${accountId}/edit`);
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

  if (!account) {
    return (
      <Paper sx={{ p: 3, textAlign: 'center' }}>
        <Typography variant="h6" color="error">
          Compte non trouvé
        </Typography>
      </Paper>
    );
  }

  return (
    <Grid container spacing={3}>
      <Grid item xs={12}>
        <Paper sx={{ p: 3 }}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
            <Box sx={{ display: 'flex', alignItems: 'center' }}>
              <AccountIcon sx={{ mr: 2, fontSize: 40, color: 'primary.main' }} />
              <Box>
                <Typography variant="h4">
                  {account.accountNumber}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {getAccountTypeLabel(account.accountType)}
                </Typography>
              </Box>
            </Box>
            <Box>
              <Chip
                label={account.status.toUpperCase()}
                color={getStatusColor(account.status)}
                sx={{ mr: 2 }}
              />
              <Tooltip title="Modifier">
                <IconButton onClick={handleEdit} color="primary">
                  <EditIcon />
                </IconButton>
              </Tooltip>
              <Tooltip title="Imprimer">
                <IconButton color="secondary">
                  <PrintIcon />
                </IconButton>
              </Tooltip>
            </Box>
          </Box>

          <Divider sx={{ mb: 3 }} />

          <Grid container spacing={3}>
            <Grid item xs={12} md={6}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
                    <PersonIcon sx={{ mr: 1 }} />
                    Informations client
                  </Typography>
                  <List dense>
                    <ListItem>
                      <ListItemText
                        primary="Nom du client"
                        secondary={account.clientName}
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Numéro client"
                        secondary={account.clientNumber}
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Agence"
                        secondary={account.agency}
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Date de création"
                        secondary={new Date(account.createdAt).toLocaleDateString('fr-FR')}
                      />
                    </ListItem>
                  </List>
                </CardContent>
              </Card>
            </Grid>

            <Grid item xs={12} md={6}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
                    <MoneyIcon sx={{ mr: 1 }} />
                    Informations financières
                  </Typography>
                  <List dense>
                    <ListItem>
                      <ListItemText
                        primary="Solde actuel"
                        secondary={
                          <Typography variant="h6" color="primary">
                            {new Intl.NumberFormat('fr-FR', {
                              style: 'currency',
                              currency: account.currency,
                            }).format(account.balance)}
                          </Typography>
                        }
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Solde minimum"
                        secondary={new Intl.NumberFormat('fr-FR', {
                          style: 'currency',
                          currency: account.currency,
                        }).format(account.minimumBalance)}
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Taux de commission"
                        secondary={`${(account.commissionRate * 100).toFixed(2)}%`}
                      />
                    </ListItem>
                    <ListItem>
                      <ListItemText
                        primary="Devise"
                        secondary={account.currency}
                      />
                    </ListItem>
                  </List>
                </CardContent>
              </Card>
            </Grid>

            <Grid item xs={12}>
              <Card>
                <CardContent>
                  <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
                    <HistoryIcon sx={{ mr: 1 }} />
                    Paramètres du compte
                  </Typography>
                  <Grid container spacing={2}>
                    <Grid item xs={6} md={3}>
                      <Typography variant="body2" color="text.secondary">
                        Notifications SMS
                      </Typography>
                      <Chip
                        label={account.sendSmsNotifications ? 'Activé' : 'Désactivé'}
                        color={account.sendSmsNotifications ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                    <Grid item xs={6} md={3}>
                      <Typography variant="body2" color="text.secondary">
                        Découvert autorisé
                      </Typography>
                      <Chip
                        label={account.allowOverdraft ? 'Oui' : 'Non'}
                        color={account.allowOverdraft ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                    {account.allowOverdraft && (
                      <Grid item xs={6} md={3}>
                        <Typography variant="body2" color="text.secondary">
                          Limite de découvert
                        </Typography>
                        <Typography variant="body1">
                          {new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: account.currency,
                          }).format(account.overdraftLimit)}
                        </Typography>
                      </Grid>
                    )}
                  </Grid>
                </CardContent>
              </Card>
            </Grid>
          </Grid>

          <Box sx={{ mt: 3, display: 'flex', gap: 2, justifyContent: 'center' }}>
            <Button
              variant="outlined"
              startIcon={<HistoryIcon />}
              onClick={() => navigate(`/accounts/${accountId}/transactions`)}
            >
              Voir l'historique
            </Button>
            <Button
              variant="contained"
              startIcon={<EditIcon />}
              onClick={handleEdit}
            >
              Modifier le compte
            </Button>
          </Box>
        </Paper>
      </Grid>
    </Grid>
  );
};

export default AccountView;