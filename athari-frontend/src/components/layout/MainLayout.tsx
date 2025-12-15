import React, { useState } from 'react';
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
        </main>
      </div>
    </div>
  );
};

export default MainLayout;
