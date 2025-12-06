import React, { useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  Grid,
  Typography,
  Chip,
  Stack,
  Button,
  Divider,
  LinearProgress,
  Alert,
} from '@mui/material';
import {
  ArrowBack as BackIcon,
  Edit as EditIcon,
  Print as PrintIcon,
  History as HistoryIcon,
  Download as DownloadIcon,
  AccountBalance as AccountIcon,
  Person as PersonIcon,
  LocationOn as BranchIcon,
  Event as DateIcon,
  MonetizationOn as BalanceIcon,
  AccountBalanceWallet as WalletIcon,
} from '@mui/icons-material';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchAccountById } from '../../store/compte/compteThunks';
import {
  selectSelectedAccount,
  selectIsLoading,
  selectError,
} from '../../store/compte/compteSelectors';

const AccountDetailPage: React.FC = () => {
  const { id } = useParams();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const account = useSelector(selectSelectedAccount);
  const isLoading = useSelector(selectIsLoading);
  const error = useSelector(selectError);

  useEffect(() => {
    if (id) {
      dispatch(fetchAccountById(id) as any);
    }
  }, [dispatch, id]);

  if (isLoading) {
    return <LinearProgress />;
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  if (!account) {
    return <Alert severity="info">Compte non trouvé</Alert>;
  }

  const getTypeColor = (type: string) => {
    const colors: Record<string, 'primary' | 'secondary' | 'success' | 'warning' | 'info'> = {
      courant: 'primary',
      epargne: 'success',
      bloque: 'warning',
      mata_boost: 'info',
      collecte_journaliere: 'secondary',
    };
    return colors[type] || 'primary';
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, 'success' | 'error' | 'warning' | 'default'> = {
      active: 'success',
      blocked: 'error',
      pending: 'warning',
      closed: 'default',
    };
    return colors[status] || 'default';
  };

  return (
    <Box>
      {/* En-tête avec boutons d'action */}
      <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
        <Stack direction="row" spacing={2} alignItems="center">
          <Button
            startIcon={<BackIcon />}
            onClick={() => navigate('/accounts')}
          >
            Retour
          </Button>
          <Typography variant="h4" fontWeight="bold">
            Détails du Compte
          </Typography>
        </Stack>

        <Stack direction="row" spacing={1}>
          <Button
            startIcon={<PrintIcon />}
            variant="outlined"
          >
            Imprimer
          </Button>
          <Button
            startIcon={<DownloadIcon />}
            variant="outlined"
          >
            Exporter
          </Button>
          <Button
            startIcon={<EditIcon />}
            variant="contained"
            onClick={() => navigate(`/accounts/${account.id}/edit`)}
          >
            Modifier
          </Button>
        </Stack>
      </Stack>

      <Grid container spacing={3}>
        {/* Colonne gauche - Informations principales */}
        <Grid item xs={12} md={8}>
          <Card>
            <CardContent>
              <Grid container spacing={3}>
                {/* En-tête du compte */}
                <Grid item xs={12}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center">
                    <Stack direction="row" spacing={2} alignItems="center">
                      <AccountIcon color="primary" sx={{ fontSize: 40 }} />
                      <Box>
                        <Typography variant="h5" fontWeight="bold">
                          {account.accountNumber}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {account.clientName}
                        </Typography>
                      </Box>
                    </Stack>

                    <Stack direction="row" spacing={1}>
                      <Chip
                        label={account.type.toUpperCase()}
                        color={getTypeColor(account.type)}
                        variant="outlined"
                      />
                      <Chip
                        label={account.status.toUpperCase()}
                        color={getStatusColor(account.status)}
                      />
                    </Stack>
                  </Stack>
                </Grid>

                <Grid item xs={12}>
                  <Divider />
                </Grid>

                {/* Informations financières */}
                <Grid item xs={12}>
                  <Typography variant="h6" gutterBottom>
                    Informations Financières
                  </Typography>
                  <Grid container spacing={2}>
                    <Grid item xs={6} md={3}>
                      <Card variant="outlined">
                        <CardContent>
                          <Typography variant="caption" color="text.secondary" display="block">
                            Solde Actuel
                          </Typography>
                          <Typography variant="h5" fontWeight="bold" color="primary.main">
                            {account.balance.toLocaleString()} {account.currency}
                          </Typography>
                        </CardContent>
                      </Card>
                    </Grid>

                    <Grid item xs={6} md={3}>
                      <Card variant="outlined">
                        <CardContent>
                          <Typography variant="caption" color="text.secondary" display="block">
                            Solde Disponible
                          </Typography>
                          <Typography variant="h5" fontWeight="bold" color="success.main">
                            {account.availableBalance.toLocaleString()} {account.currency}
                          </Typography>
                        </CardContent>
                      </Card>
                    </Grid>

                    <Grid item xs={6} md={3}>
                      <Card variant="outlined">
                        <CardContent>
                          <Typography variant="caption" color="text.secondary" display="block">
                            Taux d'Intérêt
                          </Typography>
                          <Typography variant="h5" fontWeight="bold">
                            {account.interestRate || 0}%
                          </Typography>
                        </CardContent>
                      </Card>
                    </Grid>

                    <Grid item xs={6} md={3}>
                      <Card variant="outlined">
                        <CardContent>
                          <Typography variant="caption" color="text.secondary" display="block">
                            Frais Mensuels
                          </Typography>
                          <Typography variant="h5" fontWeight="bold">
                            {(account.monthlyFees || 0).toLocaleString()} {account.currency}
                          </Typography>
                        </CardContent>
                      </Card>
                    </Grid>
                  </Grid>
                </Grid>

                {/* Limites et restrictions */}
                <Grid item xs={12}>
                  <Typography variant="h6" gutterBottom>
                    Limites et Restrictions
                  </Typography>
                  <Grid container spacing={2}>
                    <Grid item xs={12} md={6}>
                      <Stack spacing={1}>
                        <Stack direction="row" justifyContent="space-between">
                          <Typography variant="body2">Limite de retrait:</Typography>
                          <Typography variant="body2" fontWeight="medium">
                            {account.withdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}
                          </Typography>
                        </Stack>
                        <Stack direction="row" justifyContent="space-between">
                          <Typography variant="body2">Limite journalière:</Typography>
                          <Typography variant="body2" fontWeight="medium">
                            {account.dailyWithdrawalLimit?.toLocaleString() || 'Non définie'} {account.currency}
                          </Typography>
                        </Stack>
                        <Stack direction="row" justifyContent="space-between">
                          <Typography variant="body2">Solde minimum:</Typography>
                          <Typography variant="body2" fontWeight="medium">
                            {account.minimumBalance?.toLocaleString() || 'Non défini'} {account.currency}
                          </Typography>
                        </Stack>
                      </Stack>
                    </Grid>

                    <Grid item xs={12} md={6}>
                      {account.restrictions && (
                        <Card variant="outlined">
                          <CardContent>
                            <Typography variant="subtitle2" gutterBottom>
                              Restrictions
                            </Typography>
                            <Stack spacing={0.5}>
                              {account.restrictions.noDebit && (
                                <Chip label="Pas de débit" size="small" color="error" variant="outlined" />
                              )}
                              {account.restrictions.noCredit && (
                                <Chip label="Pas de crédit" size="small" color="error" variant="outlined" />
                              )}
                              {account.restrictions.noTransfer && (
                                <Chip label="Pas de virement" size="small" color="error" variant="outlined" />
                              )}
                            </Stack>
                            {account.restrictions.reason && (
                              <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block' }}>
                                Raison: {account.restrictions.reason}
                              </Typography>
                            )}
                          </CardContent>
                        </Card>
                      )}
                    </Grid>
                  </Grid>
                </Grid>

                {/* Sous-comptes MATA Boost */}
                {account.subAccounts && (
                  <Grid item xs={12}>
                    <Typography variant="h6" gutterBottom>
                      Sous-comptes MATA Boost
                    </Typography>
                    <Grid container spacing={2}>
                      {Object.entries(account.subAccounts).map(([key, value]) => (
                        <Grid item xs={6} md={4} key={key}>
                          <Card variant="outlined">
                            <CardContent>
                              <Typography variant="caption" color="text.secondary" display="block" gutterBottom>
                                {key.charAt(0).toUpperCase() + key.slice(1)}
                              </Typography>
                              <Typography variant="h6" fontWeight="bold">
                                {value.toLocaleString()} {account.currency}
                              </Typography>
                            </CardContent>
                          </Card>
                        </Grid>
                      ))}
                    </Grid>
                  </Grid>
                )}
              </Grid>
            </CardContent>
          </Card>
        </Grid>

        {/* Colonne droite - Informations secondaires */}
        <Grid item xs={12} md={4}>
          <Stack spacing={3}>
            {/* Informations client */}
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <PersonIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                  Informations Client
                </Typography>
                <Stack spacing={1}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      ID Client:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.clientId}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Nom:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.clientName}
                    </Typography>
                  </Stack>
                </Stack>
              </CardContent>
            </Card>

            {/* Informations agence */}
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <BranchIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                  Informations Agence
                </Typography>
                <Stack spacing={1}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Agence:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.branchName}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Code Agence:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.branchId}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Gestionnaire:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.managerName || 'Non assigné'}
                    </Typography>
                  </Stack>
                </Stack>
              </CardContent>
            </Card>

            {/* Informations temporelles */}
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <DateIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                  Dates Importantes
                </Typography>
                <Stack spacing={1}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Date d'ouverture:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {new Date(account.openingDate).toLocaleDateString('fr-FR')}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2" color="text.secondary">
                      Dernière activité:
                    </Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {account.lastActivityDate 
                        ? new Date(account.lastActivityDate).toLocaleDateString('fr-FR')
                        : 'Aucune'
                      }
                    </Typography>
                  </Stack>
                  {account.maturityDate && (
                    <Stack direction="row" justifyContent="space-between">
                      <Typography variant="body2" color="text.secondary">
                        Date d'échéance:
                      </Typography>
                      <Typography variant="body2" fontWeight="medium">
                        {new Date(account.maturityDate).toLocaleDateString('fr-FR')}
                      </Typography>
                    </Stack>
                  )}
                </Stack>
              </CardContent>
            </Card>

            {/* Actions rapides */}
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  <WalletIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                  Actions Rapides
                </Typography>
                <Stack spacing={1}>
                  <Button
                    fullWidth
                    variant="outlined"
                    startIcon={<HistoryIcon />}
                  >
                    Voir l'historique
                  </Button>
                  <Button
                    fullWidth
                    variant="outlined"
                  >
                    Générer un relevé
                  </Button>
                  <Button
                    fullWidth
                    variant="contained"
                    color="error"
                  >
                    Clôturer le compte
                  </Button>
                </Stack>
              </CardContent>
            </Card>
          </Stack>
        </Grid>
      </Grid>
    </Box>
  );
};

export default AccountDetailPage;