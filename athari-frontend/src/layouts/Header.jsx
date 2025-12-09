import { 
  Bell, 
  Menu, 
  Search, 
  Settings, 
  Sun, 
  Plus, 
  ChevronDown,
  LogOut // Ajout pour un bouton de déconnexion moderne
} from 'lucide-react';
import React from 'react';

/**
 * Composant Header (Barre de navigation supérieure)
 * Utilise la palette Bleu Foncé (Indigo) et Cyan Vif (Secondaire)
 */
function Header() {

  // Couleurs du thème :
  // Primaire (Bleu Foncé) : indigo-700
  // Secondaire/Vibrant (Cyan) : cyan-500
  const primaryColorClass = 'text-indigo-700 dark:text-indigo-400';
  const vibrantAccentClass = 'bg-cyan-500 hover:bg-cyan-600';
  
  return (
    // Conteneur principal : effet "flottant" avec un arrière-plan translucide et une ombre subtile
    <div className='sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md shadow-sm border-b border-slate-200/50
      dark:border-slate-800/50 px-6 py-3.5'>
      
      <div className='flex items-center justify-between'>

        {/* Section de gauche : Logo/Titre & Menu Toggle */}
        <div className='flex items-center space-x-4'>
          
          {/* Menu Toggle (pour la Sidebar) */}
          <button className='p-2 rounded-xl text-slate-600 dark:text-slate-300
            hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors'>
            <Menu className="w-5 h-5"/>
          </button>

          {/* Nom du Système/Application */}
          <div className='hidden md:block'>
            <h1 className={`text-2xl font-black ${primaryColorClass} tracking-tight`}>
              ATHARIBANK
            </h1>
          </div>
        </div>

        {/* Centre : Barre de Recherche Améliorée */}
        <div className='flex-1 max-w-xl mx-8 hidden sm:block'>
          <div className='relative'>
            <Search className='w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2
            text-slate-400'/>
            <input 
              type="text"
              placeholder="Rechercher client, transaction, compte..."
              className='w-full pl-10 pr-4 py-2 text-sm border border-slate-300 dark:border-slate-700 
              bg-slate-50 dark:bg-slate-800 rounded-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 
              transition-all duration-200 text-slate-700 dark:text-slate-200'
            />
          </div>
        </div>

        {/* Section de droite : Actions, Profil et Boutons */}
        <div className='flex items-center space-x-3'>
          
          {/* Bouton d'Action Primaire (Ajouter Client) avec Couleur Vibrante */}
          <button className={`hidden sm:flex items-center space-x-1 px-4 py-2 text-sm font-medium text-white rounded-full transition-colors ${vibrantAccentClass}`}>
            <Plus className='w-4 h-4'/>
            <span>Nouveau Client</span>
          </button>

          {/* Notifications */}
          <button className='relative p-2.5 rounded-xl text-slate-600 dark:text-slate-300
            hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors'>
            <Bell className="w-5 h-5"/>
            {/* Pastille de notification avec couleur vibrante */}
            <span className={`absolute -top-1 -right-1 w-5 h-5 text-white text-xs
            rounded-full flex items-center justify-center font-bold ${vibrantAccentClass}`}>
              3
            </span>
          </button>

          {/* Thème (Mode Jour/Nuit) */}
          <button className='p-2.5 rounded-xl text-slate-600 dark:text-slate-300
            hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors hidden sm:block'>
            <Sun className='w-5 h-5'/>
          </button>
          
          {/* Paramètres */}
          <button className='p-2.5 rounded-xl text-slate-600 dark:text-slate-300
            hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors'>
            <Settings className='w-5 h-5'/>
          </button>

          {/* Séparateur et Profil utilisateur */}
          <div className='flex items-center space-x-2 pl-3 border-l border-slate-200 dark:border-slate-700'>
            
            {/* Avatar */}
            <img 
              src="https://i.pravatar.cc/300" 
              alt="User Avatar" 
              // Anneau de couleur primaire autour de l'avatar
              className={`w-8 h-8 rounded-full ring-2 ring-indigo-500`}
            />
            
            {/* Infos Utilisateur */}
            <div className='hidden lg:block cursor-pointer'>
              <p className='text-sm font-medium text-slate-700 dark:text-slate-200'>
                user
              </p>
             
            </div>
            
            {/* Menu Déroulant du Profil */}
            <button className='p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors'>
              <ChevronDown className='w-4 h-4'/>
            </button>
          </div>

          {/* Déconnexion (Visible sur mobile ou comme icône finale) */}
          <button className='p-2.5 rounded-xl text-slate-600 dark:text-slate-300
            hover:bg-red-500 hover:text-white transition-colors block lg:hidden'>
            <LogOut className='w-5 h-5'/>
          </button>
        </div>

      </div>
    </div>
  );
}

export default Header;