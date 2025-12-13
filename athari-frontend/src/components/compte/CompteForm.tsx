import React, { useEffect } from 'react';
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
                      return (
                        <div>
                          <h2 className="text-2xl font-bold mb-4">{isEdit ? 'Modifier le Compte' : 'Créer un Nouveau Compte'}</h2>

                          {error && (
                            <div className="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-800">{error}</div>
                          )}

                          <div className="bg-white shadow rounded p-6">
                            {/* Simple stepper */}
                            <div className="mb-6">
                              <ol className="flex items-center gap-4">
                                {steps.map((label, idx) => (
                                  <li key={label} className="flex items-center gap-3">
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm ${idx === activeStep ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}>{idx + 1}</div>
                                    <span className={`text-sm ${idx === activeStep ? 'text-gray-900 font-medium' : 'text-gray-500'}`}>{label}</span>
                                  </li>
                                ))}
                              </ol>
                            </div>

                            <form onSubmit={formik.handleSubmit}>
                              {activeStep === 0 && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">ID Client</label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-6 8a6 6 0 1112 0H4z"/></svg>
                                      </div>
                                      <input
                                        id="clientId"
                                        name="clientId"
                                        value={formik.values.clientId}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pl-10 pr-3 py-2 border rounded-md"
                                      />
                                    </div>
                                    {formik.touched.clientId && formik.errors.clientId && (<p className="mt-1 text-sm text-red-600">{formik.errors.clientId}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Nom Complet du Client</label>
                                    <input
                                      id="clientName"
                                      name="clientName"
                                      value={formik.values.clientName}
                                      onChange={formik.handleChange}
                                      onBlur={formik.handleBlur}
                                      className="mt-1 block w-full border rounded-md px-3 py-2"
                                    />
                                    {formik.touched.clientName && formik.errors.clientName && (<p className="mt-1 text-sm text-red-600">{formik.errors.clientName}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Catégorie de Client</label>
                                    <select
                                      id="category"
                                      name="category"
                                      value={formik.values.category}
                                      onChange={formik.handleChange}
                                      onBlur={formik.handleBlur}
                                      className="mt-1 block w-full border rounded-md px-3 py-2"
                                    >
                                      {categories.map(c => (<option key={c.value} value={c.value}>{c.label}</option>))}
                                    </select>
                                    {formik.touched.category && formik.errors.category && (<p className="mt-1 text-sm text-red-600">{formik.errors.category}</p>)}
                                  </div>
                                </div>
                              )}

                              {activeStep === 1 && (
                                <div className="grid grid-cols-1 gap-4">
                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Type de Compte</label>
                                    <select
                                      id="type"
                                      name="type"
                                      value={formik.values.type}
                                      onChange={formik.handleChange}
                                      onBlur={formik.handleBlur}
                                      className="mt-1 block w-full border rounded-md px-3 py-2"
                                    >
                                      {accountTypes.map((t) => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                      ))}
                                    </select>
                                    {formik.touched.type && formik.errors.type && (<p className="mt-1 text-sm text-red-600">{formik.errors.type}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Devise</label>
                                    <select
                                      id="currency"
                                      name="currency"
                                      value={formik.values.currency}
                                      onChange={formik.handleChange}
                                      onBlur={formik.handleBlur}
                                      className="mt-1 block w-full border rounded-md px-3 py-2"
                                    >
                                      {currencies.map(c => (<option key={c.value} value={c.value}>{c.label} ({c.symbol})</option>))}
                                    </select>
                                    {formik.touched.currency && formik.errors.currency && (<p className="mt-1 text-sm text-red-600">{formik.errors.currency}</p>)}
                                  </div>

                                  <div className="bg-gray-50 border rounded p-3">
                                    <div className="text-sm font-medium text-gray-700">Description du type de compte sélectionné:</div>
                                    <div className="text-sm text-gray-600 mt-1">{accountTypes.find(t => t.value === formik.values.type)?.description}</div>
                                  </div>
                                </div>
                              )}

                              {activeStep === 2 && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Solde Initial</label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1v2a7 7 0 017 7h2a9 9 0 00-9-9z"/></svg>
                                      </div>
                                      <input
                                        id="initialBalance"
                                        name="initialBalance"
                                        type="number"
                                        value={formik.values.initialBalance}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pl-10 pr-12 py-2 border rounded-md"
                                      />
                                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">{currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}</div>
                                    </div>
                                    {formik.touched.initialBalance && formik.errors.initialBalance && (<p className="mt-1 text-sm text-red-600">{formik.errors.initialBalance}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Taux d'Intérêt (%)</label>
                                    <div className="mt-1 relative">
                                      <input
                                        id="interestRate"
                                        name="interestRate"
                                        type="number"
                                        value={formik.values.interestRate}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pr-10 py-2 border rounded-md"
                                      />
                                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">%</div>
                                    </div>
                                    {formik.touched.interestRate && formik.errors.interestRate && (<p className="mt-1 text-sm text-red-600">{formik.errors.interestRate}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Frais Mensuels</label>
                                    <div className="mt-1 relative">
                                      <input
                                        id="monthlyFees"
                                        name="monthlyFees"
                                        type="number"
                                        value={formik.values.monthlyFees}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pr-10 py-2 border rounded-md"
                                      />
                                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">{currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}</div>
                                    </div>
                                    {formik.touched.monthlyFees && formik.errors.monthlyFees && (<p className="mt-1 text-sm text-red-600">{formik.errors.monthlyFees}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Solde Minimum</label>
                                    <div className="mt-1 relative">
                                      <input
                                        id="minimumBalance"
                                        name="minimumBalance"
                                        type="number"
                                        value={formik.values.minimumBalance}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pr-10 py-2 border rounded-md"
                                      />
                                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">{currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}</div>
                                    </div>
                                    {formik.touched.minimumBalance && formik.errors.minimumBalance && (<p className="mt-1 text-sm text-red-600">{formik.errors.minimumBalance}</p>)}
                                  </div>

                                  <div>
                                    <label className="block text-sm font-medium text-gray-700">Limite de Retrait</label>
                                    <div className="mt-1 relative">
                                      <input
                                        id="withdrawalLimit"
                                        name="withdrawalLimit"
                                        type="number"
                                        value={formik.values.withdrawalLimit}
                                        onChange={formik.handleChange}
                                        onBlur={formik.handleBlur}
                                        className="block w-full pr-10 py-2 border rounded-md"
                                      />
                                      <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">{currencies.find(c => c.value === formik.values.currency)?.symbol || 'FCFA'}</div>
                                    </div>
                                    {formik.touched.withdrawalLimit && formik.errors.withdrawalLimit && (<p className="mt-1 text-sm text-red-600">{formik.errors.withdrawalLimit}</p>)}
                                  </div>

                                  {/* Sous-comptes MATA Boost */}
                                  {showSubAccounts && (
                                    <div className="md:col-span-2">
                                      <div className="border-t my-4"></div>
                                      <h3 className="text-lg font-medium">Sous-comptes MATA Boost</h3>
                                      <p className="text-sm text-gray-600">Répartissez le solde initial entre les différents sous-comptes (optionnel)</p>

                                      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-3">
                                        {mataSubAccounts.map(sa => (
                                          <div key={sa.id} className="border rounded p-3 bg-white">
                                            <div className="font-medium">{sa.label}</div>
                                            <div className="text-xs text-gray-500">{sa.description}</div>
                                            <input className="mt-2 block w-full border rounded-md px-2 py-1" placeholder="Montant" type="number" />
                                          </div>
                                        ))}
                                      </div>
                                    </div>
                                  )}

                                  <div className="md:col-span-2">
                                    <div className="border rounded p-4 bg-gray-50">
                                      <div className="text-sm text-gray-600">Récapitulatif</div>
                                      <div className="grid grid-cols-2 gap-4 mt-3">
                                        <div>
                                          <div className="text-xs text-gray-500">Client</div>
                                          <div className="font-medium">{formik.values.clientName}</div>
                                        </div>
                                        <div>
                                          <div className="text-xs text-gray-500">Type de Compte</div>
                                          <div className="font-medium">{accountTypes.find(t => t.value === formik.values.type)?.label}</div>
                                        </div>
                                        <div>
                                          <div className="text-xs text-gray-500">Solde Initial</div>
                                          <div className="font-medium text-blue-600">{Number(formik.values.initialBalance).toLocaleString()} {currencies.find(c => c.value === formik.values.currency)?.symbol}</div>
                                        </div>
                                        <div>
                                          <div className="text-xs text-gray-500">Taux d'Intérêt</div>
                                          <div className="font-medium">{formik.values.interestRate}%</div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              )}
                              {/* Navigation buttons */}
                              <div className="flex items-center justify-between mt-6">
                                <button type="button" onClick={handleCancel} disabled={isSubmitting} className="px-4 py-2 border rounded-md text-sm">Annuler</button>

                                <div className="flex items-center gap-2">
                                  {activeStep > 0 && (
                                    <button type="button" onClick={handleBack} disabled={isSubmitting} className="px-4 py-2 border rounded-md text-sm">Retour</button>
                                  )}

                                  {activeStep < steps.length - 1 ? (
                                    <button type="button" onClick={handleNext} disabled={!formik.isValid || isSubmitting} className="px-4 py-2 bg-blue-600 text-white rounded-md text-sm">Suivant</button>
                                  ) : (
                                    <button type="submit" disabled={!formik.isValid || isSubmitting} className="px-4 py-2 bg-blue-600 text-white rounded-md text-sm">{isSubmitting ? 'Enregistrement...' : (isEdit ? 'Modifier' : 'Créer')}</button>
                                  )}
                                </div>
                              </div>
                            </form>
                          </div>
                        </div>
                      );
                    };

                    export default AccountForm;
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