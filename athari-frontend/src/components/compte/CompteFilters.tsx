import React, { useState } from 'react';
import { Search, X, RotateCcw } from 'lucide-react';

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
      </div>
    </div>
  );
};

export default CompteFilters;
