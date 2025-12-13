import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';

const drawerWidthClass = 'w-60'; // ~240px

const MainLayout: React.FC = () => {
  const [mobileOpen, setMobileOpen] = useState(false);

  const menuItems = [
    { text: 'Comptes', path: '/accounts' },
    { text: 'Clients', path: '/clients' },
    { text: 'Crédits', path: '/credits' },
    { text: 'Rapports', path: '/reports' },
  ];

  return (
    <div className="min-h-screen flex bg-gray-50">
      {/* Sidebar - desktop */}
      <aside className={`${drawerWidthClass} hidden md:flex flex-col bg-white border-r`}>
        <div className="h-16 flex items-center px-4 font-bold">ATHARI Banking</div>
        <nav className="flex-1 px-2 py-4">
          {menuItems.map((item) => (
            <a
              key={item.text}
              href={item.path}
              onClick={(e) => { e.preventDefault(); window.history.pushState({}, '', item.path); const navEvent = new PopStateEvent('popstate'); window.dispatchEvent(navEvent); }}
              className="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700"
            >
              <span className="w-6 text-center">•</span>
              <span>{item.text}</span>
            </a>
          ))}
        </nav>
      </aside>

      {/* Mobile sidebar overlay */}
      <div className={`${mobileOpen ? 'block' : 'hidden'} md:hidden fixed inset-0 z-40`}> 
        <div className="absolute inset-0 bg-black opacity-40" onClick={() => setMobileOpen(false)} />
        <div className="absolute left-0 top-0 bottom-0 bg-white w-64 shadow-md">
          <div className="h-16 flex items-center px-4 font-bold">ATHARI Banking</div>
          <nav className="px-2 py-4">
            {menuItems.map((item) => (
              <a
                key={item.text}
                href={item.path}
                onClick={(e) => { e.preventDefault(); setMobileOpen(false); window.history.pushState({}, '', item.path); const navEvent = new PopStateEvent('popstate'); window.dispatchEvent(navEvent); }}
                className="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700"
              >
                <span className="w-6 text-center">•</span>
                <span>{item.text}</span>
              </a>
            ))}
          </nav>
        </div>
      </div>

      {/* Main area */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <header className="h-16 bg-white border-b flex items-center px-4 md:pl-64">
          <button
            className="md:hidden mr-3 p-2 rounded hover:bg-gray-100"
            onClick={() => setMobileOpen(true)}
            aria-label="Open menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M3 5h14a1 1 0 110 2H3a1 1 0 110-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2z" clipRule="evenodd" />
            </svg>
          </button>
          <div className="text-lg font-semibold">Core Banking System</div>
        </header>

        <main className="flex-1 p-6 md:pl-64 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default MainLayout;