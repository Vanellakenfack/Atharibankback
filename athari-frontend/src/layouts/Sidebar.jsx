import React, { useState } from 'react';

function Sidebar() {
  const [activeDropdown, setActiveDropdown] = useState(null);

  const toggleDropdown = (dropdownId) => {
    setActiveDropdown(activeDropdown === dropdownId ? null : dropdownId);
  };

  return (
    <nav className="w-64 min-h-screen bg-gradient-to-b from-white to-blue-100 border-r border-gray-200 shadow-md p-6 sticky top-16 overflow-y-auto">
      <h3 className="text-blue-800 font-bold text-lg mb-8 flex items-center gap-2">
        ⚙️ Administration
      </h3>
      <ul className="space-y-2">
        <li>
          <a className="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-gradient-to-r hover:from-blue-100 hover:to-yellow-100 hover:text-blue-800 transition-all cursor-pointer relative overflow-hidden group" href="#">
            <i className="fas fa-tachometer-alt w-5 text-center text-blue-600 group-hover:text-blue-800 group-hover:scale-110 transition-all"></i>
            Dashboard
          </a>
        </li>
        <li>
          <a className="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-gradient-to-r hover:from-blue-100 hover:to-yellow-100 hover:text-blue-800 transition-all" href="/creerclients">
            <i className="fas fa-user-plus w-5 text-center text-blue-600"></i> Créer clients
          </a>
        </li>
        <li>
          <button className="w-full flex items-center justify-between px-4 py-3 rounded-xl text-gray-700 hover:bg-gradient-to-r hover:from-blue-100 hover:to-yellow-100 hover:text-blue-800 transition-all" onClick={() => toggleDropdown('creerComptes')}>
            <span className="flex items-center gap-3">
              <i className="fas fa-university w-5 text-center text-blue-600"></i>
              comptes
            </span>
            <i className={`fas fa-chevron-down transition-transform ${activeDropdown === 'creerComptes' ? 'rotate-180' : ''}`}></i>
          </button>
          <div className={`overflow-hidden transition-all duration-300 ${activeDropdown === 'creerComptes' ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'} bg-white/95 rounded-lg mt-2 shadow-md backdrop-blur-sm`}>
            <div className="p-2">
              <div className="bg-blue-600 text-white p-2 rounded mb-1 font-bold border-l-4 border-blue-800">
                <a href="/comptes/index" className="flex items-center gap-2 text-white hover:text-yellow-200">
                  <i className="fas fa-user w-4"></i> Comptes Courant
                </a>
              </div>
              <div className="bg-green-600 text-white p-2 rounded mb-1 font-bold border-l-4 border-green-800">
                <a href="/epargne/index" className="flex items-center gap-2 text-white hover:text-yellow-200">
                  <i className="fas fa-piggy-bank w-4"></i> Comptes d'Epargne
                </a>
              </div>
              <div className="bg-yellow-600 text-gray-900 p-2 rounded mb-1 font-bold border-l-4 border-yellow-800">
                <a href="/collecte/index" className="flex items-center gap-2 text-gray-900 hover:text-yellow-800">
                  <i className="fas fa-calendar-alt w-4"></i> Comptes de Collectes
                </a>
              </div>
              <div className="bg-purple-600 text-white p-2 rounded mb-1 font-bold border-l-4 border-purple-800">
                <a href="/mata/index" className="flex items-center gap-2 text-white hover:text-yellow-200">
                  <i className="fas fa-rocket w-4"></i> Mata Boost
                </a>
              </div>
              <div className="bg-red-600 text-white p-2 rounded font-bold border-l-4 border-red-800">
                <a href="/dat/index" className="flex items-center gap-2 text-white hover:text-yellow-200">
                  <i className="fas fa-clock w-4"></i> Dépots A Terme (DAT)
                </a>
              </div>
            </div>
          </div>
        </li>
        {/* Similar structure for other dropdowns: credit, depot, rapports */}
        {/* ... (truncated for brevity, but follow the same pattern) */}
      </ul>
    </nav>
  );
}

export default Sidebar;