import React, { useState, useEffect } from 'react';
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [accountId]);

  const loadAccountData = async () => {
    setLoading(true);
    try {
      const account = await accountService.getAccountById(accountId);
      if (account) setFormData(account);
    } catch (err) {
      console.error('Error loading account:', err);
      setError('Erreur lors du chargement du compte');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setSuccess('');
    try {
      if (accountId) {
        await accountService.updateAccount(accountId, formData);
        setSuccess('Compte mis à jour avec succès');
      } else {
        await accountService.createAccount(formData);
        setSuccess('Compte créé avec succès');
        setFormData({
          clientNumber: '', clientName: '', accountType: '', agency: '', currency: 'XAF',
          minimumBalance: 0, commissionRate: 0.01, allowOverdraft: false, overdraftLimit: 0,
          sendSmsNotifications: true, status: 'active',
        });
      }
      setTimeout(() => navigate('/accounts'), 1200);
    } catch (err) {
      console.error('Error saving account:', err);
      setError('Erreur lors de la sauvegarde du compte');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => navigate('/accounts');

  if (loading) return <div className="py-8 text-center">Chargement...</div>;

  return (
    <div className="bg-white p-6 rounded shadow">
      <div className="flex items-center mb-4">
        <div className="text-2xl mr-3">🏦</div>
        <h3 className="text-xl font-semibold">{accountId ? 'Modifier le compte' : 'Créer un nouveau compte'}</h3>
      </div>

      {error && <div className="mb-3 text-red-700 bg-red-100 p-3 rounded">{error}</div>}
      {success && <div className="mb-3 text-green-700 bg-green-100 p-3 rounded">{success}</div>}

      <form onSubmit={handleSubmit}>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm text-gray-600 mb-1">Numéro client</label>
            <input name="clientNumber" value={formData.clientNumber} onChange={handleChange} required disabled={accountId !== null}
              className="w-full border rounded px-3 py-2" />
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Nom du client</label>
            <input name="clientName" value={formData.clientName} onChange={handleChange} required className="w-full border rounded px-3 py-2" />
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Type de compte</label>
            <select name="accountType" value={formData.accountType} onChange={handleChange} required className="w-full border rounded px-3 py-2">
              <option value="">-- Sélectionner --</option>
              {accountTypes.map(t => <option key={t.code} value={t.code}>{t.label} ({t.number})</option>)}
            </select>
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Agence</label>
            <select name="agency" value={formData.agency} onChange={handleChange} required className="w-full border rounded px-3 py-2">
              <option value="">-- Sélectionner une agence --</option>
              {agencies.map(a => <option key={a.code} value={a.code}>{a.name} ({a.code})</option>)}
            </select>
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Devise</label>
            <select name="currency" value={formData.currency} onChange={handleChange} className="w-full border rounded px-3 py-2">
              <option value="XAF">FCFA (XAF)</option>
              <option value="EUR">Euro (EUR)</option>
              <option value="USD">Dollar US (USD)</option>
            </select>
          </div>

          <div className="md:col-span-2">
            <div className="mt-4 mb-2 text-sm text-gray-600 font-medium">Paramètres du compte</div>
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Solde minimum</label>
            <input name="minimumBalance" type="number" min={0} value={formData.minimumBalance} onChange={handleChange} className="w-full border rounded px-3 py-2" />
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Taux de commission (%)</label>
            <input name="commissionRate" type="number" min={0} max={100} step={0.01} value={formData.commissionRate * 100}
              onChange={(e) => setFormData(prev => ({ ...prev, commissionRate: parseFloat(e.target.value) / 100 }))}
              className="w-full border rounded px-3 py-2" />
          </div>

          <div className="flex items-center gap-2">
            <input id="allowOverdraft" name="allowOverdraft" type="checkbox" checked={formData.allowOverdraft} onChange={handleChange} className="w-4 h-4" />
            <label htmlFor="allowOverdraft" className="text-sm text-gray-700">Autoriser le découvert</label>
          </div>

          {formData.allowOverdraft && (
            <div>
              <label className="block text-sm text-gray-600 mb-1">Limite de découvert</label>
              <input name="overdraftLimit" type="number" min={0} value={formData.overdraftLimit} onChange={handleChange} className="w-full border rounded px-3 py-2" />
            </div>
          )}

          <div className="flex items-center gap-2">
            <input id="sendSmsNotifications" name="sendSmsNotifications" type="checkbox" checked={formData.sendSmsNotifications} onChange={handleChange} className="w-4 h-4" />
            <label htmlFor="sendSmsNotifications" className="text-sm text-gray-700">Notifications SMS</label>
          </div>

          <div>
            <label className="block text-sm text-gray-600 mb-1">Statut</label>
            <select name="status" value={formData.status} onChange={handleChange} className="w-full border rounded px-3 py-2">
              <option value="active">Actif</option>
              <option value="pending">En attente</option>
              <option value="blocked">Bloqué</option>
              <option value="closed">Fermé</option>
            </select>
          </div>

          <div className="md:col-span-2 flex justify-end gap-3 mt-4">
            <button type="button" onClick={handleCancel} disabled={submitting} className="px-4 py-2 border rounded">Annuler</button>
            <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded flex items-center gap-2" disabled={submitting}>
              {submitting && <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />}
              <span>{accountId ? 'Mettre à jour' : 'Créer le compte'}</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  );
};

export default AccountForm;