<<<<<<< HEAD
import React from 'react';
=======
import React, { useState } from 'react';
import { Search, X, RotateCcw } from 'lucide-react';
>>>>>>> dev

interface CompteFiltersProps {
  onFilterChange?: (filters: any) => void;
}

const CompteFilters: React.FC<CompteFiltersProps> = ({ onFilterChange }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState('');

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
    onFilterChange?.({ searchTerm: e.target.value, typeFilter });
  };

  const handleTypeChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setTypeFilter(e.target.value);
    onFilterChange?.({ searchTerm, typeFilter: e.target.value });
  };

  const handleReset = () => {
    setSearchTerm('');
    setTypeFilter('');
    onFilterChange?.({ searchTerm: '', typeFilter: '' });
  };

  return (
<<<<<<< HEAD
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
=======
    <div className="bg-white rounded-lg shadow p-4 mb-6">
      <div className="flex gap-4 flex-wrap">
        <div className="flex-1 min-w-[200px] relative">
          <Search className="absolute left-3 top-3 text-gray-400" size={18} />
          <input
            type="text"
            placeholder="Chercher par numéro..."
            value={searchTerm}
            onChange={handleSearch}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
          />
        </div>
        
        <select
          value={typeFilter}
          onChange={handleTypeChange}
          className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600 bg-white"
        >
          <option value="">Tous les types</option>
          <option value="courant">Courant</option>
          <option value="epargne">Épargne</option>
          <option value="titre">Titre</option>
        </select>

        <button
          onClick={handleReset}
          className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg flex items-center gap-2 transition"
        >
          <RotateCcw size={18} />
          Réinitialiser
        </button>
>>>>>>> dev
      </div>
    </div>
  );
};

export default CompteFilters;
