// Login.jsx (Ajusté)
import React from "react";
// Assurez-vous que le fichier CSS 'bubbles.css' est bien importé
import "../assets/css/bubbles.css"; 

export default function Login() {
  return (
    // Conteneur principal avec le dégradé de fond
    <div 
      className="min-h-screen flex items-center justify-center relative" 
      style={{ background: 'linear-gradient(to right, #6a11cb, #2575fc)' }}
    >
      
      {/* Bulles animées (Assurez-vous que 'bubbles.css' définit 'financial-bubbles' et 'bubble') */}
      <div className="financial-bubbles absolute inset-0 pointer-events-none">
        <div className="bubble">$</div>
        <div className="bubble">€</div>
        <div className="bubble">FCFA</div>
        <div className="bubble">£</div>
      </div>

      {/* Carte de Connexion (Style "Glassmorphism" Léger) */}
      {/* max-w-sm pour un formulaire plus compact, p-8 pour un padding généreux */}
      <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl backdrop-blur p-8 z-10">
        
        {/* Logo/Avatar */}
        <div className="text-center mb-6">
          {/* Style ajusté pour ressembler à un placeholder de logo sur la carte */}
          <div className="w-20 h-20 bg-gray-100 rounded-full mx-auto border border-gray-300 flex items-center justify-center text-lg font-bold text-indigo-700">
            {/* Si vous n'avez pas d'image, utilisez un placeholder Tailwind */}
            Logo
          </div>
        </div>

        <h2 className="text-2xl font-semibold text-gray-800 text-center">Connexion</h2>
        <p className="text-sm text-gray-600 text-center mb-6">Accédez à votre espace sécurisé</p>

        {/* Formulaire */}
        <form className="space-y-4"> {/* espace-y-4 pour plus d'espace vertical */}
          
          {/* Champ Email */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Adresse Email</label>
            <div className="relative">
              {/* input avec coins arrondis (rounded-lg) et padding ajusté (py-2.5) */}
              <input 
                type="email" 
                placeholder="exemple@atharibank.com" 
                className="w-full border border-gray-300 rounded-lg px-3 py-2.5 pl-10 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" 
              />
              <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">📧</span>
            </div>
          </div>

          {/* Champ Mot de passe */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
            <div className="relative">
              <input 
                type="password" 
                placeholder="Votre mot de passe" 
                className="w-full border border-gray-300 rounded-lg px-3 py-2.5 pl-10 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" 
              />
              <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">🔒</span>
            </div>
          </div>
          
          {/* Lien "Mot de passe oublié" (ajouté pour correspondre à l'image) */}
          <div className="text-right text-sm">
             <a href="#" className="text-blue-600 hover:text-blue-700 transition duration-150">Mot de passe oublié ?</a>
          </div>

          {/* Bouton Se connecter */}
          {/* Style du bouton avec des coins arrondis (rounded-lg) et une couleur accentuée */}
          <button 
            type="submit" 
            className="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition duration-150 shadow-md"
          >
            Se connecter
          </button>

          {/* Lien Retour à l'accueil */}
          <div className="text-center pt-2">
            <a href="/" className="text-sm text-gray-600 hover:text-gray-800 transition duration-150">← Retour à l'accueil</a>
          </div>
        </form>
      </div>
    </div>
  );
}