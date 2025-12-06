import React, { useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  Grid,
  TextField,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
  FormHelperText,
  Typography,
  Button,
  Stepper,
  Step,
  StepLabel,
  IconButton,
  InputAdornment,
  Divider,
  Alert,
  Paper,
  Chip,
  Stack,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  Add as AddIcon,
  Remove as RemoveIcon,
  AccountBalance as AccountIcon,
  Person as PersonIcon,
  AttachMoney as MoneyIcon,
} from '@mui/icons-material';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import {
  createAccount,
  updateAccount,
  fetchAccountById,
} from '@/store/account/accountThunks';
import {
  selectIsSubmitting,
  selectError,
  selectSelectedAccount,
} from '@/store/account/accountSelectors';
import { Account, AccountType, CreateAccountData, Currency } from '@/types/account';

// Schéma de validation Yup
const validationSchema = Yup.object({
  clientId: Yup.string().required('ID client requis'),
  clientName: Yup.string().required('Nom du client requis'),
  type: Yup.string().required('Type de compte requis'),
  currency: Yup.string().required('Devise requise'),
  initialBalance: Yup.number()
    .required('Solde initial requis')
    .min(0, 'Le solde ne peut pas être négatif'),
  category: Yup.string().required('Catégorie requise'),
  interestRate: Yup.number()
    .min(0, 'Taux minimum 0%')
    .max(100, 'Taux maximum 100%'),
  monthlyFees: Yup.number().min(0, 'Frais minimum 0'),
  minimumBalance: Yup.number().min(0, 'Solde minimum 0'),
  withdrawalLimit: Yup.number().min(0, 'Limite minimum 0'),
});

// Types de compte disponibles
const accountTypes: { value: AccountType; label: string; description: string }[] = [
  { value: 'courant', label: 'Compte Courant', description: 'Compte de dépôt avec possibilité de chèques' },
  { value: 'epargne', label: 'Compte Épargne', description: 'Compte rémunéré avec intérêts' },
  { value: 'bloque', label: 'Compte Bloqué', description: 'Compte à terme avec blocage des fonds' },
  { value: 'mata_boost', label: 'MATA Boost', description: 'Compte multi-objectifs avec sous-comptes' },
  { value: 'collecte_journaliere', label: 'Collecte Journalière', description: 'Compte pour collecte quotidienne' },
  { value: 'salaire', label: 'Compte Salaire', description: 'Compte dédié aux salaires' },
  { value: 'islamique', label: 'Compte Islamique', description: 'Compte conforme à la finance islamique' },
  { value: 'association', label: 'Compte Association', description: 'Compte pour associations' },
  { value: 'entreprise', label: 'Compte Entreprise', description: 'Compte professionnel' },
];

// Devises disponibles
const currencies: { value: Currency; label: string; symbol: string }[] = [
  { value: 'XAF', label: 'Franc CFA', symbol: 'FCFA' },
  { value: 'EUR', label: 'Euro', symbol: '€' },
  { value: 'USD', label: 'Dollar US', symbol: '$' },
];

// Catégories de client
const categories = [
  { value: 'particulier', label: 'Particulier' },
  { value: 'entreprise', label: 'Entreprise' },
  { value: 'association', label: 'Association' },
  { value: 'gouvernemental', label: 'Gouvernemental' },
];

// Sous-comptes MATA Boost
const mataSubAccounts = [
  { id: 'business', label: 'Business', description: 'Fonds pour les activités commerciales' },
  { id: 'education', label: 'Éducation', description: 'Fonds pour les frais scolaires' },
  { id: 'health', label: 'Santé', description: 'Fonds pour les dépenses médicales' },
  { id: 'celebration', label: 'Célébration', description: 'Fonds pour les événements festifs' },
  { id: 'supplies', label: 'Fournitures', description: 'Fonds pour les achats divers' },
  { id: 'realEstate', label: 'Immobilier', description: 'Fonds pour l\'immobilier' },
];

interface AccountFormProps {
  isEdit?: boolean;
}

const AccountForm: React.FC<AccountFormProps> = ({ isEdit = false }) => {
  const { id } = useParams();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const isSubmitting = useSelector(selectIsSubmitting);
  const error = useSelector(selectError);
  const selectedAccount = useSelector(selectSelectedAccount);

  const [activeStep, setActiveStep] = React.useState(0);
  const [showSubAccounts, setShowSubAccounts] = React.useState(false);

  // Étape pour les formulaires multi-étapes
  const steps = ['Informations client', 'Type de compte', 'Paramètres financiers'];

  // Initialisation des valeurs du formulaire
  const initialValues: CreateAccountData = {
    clientId: '',
    clientName: '',
    type: 'courant',
    currency: 'XAF',
    initialBalance: 0,
    category: 'particulier',
    interestRate: 0,
    monthlyFees: 0,
    minimumBalance: 0,
    withdrawalLimit: 0,
  };

  const formik = useFormik({
    initialValues,
    validationSchema,
    onSubmit: async (values) => {
      try {
        if (isEdit && id) {
          await dispatch(updateAccount({
            id,
            ...values,
          }) as any);
        } else {
          await dispatch(createAccount(values) as any);
        }
        navigate('/accounts');
      } catch (error) {
        console.error('Erreur lors de la soumission:', error);
      }
    },
  });

  // Charger les données du compte pour l'édition
  useEffect(() => {
    if (isEdit && id) {
      dispatch(fetchAccountById(id) as any);
    }
  }, [dispatch, isEdit, id]);

  // Mettre à jour les valeurs du formulaire lorsque le compte sélectionné change
  useEffect(() => {
    if (isEdit && selectedAccount) {
      formik.setValues({
        clientId: selectedAccount.clientId,
        clientName: selectedAccount.clientName,
        type: selectedAccount.type,
        currency: selectedAccount.currency,
        initialBalance: selectedAccount.balance,
        category: 'particulier', // Vous devrez ajouter cette propriété à l'interface Account
        interestRate: selectedAccount.interestRate || 0,
        monthlyFees: selectedAccount.monthlyFees || 0,
        minimumBalance: selectedAccount.minimumBalance || 0,
        withdrawalLimit: selectedAccount.withdrawalLimit || 0,
      });
    }
  }, [selectedAccount, isEdit]);

  // Gestion de l'affichage des sous-comptes MATA
  useEffect(() => {
    setShowSubAccounts(formik.values.type === 'mata_boost');
  }, [formik.values.type]);

  const handleNext = () => {
    setActiveStep((prevStep) => Math.min(prevStep + 1, steps.length - 1));
  };

  const handleBack = () => {
    setActiveStep((prevStep) => Math.max(prevStep - 1, 0));
  };

  const handleCancel = () => {
    navigate('/accounts');
  };

  return (
    <Box>
      <Typography variant="h4" gutterBottom fontWeight="bold">
        {isEdit ? 'Modifier le Compte' : 'Créer un Nouveau Compte'}
      </Typography>

      {error && (
        <Alert severity="error" sx={{ mb: 3 }}>
          {error}
        </Alert>
      )}

      <Card>
        <CardContent>
          {/* Stepper pour les formulaires multi-étapes */}
          <Stepper activeStep={activeStep} sx={{ mb: 4 }}>
            {steps.map((label) => (
              <Step key={label}>
                <StepLabel>{label}</StepLabel>
              </Step>
            ))}
          </Stepper>

          <form onSubmit={formik.handleSubmit}>
            {activeStep === 0 && (
              <Grid container spacing={3}>
                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="clientId"
                    name="clientId"
                    label="ID Client"
                    value={formik.values.clientId}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.clientId && Boolean(formik.errors.clientId)}
                    helperText={formik.touched.clientId && formik.errors.clientId}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <PersonIcon color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="clientName"
                    name="clientName"
                    label="Nom Complet du Client"
                    value={formik.values.clientName}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.clientName && Boolean(formik.errors.clientName)}
                    helperText={formik.touched.clientName && formik.errors.clientName}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <FormControl fullWidth error={formik.touched.category && Boolean(formik.errors.category)}>
                    <InputLabel>Catégorie de Client</InputLabel>
                    <Select
                      id="category"
                      name="category"
                      value={formik.values.category}
                      onChange={formik.handleChange}
                      onBlur={formik.handleBlur}
                      label="Catégorie de Client"
                    >
                      {categories.map((category) => (
                        <MenuItem key={category.value} value={category.value}>
                          {category.label}
                        </MenuItem>
                      ))}
                    </Select>
                    {formik.touched.category && formik.errors.category && (
                      <FormHelperText>{formik.errors.category}</FormHelperText>
                    )}
                  </FormControl>
                </Grid>
              </Grid>
            )}

            {activeStep === 1 && (
              <Grid container spacing={3}>
                <Grid item xs={12}>
                  <FormControl fullWidth error={formik.touched.type && Boolean(formik.errors.type)}>
                    <InputLabel>Type de Compte</InputLabel>
                    <Select
                      id="type"
                      name="type"
                      value={formik.values.type}
                      onChange={formik.handleChange}
                      onBlur={formik.handleBlur}
                      label="Type de Compte"
                    >
                      {accountTypes.map((type) => (
                        <MenuItem key={type.value} value={type.value}>
                          <Box>
                            <Typography>{type.label}</Typography>
                            <Typography variant="caption" color="text.secondary">
                              {type.description}
                            </Typography>
                          </Box>
                        </MenuItem>
                      ))}
                    </Select>
                    {formik.touched.type && formik.errors.type && (
                      <FormHelperText>{formik.errors.type}</FormHelperText>
                    )}
                  </FormControl>
                </Grid>

                <Grid item xs={12} md={6}>
                  <FormControl fullWidth error={formik.touched.currency && Boolean(formik.errors.currency)}>
                    <InputLabel>Devise</InputLabel>
                    <Select
                      id="currency"
                      name="currency"
                      value={formik.values.currency}
                      onChange={formik.handleChange}
                      onBlur={formik.handleBlur}
                      label="Devise"
                    >
                      {currencies.map((currency) => (
                        <MenuItem key={currency.value} value={currency.value}>
                          {currency.label} ({currency.symbol})
                        </MenuItem>
                      ))}
                    </Select>
                    {formik.touched.currency && formik.errors.currency && (
                      <FormHelperText>{formik.errors.currency}</FormHelperText>
                    )}
                  </FormControl>
                </Grid>

                {/* Aperçu du type de compte sélectionné */}
                <Grid item xs={12}>
                  <Paper variant="outlined" sx={{ p: 2, bgcolor: 'background.default' }}>
                    <Typography variant="subtitle2" gutterBottom>
                      Description du type de compte sélectionné:
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      {accountTypes.find(t => t.value === formik.values.type)?.description}
                    </Typography>
                  </Paper>
                </Grid>
              </Grid>
            )}

            {activeStep === 2 && (
              <Grid container spacing={3}>
                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="initialBalance"
                    name="initialBalance"
                    label="Solde Initial"
                    type="number"
                    value={formik.values.initialBalance}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.initialBalance && Boolean(formik.errors.initialBalance)}
                    helperText={formik.touched.initialBalance && formik.errors.initialBalance}
                    InputProps={{
                      endAdornment: (
                        <InputAdornment position="end">
                          {currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}
                        </InputAdornment>
                      ),
                      startAdornment: (
                        <InputAdornment position="start">
                          <MoneyIcon color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="interestRate"
                    name="interestRate"
                    label="Taux d'Intérêt (%)"
                    type="number"
                    value={formik.values.interestRate}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.interestRate && Boolean(formik.errors.interestRate)}
                    helperText={formik.touched.interestRate && formik.errors.interestRate}
                    InputProps={{
                      endAdornment: <InputAdornment position="end">%</InputAdornment>,
                    }}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="monthlyFees"
                    name="monthlyFees"
                    label="Frais Mensuels"
                    type="number"
                    value={formik.values.monthlyFees}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.monthlyFees && Boolean(formik.errors.monthlyFees)}
                    helperText={formik.touched.monthlyFees && formik.errors.monthlyFees}
                    InputProps={{
                      endAdornment: (
                        <InputAdornment position="end">
                          {currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="minimumBalance"
                    name="minimumBalance"
                    label="Solde Minimum"
                    type="number"
                    value={formik.values.minimumBalance}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.minimumBalance && Boolean(formik.errors.minimumBalance)}
                    helperText={formik.touched.minimumBalance && formik.errors.minimumBalance}
                    InputProps={{
                      endAdornment: (
                        <InputAdornment position="end">
                          {currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <TextField
                    fullWidth
                    id="withdrawalLimit"
                    name="withdrawalLimit"
                    label="Limite de Retrait"
                    type="number"
                    value={formik.values.withdrawalLimit}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    error={formik.touched.withdrawalLimit && Boolean(formik.errors.withdrawalLimit)}
                    helperText={formik.touched.withdrawalLimit && formik.errors.withdrawalLimit}
                    InputProps={{
                      endAdornment: (
                        <InputAdornment position="end">
                          {currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>

                {/* Sous-comptes MATA Boost */}
                {showSubAccounts && (
                  <Grid item xs={12}>
                    <Divider sx={{ my: 2 }} />
                    <Typography variant="h6" gutterBottom>
                      Sous-comptes MATA Boost
                    </Typography>
                    <Typography variant="body2" color="text.secondary" paragraph>
                      Répartissez le solde initial entre les différents sous-comptes (optionnel)
                    </Typography>

                    <Grid container spacing={2}>
                      {mataSubAccounts.map((subAccount) => (
                        <Grid item xs={12} sm={6} md={4} key={subAccount.id}>
                          <Paper variant="outlined" sx={{ p: 2 }}>
                            <Typography variant="subtitle2" gutterBottom>
                              {subAccount.label}
                            </Typography>
                            <Typography variant="caption" color="text.secondary" display="block" gutterBottom>
                              {subAccount.description}
                            </Typography>
                            <TextField
                              fullWidth
                              size="small"
                              label="Montant"
                              type="number"
                              InputProps={{
                                endAdornment: (
                                  <InputAdornment position="end">
                                    {currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}
                                  </InputAdornment>
                                ),
                              }}
                              sx={{ mt: 1 }}
                            />
                          </Paper>
                        </Grid>
                      ))}
                    </Grid>
                  </Grid>
                )}

                {/* Récapitulatif */}
                <Grid item xs={12}>
                  <Paper variant="outlined" sx={{ p: 3, bgcolor: 'background.default' }}>
                    <Typography variant="h6" gutterBottom>
                      Récapitulatif
                    </Typography>
                    <Grid container spacing={2}>
                      <Grid item xs={6} md={3}>
                        <Typography variant="caption" color="text.secondary">
                          Client
                        </Typography>
                        <Typography variant="body2" fontWeight="medium">
                          {formik.values.clientName}
                        </Typography>
                      </Grid>
                      <Grid item xs={6} md={3}>
                        <Typography variant="caption" color="text.secondary">
                          Type de Compte
                        </Typography>
                        <Typography variant="body2" fontWeight="medium">
                          {accountTypes.find(t => t.value === formik.values.type)?.label}
                        </Typography>
                      </Grid>
                      <Grid item xs={6} md={3}>
                        <Typography variant="caption" color="text.secondary">
                          Solde Initial
                        </Typography>
                        <Typography variant="body2" fontWeight="medium" color="primary.main">
                          {formik.values.initialBalance.toLocaleString()} {currencies.find(c => c.value === formik.values.currency)?.symbol}
                        </Typography>
                      </Grid>
                      <Grid item xs={6} md={3}>
                        <Typography variant="caption" color="text.secondary">
                          Taux d'Intérêt
                        </Typography>
                        <Typography variant="body2" fontWeight="medium">
                          {formik.values.interestRate}%
                        </Typography>
                      </Grid>
                    </Grid>
                  </Paper>
                </Grid>
              </Grid>
            )}

            {/* Boutons de navigation */}
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 4 }}>
              <Button
                startIcon={<CancelIcon />}
                onClick={handleCancel}
                disabled={isSubmitting}
              >
                Annuler
              </Button>

              <Stack direction="row" spacing={2}>
                {activeStep > 0 && (
                  <Button
                    onClick={handleBack}
                    disabled={isSubmitting}
                  >
                    Retour
                  </Button>
                )}

                {activeStep < steps.length - 1 ? (
                  <Button
                    variant="contained"
                    onClick={handleNext}
                    disabled={!formik.isValid || isSubmitting}
                  >
                    Suivant
                  </Button>
                ) : (
                  <Button
                    type="submit"
                    variant="contained"
                    startIcon={<SaveIcon />}
                    disabled={!formik.isValid || isSubmitting}
                  >
                    {isSubmitting ? 'Enregistrement...' : (isEdit ? 'Modifier' : 'Créer')}
                  </Button>
                )}
              </Stack>
            </Box>
          </form>
        </CardContent>
      </Card>
    </Box>
  );
};

export default AccountForm;