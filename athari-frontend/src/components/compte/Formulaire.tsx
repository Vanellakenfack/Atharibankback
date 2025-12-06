import React, { useState, useEffect } from 'react';
import {
  Box,
  TextField,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
  Grid,
  Button,
  Typography,
  Paper,
  Divider,
  FormControlLabel,
  Switch,
  Alert,
} from '@mui/material';
import { LoadingButton } from '@mui/lab';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  AccountBalance as AccountIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import accountService from '../../services/api/compteService';

const AccountForm = ({ accountId = null }) => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const [formData, setFormData] = useState({
    clientNumber: '',
    clientName: '',
    accountType: '',
    agency: '',
    currency: 'XAF',
    minimumBalance: 0,
    commissionRate: 0.01,
    allowOverdraft: false,
    overdraftLimit: 0,
    sendSmsNotifications: true,
    status: 'active',
  });

  const accountTypes = accountService.getAccountTypes();
  const agencies = accountService.getAgencies();

  useEffect(() => {
    if (accountId) {
      loadAccountData();
    }
  }, [accountId]);

  const loadAccountData = async () => {
    setLoading(true);
    try {
      const account = await accountService.getAccountById(accountId);
      if (account) {
        setFormData(account);
      }
    } catch (error) {
      console.error('Error loading account:', error);
      setError('Erreur lors du chargement du compte');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setSuccess('');

    try {
      if (accountId) {
        // Mise à jour
        await accountService.updateAccount(accountId, formData);
        setSuccess('Compte mis à jour avec succès');
      } else {
        // Création
        await accountService.createAccount(formData);
        setSuccess('Compte créé avec succès');
        // Réinitialiser le formulaire après création
        setFormData({
          clientNumber: '',
          clientName: '',
          accountType: '',
          agency: '',
          currency: 'XAF',
          minimumBalance: 0,
          commissionRate: 0.01,
          allowOverdraft: false,
          overdraftLimit: 0,
          sendSmsNotifications: true,
          status: 'active',
        });
      }

      // Redirection après un délai
      setTimeout(() => {
        navigate('/accounts');
      }, 2000);
    } catch (error) {
      console.error('Error saving account:', error);
      setError('Erreur lors de la sauvegarde du compte');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => {
    navigate('/accounts');
  };

  if (loading) {
    return (
      <Box sx={{ textAlign: 'center', py: 4 }}>
        <Typography>Chargement...</Typography>
      </Box>
    );
  }

  return (
    <Paper sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
        <AccountIcon sx={{ mr: 2, color: 'primary.main' }} />
        <Typography variant="h5">
          {accountId ? 'Modifier le compte' : 'Créer un nouveau compte'}
        </Typography>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert severity="success" sx={{ mb: 2 }}>
          {success}
        </Alert>
      )}

      <form onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              label="Numéro client"
              name="clientNumber"
              value={formData.clientNumber}
              onChange={handleChange}
              required
              disabled={accountId !== null}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              label="Nom du client"
              name="clientName"
              value={formData.clientName}
              onChange={handleChange}
              required
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth required>
              <InputLabel>Type de compte</InputLabel>
              <Select
                name="accountType"
                value={formData.accountType}
                onChange={handleChange}
                label="Type de compte"
              >
                <MenuItem value="">
                  <em>Sélectionner un type</em>
                </MenuItem>
                {accountTypes.map((type) => (
                  <MenuItem key={type.code} value={type.code}>
                    {type.label} ({type.number})
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth required>
              <InputLabel>Agence</InputLabel>
              <Select
                name="agency"
                value={formData.agency}
                onChange={handleChange}
                label="Agence"
              >
                <MenuItem value="">
                  <em>Sélectionner une agence</em>
                </MenuItem>
                {agencies.map((agency) => (
                  <MenuItem key={agency.code} value={agency.code}>
                    {agency.name} ({agency.code})
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              label="Devise"
              name="currency"
              value={formData.currency}
              onChange={handleChange}
              required
              select
            >
              <MenuItem value="XAF">FCFA (XAF)</MenuItem>
              <MenuItem value="EUR">Euro (EUR)</MenuItem>
              <MenuItem value="USD">Dollar US (USD)</MenuItem>
            </TextField>
          </Grid>

          <Grid item xs={12}>
            <Divider sx={{ my: 2 }}>
              <Typography variant="body2" color="text.secondary">
                Paramètres du compte
              </Typography>
            </Divider>
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              label="Solde minimum"
              name="minimumBalance"
              type="number"
              value={formData.minimumBalance}
              onChange={handleChange}
              InputProps={{
                inputProps: { min: 0 },
              }}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              label="Taux de commission (%)"
              name="commissionRate"
              type="number"
              value={formData.commissionRate * 100}
              onChange={(e) => {
                setFormData(prev => ({
                  ...prev,
                  commissionRate: parseFloat(e.target.value) / 100,
                }));
              }}
              InputProps={{
                inputProps: { min: 0, max: 100, step: 0.01 },
              }}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControlLabel
              control={
                <Switch
                  checked={formData.allowOverdraft}
                  onChange={handleChange}
                  name="allowOverdraft"
                />
              }
              label="Autoriser le découvert"
            />
          </Grid>

          {formData.allowOverdraft && (
            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="Limite de découvert"
                name="overdraftLimit"
                type="number"
                value={formData.overdraftLimit}
                onChange={handleChange}
                InputProps={{
                  inputProps: { min: 0 },
                }}
              />
            </Grid>
          )}

          <Grid item xs={12} md={6}>
            <FormControlLabel
              control={
                <Switch
                  checked={formData.sendSmsNotifications}
                  onChange={handleChange}
                  name="sendSmsNotifications"
                />
              }
              label="Notifications SMS"
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>Statut</InputLabel>
              <Select
                name="status"
                value={formData.status}
                onChange={handleChange}
                label="Statut"
              >
                <MenuItem value="active">Actif</MenuItem>
                <MenuItem value="pending">En attente</MenuItem>
                <MenuItem value="blocked">Bloqué</MenuItem>
                <MenuItem value="closed">Fermé</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12}>
            <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end', mt: 3 }}>
              <Button
                variant="outlined"
                startIcon={<CancelIcon />}
                onClick={handleCancel}
                disabled={submitting}
              >
                Annuler
              </Button>
              <LoadingButton
                type="submit"
                variant="contained"
                startIcon={<SaveIcon />}
                loading={submitting}
                loadingPosition="start"
              >
                {accountId ? 'Mettre à jour' : 'Créer le compte'}
              </LoadingButton>
            </Box>
          </Grid>
        </Grid>
      </form>
    </Paper>
  );
};

export default AccountForm;