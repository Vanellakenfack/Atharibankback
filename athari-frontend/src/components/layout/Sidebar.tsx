import React, { useState } from 'react'
import { 
  Zap, 
  ArrowLeftRight, 
  Building2, 
  FolderCog, 
  Wallet, 
  Users, 
  ShieldCheck, 
  BarChart3,
  ChevronDown,
  ChevronRight,
  Settings
} from 'lucide-react'

const menuItems = [
  {
    id: 'parametres',
    name: 'Dashboard',
    icon: Settings,
    link: '/dashboard',
    badge: 'New',
  },
  {
    id: 'front-office',
    name: 'Transactions Front office',
    icon: ArrowLeftRight,
    link: '/front-office',
    badge: 'New',
  },
  {
    id: 'back-office',
    name: 'Transactions Back office',
    icon: Building2,
    link: '/back-office',
    badge: 'New',
  },
  {
    id: 'operations',
    name: 'Opérations diverses',
    icon: FolderCog,
    link: '/operations-diverses',
    badge: 'New',
  },
  {
    id: 'prets',
    name: 'Prêts',
    icon: Wallet,
    link: '/prets',
    badge: 'New',
  },
  {
    id: 'gestion-utilisateurs',
    name: 'Gestion des utilisateurs',
    icon: Users,
    link: '/gestion-utilisateurs',
    badge: 'New',
  },
  {
    id: 'securites',
    name: 'Sécurités',
    icon: ShieldCheck,
    link: '/securites',
    badge: 'New',
  },
  {
    id: 'analytics',
    name: 'Analytics',
    icon: BarChart3,
    submenu: [
      { id: 'overview', label: 'Overview', link: '/analytics/overview' },
      { id: 'reports', label: 'Reports', link: '/analytics/reports' },
      { id: 'insights', label: 'Insights', link: '/analytics/insights' },
    ]
  },
]

function Sidebar({ collapsed, onToggle, currentPage, onPageChange }) {
  const [activeItem, setActiveItem] = useState('parametres')
  const [openSubmenu, setOpenSubmenu] = useState(null)

  const handleItemClick = (item) => {
    if (item.submenu) {
      setOpenSubmenu(openSubmenu === item.id ? null : item.id)
    } else {
      setActiveItem(item.id)
      // Optionnel : appeler onPageChange si défini
      if (onPageChange) {
        onPageChange(item.link)
      }
    }
  }

  const handleSubmenuClick = (subitem) => {
    setActiveItem(subitem.id)
    if (onPageChange) {
      onPageChange(subitem.link)
    }
  }

  return (
    <div 
      className={`${collapsed ? "w-20" : "w-72"} h-screen transition duration-300 ease-in-out bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-r border-slate-200/50 dark:border-slate-700/50 flex flex-col relative z-10`}
    >
      
      {/* LOGO */}
      <div className="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
            <Zap className="w-6 h-6 text-white" />
          </div>
          {!collapsed && (
            <div>
              <h1 className="text-xl font-bold text-slate-800 dark:text-white">
                Athari
              </h1>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                Panneaux
              </p>
            </div>
          )}
        </div>
      </div>

      {/* NAVIGATION */}
      <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
        {menuItems.map((item) => {
          const Icon = item.icon
          const isActive = activeItem === item.id
          const isOpen = openSubmenu === item.id

          return (
            <div key={item.id}>
              <button 
                onClick={() => handleItemClick(item)}
                className={`w-full flex items-center justify-between p-3 rounded-xl 
                  transition-all duration-200 group
                  ${isActive 
                    ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' 
                    : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300'
                  }`}
              >
                <div className="flex items-center space-x-3">
                  <Icon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-slate-500 dark:text-slate-400'}`} />
                  {!collapsed && (
                    <span className="font-medium text-sm">{item.name}</span>
                  )}
                </div>
                
                {!collapsed && (
                  <div className="flex items-center space-x-2">
                    {item.badge && (
                      <span className={`px-2 py-0.5 text-xs rounded-full 
                        ${isActive 
                          ? 'bg-white/20 text-white' 
                          : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                        }`}>
                        {item.badge}
                      </span>
                    )}
                    
                    {item.submenu && (
                      <ChevronDown 
                        className={`w-4 h-4 transition-transform duration-200 ${isOpen ? 'rotate-0' : '-rotate-90'}`} 
                      />
                    )}
                  </div>
                )}
              </button>

              {/* Sous-menu */}
              {item.submenu && isOpen && !collapsed && (
                <div className="ml-8 mt-2 space-y-1">
                  {item.submenu.map((subitem) => (
                    <button
                      key={subitem.id}
                      onClick={() => handleSubmenuClick(subitem)}
                      className={`w-full text-left p-2 pl-4 rounded-lg text-sm transition-all duration-200
                        ${activeItem === subitem.id
                          ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                          : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                        }`}
                    >
                      {subitem.label}
                    </button>
                  ))}
                </div>
              )}
            </div>
          )
        })}
      </nav>

      {/* PROFILE */}
      <div className="p-4 border-t border-slate-200/50 dark:border-slate-700/50">
        <div className="flex items-center space-x-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
          <img 
            src="https://ui-avatars.com/api/?name=Steve&background=6366f1&color=fff" 
            alt="utilisateur"
            className="w-10 h-10 rounded-full ring-2 ring-blue-500" 
          />
          {!collapsed && (
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-800 dark:text-white truncate">
                Steve
              </p>
              <p className="text-xs text-slate-500 dark:text-slate-400 truncate">
                Utilisateur
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default Sidebar