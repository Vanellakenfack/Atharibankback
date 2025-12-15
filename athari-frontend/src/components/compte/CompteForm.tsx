<<<<<<< HEAD
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
=======
import React, { useState } from 'react';
>>>>>>> dev

interface CompteFormProps {
  onSubmit?: (formData: any) => void;
  initialData?: any;
}

const CompteForm: React.FC<CompteFormProps> = ({ onSubmit, initialData }) => {
  const [formData, setFormData] = useState(initialData || {
    numero: '',
    type: '',
    solde: 0,
    devise: 'EUR',
    description: ''
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit?.(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">Numéro de compte</label>
        <input
          type="text"
          name="numero"
          value={formData.numero}
          onChange={handleChange}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
          placeholder="Ex: 2024001"
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
          <select
            name="type"
            value={formData.type}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600 bg-white"
          >
            <option value="">Sélectionner...</option>
            <option value="courant">Courant</option>
            <option value="epargne">Épargne</option>
            <option value="titre">Titre</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Devise</label>
          <select
            name="devise"
            value={formData.devise}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600 bg-white"
          >
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
            <option value="GBP">GBP</option>
          </select>
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">Solde initial</label>
        <input
          type="number"
          name="solde"
          value={formData.solde}
          onChange={handleChange}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
          placeholder="0.00"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <textarea
          name="description"
          value={formData.description}
          onChange={handleChange}
          rows={4}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
          placeholder="Ajoutez une description..."
        />
      </div>

<<<<<<< HEAD
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
=======
      <div className="flex gap-3">
        <button
          type="submit"
          className="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition"
        >
          Valider
        </button>
        <button
          type="button"
          className="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition"
        >
          Annuler
        </button>
      </div>
    </form>
>>>>>>> dev
  );
};

export default CompteForm;
