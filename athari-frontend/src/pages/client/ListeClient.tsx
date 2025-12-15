<<<<<<< HEAD:athari-frontend/src/pages/client/ListeClient.jsx
import React, { useState, useEffect } from 'react';
import { Eye, Edit2, Trash2, Plus, Search, ChevronDown, ChevronUp, Filter } from 'lucide-react';
=======
import React, { useState } from 'react';
import {
    Paper, Table, TableBody, TableCell, TableContainer,
    TableHead, TableRow, TableSortLabel, TablePagination, Box,
    IconButton, Typography, TextField, InputAdornment, useTheme,
    Button,
} from '@mui/material';
import Header from '../../components/layout/Header';
import { visuallyHidden } from '@mui/utils';
import {
    Edit as EditIcon,
    Visibility as VisibilityIcon,
    Delete as DeleteIcon,
    Search as SearchIcon,
    Add as AddIcon,
} from '@mui/icons-material';
// 1. IMPORT DU HOOK DE NAVIGATION
import { useNavigate } from 'react-router-dom'; 
>>>>>>> 09f7f520819d17b8f5bd2c7cfcce97e473c264b0:athari-frontend/src/pages/client/ListeClient.tsx

const ListeClient = () => {
  const [clients, setClients] = useState([]);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [order, setOrder] = useState('asc');
  const [orderBy, setOrderBy] = useState('nom');
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    const dummyClients = [
      { id: 1, nom: 'Dupont', prenom: 'Jean', email: 'jean.dupont@example.com', telephone: '+33 6 12 34 56 78', status: 'Actif' },
      { id: 2, nom: 'Martin', prenom: 'Marie', email: 'marie.martin@example.com', telephone: '+33 6 98 76 54 32', status: 'Actif' },
      { id: 3, nom: 'Bernard', prenom: 'Pierre', email: 'pierre.bernard@example.com', telephone: '+33 6 11 22 33 44', status: 'Inactif' },
      { id: 4, nom: 'Lefevre', prenom: 'Sophie', email: 'sophie.lefevre@example.com', telephone: '+33 6 55 66 77 88', status: 'Actif' },
    ];
    setClients(dummyClients);
    setLoading(false);
  }, []);

  const handleRequestSort = (property) => {
    const isAsc = orderBy === property && order === 'asc';
    setOrder(isAsc ? 'desc' : 'asc');
    setOrderBy(property);
  };

  const handleChangePage = (newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const filteredClients = clients.filter(client =>
    client.nom.toLowerCase().includes(searchTerm.toLowerCase()) ||
    client.prenom.toLowerCase().includes(searchTerm.toLowerCase()) ||
    client.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const visibleRows = filteredClients.slice(
    page * rowsPerPage,
    page * rowsPerPage + rowsPerPage
  );

  if (loading) {
    return (
      <div className="h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-16 w-16 border-4 border-blue-600 border-t-transparent"></div>
      </div>
    );
  }

  const totalPages = Math.ceil(filteredClients.length / rowsPerPage);

  return (
    <div className="h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 overflow-hidden flex flex-col">
      <div className="flex-1 flex flex-col max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
          <div className="animate-fade-in">
            <h1 className="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 to-indigo-600 mb-2">
            
            </h1>
          </div>
          <button className="group relative px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white rounded-2xl flex items-center gap-3 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <Plus size={22} className="relative z-10" />
            <span className="relative z-10 font-semibold">Nouveau Client</span>
          </button>
        </div>

        {/* Search and Filter Bar */}
        <div className="mb-6 animate-fade-in animation-delay-200">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-5 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
              <input
                type="text"
                placeholder="t..."
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value);
                  setPage(0);
                }}
                className="w-full pl-14 pr-6 py-4 border-0 rounded-2xl focus:outline-none focus:ring-4 focus:ring-blue-100 bg-white/80 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl text-slate-700 placeholder-slate-400"
              />
            </div>
            <button className="px-6 py-4 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-3 text-slate-700 hover:text-blue-600">
              <Filter size={20} />
              <span className="font-medium">Filtres</span>
            </button>
          </div>
        </div>

        {/* Table Card */}
        <div className="flex-1 bg-white/90 backdrop-blur-sm rounded-3xl shadow-2xl overflow-hidden border border-white/20 animate-fade-in animation-delay-400 flex flex-col">
          {/* Table */}
          <div className="flex-1 overflow-auto">
            <table className="w-full">
              <thead className="sticky top-0 bg-gradient-to-r from-blue-50 to-indigo-50 border-b-2 border-blue-100 z-10">
                <tr>
                  <th 
                    className="px-6 py-5 text-left font-bold text-slate-700 cursor-pointer hover:bg-blue-100 transition-all duration-200 group"
                    onClick={() => handleRequestSort('nom')}
                  >
                    <div className="flex items-center gap-2">
                      <span>Nom</span>
                      {orderBy === 'nom' ? (
                        order === 'asc' ? <ChevronUp size={16} className="text-blue-600" /> : <ChevronDown size={16} className="text-blue-600" />
                      ) : (
                        <ChevronDown size={16} className="text-slate-400 group-hover:text-slate-600 transition-colors" />
                      )}
                    </div>
                  </th>
                  <th className="px-6 py-5 text-left font-bold text-slate-700">Prénom</th>
                  <th className="px-6 py-5 text-left font-bold text-slate-700">Email</th>
                  <th className="px-6 py-5 text-left font-bold text-slate-700">Téléphone</th>
                  <th className="px-6 py-5 text-center font-bold text-slate-700">Statut</th>
                  <th className="px-6 py-5 text-center font-bold text-slate-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {visibleRows.length > 0 ? visibleRows.map((client, idx) => (
                  <tr key={client.id} className={`hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 transform hover:scale-[1.005] ${idx % 2 === 0 ? 'bg-slate-50/30' : 'bg-white/50'}`}>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                          {client.nom.charAt(0)}{client.prenom.charAt(0)}
                        </div>
                        <span className="font-semibold text-slate-900">{client.nom}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-slate-600 font-medium">{client.prenom}</td>
                    <td className="px-6 py-4">
                      <a href={`mailto:${client.email}`} className="text-blue-600 hover:text-indigo-800 hover:underline text-sm break-all transition-colors">
                        {client.email}
                      </a>
                    </td>
                    <td className="px-6 py-4 text-slate-600 font-medium">{client.telephone}</td>
                    <td className="px-6 py-4 text-center">
                      <span className={`inline-flex items-center px-4 py-2 rounded-full text-xs font-bold transition-all duration-300 shadow-sm ${
                        client.status === 'Actif'
                          ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200'
                          : 'bg-gradient-to-r from-slate-100 to-slate-200 text-slate-700 border border-slate-300'
                      }`}>
                        <span className={`w-2 h-2 rounded-full mr-2 ${client.status === 'Actif' ? 'bg-green-500' : 'bg-slate-500'}`}></span>
                        {client.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center items-center gap-2">
                        <button className="group p-3 rounded-xl bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-200 shadow-sm hover:shadow-md">
                          <Eye size={18} className="group-hover:scale-110 transition-transform" />
                        </button>
                        <button className="group p-3 rounded-xl bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:scale-110 transition-all duration-200 shadow-sm hover:shadow-md">
                          <Edit2 size={18} className="group-hover:scale-110 transition-transform" />
                        </button>
                        <button className="group p-3 rounded-xl bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-200 shadow-sm hover:shadow-md">
                          <Trash2 size={18} className="group-hover:scale-110 transition-transform" />
                        </button>
                      </div>
                    </td>
                  </tr>
                )) : (
                  <tr>
                    <td colSpan="6" className="px-6 py-16 text-center text-slate-500">
                      <div className="flex flex-col items-center">
                        <Search size={48} className="text-slate-300 mb-4" />
                        <p className="text-lg font-medium">Aucun client trouvé</p>
                        <p className="text-sm">Essayez de modifier vos critères de recherche</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t bg-gradient-to-r from-slate-50 to-blue-50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <label className="text-sm font-medium text-slate-700">Lignes par page:</label>
              <select
                value={rowsPerPage}
                onChange={handleChangeRowsPerPage}
                className="px-4 py-2 border-0 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white/80 backdrop-blur-sm hover:bg-white transition-all duration-200 shadow-sm"
              >
                <option value={5}>5</option>
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
              </select>
            </div>
            <div className="text-sm font-medium text-slate-700 bg-white/60 px-4 py-2 rounded-xl shadow-sm">
              Page <span className="font-bold text-blue-600">{page + 1}</span> sur <span className="font-bold">{totalPages || 1}</span> • <span className="text-blue-600 font-bold">{filteredClients.length}</span> client(s)
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => handleChangePage(page - 1)}
                disabled={page === 0}
                className="px-6 py-3 border-0 rounded-xl text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed bg-white/80 backdrop-blur-sm hover:bg-white hover:scale-105 transition-all duration-200 text-slate-700 disabled:hover:bg-white/80 shadow-sm hover:shadow-md"
              >
                ← Précédent
              </button>
              <button
                onClick={() => handleChangePage(page + 1)}
                disabled={page >= totalPages - 1}
                className="px-6 py-3 border-0 rounded-xl text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed bg-white/80 backdrop-blur-sm hover:bg-white hover:scale-105 transition-all duration-200 text-slate-700 disabled:hover:bg-white/80 shadow-sm hover:shadow-md"
              >
                Suivant →
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ListeClient;