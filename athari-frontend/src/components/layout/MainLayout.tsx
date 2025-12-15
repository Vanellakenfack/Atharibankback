import React, { useState } from 'react';
<<<<<<< HEAD
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
=======
import { Menu, ChevronDown, Home, CreditCard, Users, Settings, BarChart3 } from 'lucide-react';
import Header from './Header';

interface MainLayoutProps {
  children: React.ReactNode;
}

const MainLayout: React.FC<MainLayoutProps> = ({ children }) => {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [expandedMenu, setExpandedMenu] = useState<string | null>(null);

  const menuItems = [
    { id: 'dashboard', label: 'Tableau de bord', icon: Home, href: '/' },
    { 
      id: 'comptes', 
      label: 'Comptes',
      icon: CreditCard,
      submenu: [
        { label: 'Mes comptes', href: '/comptes' },
        { label: 'Nouveau compte', href: '/comptes/nouveau' }
      ]
    },
    { id: 'clients', label: 'Clients', icon: Users, href: '/clients' },
    { 
      id: 'rapports', 
      label: 'Rapports',
      icon: BarChart3,
      submenu: [
        { label: 'Transactions', href: '/rapports/transactions' },
        { label: 'Statistiques', href: '/rapports/stats' }
      ]
    },
    { id: 'settings', label: 'Paramètres', icon: Settings, href: '/settings' }
  ];

  const toggleSubmenu = (id: string) => {
    setExpandedMenu(expandedMenu === id ? null : id);
  };

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar */}
      <div
        className={`${
          sidebarOpen ? 'w-64' : 'w-20'
        } bg-gray-900 text-white transition-all duration-300 ease-in-out flex flex-col shadow-lg`}
      >
        {/* Sidebar Header */}
        <div className="p-6 border-b border-gray-800 flex items-center justify-between">
          {sidebarOpen && <h1 className="text-xl font-bold">Menu</h1>}
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="p-1 hover:bg-gray-800 rounded transition"
          >
            <Menu size={20} />
          </button>
        </div>

        {/* Sidebar Menu */}
        <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
          {menuItems.map((item) => (
            <div key={item.id}>
              {item.submenu ? (
                <>
                  <button
                    onClick={() => toggleSubmenu(item.id)}
                    className="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition group"
                  >
                    <item.icon size={20} className="flex-shrink-0" />
                    {sidebarOpen && (
                      <>
                        <span className="flex-1 text-left">{item.label}</span>
                        <ChevronDown
                          size={16}
                          className={`transition-transform ${
                            expandedMenu === item.id ? 'rotate-180' : ''
                          }`}
                        />
                      </>
                    )}
                  </button>
                  {sidebarOpen && expandedMenu === item.id && (
                    <div className="pl-4 space-y-1">
                      {item.submenu.map((sub, idx) => (
                        <a
                          key={idx}
                          href={sub.href}
                          className="block px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 rounded transition"
                        >
                          {sub.label}
                        </a>
                      ))}
                    </div>
                  )}
                </>
              ) : (
                <a
                  href={item.href}
                  className="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition"
                >
                  <item.icon size={20} className="flex-shrink-0" />
                  {sidebarOpen && <span>{item.label}</span>}
                </a>
              )}
            </div>
          ))}
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header onToggleSidebar={() => setSidebarOpen(!sidebarOpen)} />
        <main className="flex-1 overflow-auto p-6">
          {children}
>>>>>>> dev
        </main>
      </div>
    </div>
  );
};

export default MainLayout;
