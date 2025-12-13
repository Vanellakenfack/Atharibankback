import React from 'react';

const AccountFilters = ({ filters, onFilterChange, onReset }) => {
  const handleChange = (e) => {
    const { name, value } = e.target;
    onFilterChange({ ...filters, [name]: value });
  };

  const handleSearch = (e) => {
    if (e.key === 'Enter') {
      onFilterChange({ ...filters });
    }
  };

  return (
    <div className="mb-3 p-3 bg-white border rounded">
      <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <div className="md:col-span-4">
          <label className="block text-sm font-medium text-gray-700">Rechercher</label>
          <div className="mt-1 relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M10 2a8 8 0 105.292 14.292l4.207 4.207 1.414-1.414-4.207-4.207A8 8 0 0010 2zM4 10a6 6 0 1112 0A6 6 0 014 10z"/></svg>
            </div>
            <input
              name="search"
              value={filters.search || ''}
              onChange={handleChange}
              onKeyPress={handleSearch}
              placeholder="Numéro, client, compte..."
              className="block w-full pl-10 pr-3 py-2 border rounded-md"
            />
          </div>
        </div>

        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700">Type de compte</label>
          <select name="accountType" value={filters.accountType || ''} onChange={handleChange} className="mt-1 block w-full border rounded-md px-3 py-2">
            <option value="">Tous</option>
            <option value="10">Compte courant</option>
            <option value="23">Mata journalier</option>
            <option value="22">Mata boost bloqué</option>
            <option value="07">Épargne bloquée</option>
          </select>
        </div>

        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700">Agence</label>
          <select name="agency" value={filters.agency || ''} onChange={handleChange} className="mt-1 block w-full border rounded-md px-3 py-2">
            <option value="">Toutes</option>
            <option value="001">001 - RÉUSSITE</option>
            <option value="002">002 - AUDACE</option>
            <option value="003">003 - SPEED</option>
            <option value="004">004 - POWER</option>
            <option value="005">005 - IMANI</option>
          </select>
        </div>

        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700">Statut</label>
          <select name="status" value={filters.status || ''} onChange={handleChange} className="mt-1 block w-full border rounded-md px-3 py-2">
            <option value="">Tous</option>
            <option value="active">Actif</option>
            <option value="pending">En attente</option>
            <option value="blocked">Bloqué</option>
            <option value="closed">Fermé</option>
          </select>
        </div>

        <div className="md:col-span-2 flex items-center gap-2">
          <button onClick={() => onFilterChange(filters)} className="px-3 py-2 bg-blue-600 text-white rounded-md">Filtrer</button>
          <button onClick={onReset} title="Réinitialiser les filtres" className="p-2 rounded-md hover:bg-gray-100">
            <svg className="h-5 w-5 text-gray-600" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6L18 18M6 18L18 6" /></svg>
          </button>
        </div>
      </div>
    </div>
  );
};

export default AccountFilters;