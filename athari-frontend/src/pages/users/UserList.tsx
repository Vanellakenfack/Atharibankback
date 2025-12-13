import React from 'react';
import { Link } from 'react-router-dom';
import { FiUsers, FiShield, FiDatabase, FiCode } from 'react-icons/fi';

const Home = () => {
  const features = [
    {
      icon: FiUsers,
      title: 'Gestion des Utilisateurs',
      description: 'CRUD complet pour gérer les utilisateurs, leurs rôles et statuts',
      link: '/users',
      color: 'bg-blue-500'
    },
    {
      icon: FiShield,
      title: 'Gestion des Rôles',
      description: 'Configuration des permissions et rôles basée sur Spatie Laravel',
      link: '/roles',
      color: 'bg-green-500'
    },
    {
      icon: FiDatabase,
      title: 'Données Mockées',
      description: 'Données locales complètes pour tester sans backend',
      link: '/',
      color: 'bg-purple-500'
    },
    {
      icon: FiCode,
      title: 'Technologies Modernes',
      description: 'React + Tailwind CSS + React Router',
      link: '/',
      color: 'bg-yellow-500'
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
      {/* En-tête */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <div className="h-10 w-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <FiUsers className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <h1 className="text-2xl font-bold text-gray-900">CRUD Gestion Utilisateurs</h1>
                <p className="text-gray-600">Frontend React avec données mockées</p>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Section hero */}
        <div className="text-center mb-16">
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            Système de Gestion Utilisateurs & Rôles
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Interface frontend complète basée sur la structure Laravel Spatie Permissions.
            Toutes les fonctionnalités sont disponibles sans authentification.
          </p>
          <div className="mt-8 flex justify-center space-x-4">
            <Link
              to="/users"
              className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
            >
              <FiUsers className="mr-2" />
              Voir les Utilisateurs
            </Link>
            <Link
              to="/roles"
              className="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              <FiShield className="mr-2" />
              Voir les Rôles
            </Link>
          </div>
        </div>

        {/* Features */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
          {features.map((feature, index) => (
            <div key={index} className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
              <div className={`${feature.color} h-12 w-12 rounded-lg flex items-center justify-center mb-4`}>
                <feature.icon className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">{feature.title}</h3>
              <p className="text-gray-600 mb-4">{feature.description}</p>
              {feature.link !== '/' && (
                <Link
                  to={feature.link}
                  className="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center"
                >
                  Accéder →
                </Link>
              )}
            </div>
          ))}
        </div>

        {/* Données incluses */}
        <div className="bg-white rounded-lg shadow-md p-8">
          <h3 className="text-2xl font-bold text-gray-900 mb-6">Données incluses dans la démo</h3>
          
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
              <h4 className="text-lg font-semibold text-gray-900 mb-4">Utilisateurs Mockés</h4>
              <ul className="space-y-3">
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                  <span>5 utilisateurs avec différents rôles</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                  <span>Statuts actif/inactif</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                  <span>Dates de création et dernière connexion</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                  <span>Attribution multiple de rôles</span>
                </li>
              </ul>
            </div>

            <div>
              <h4 className="text-lg font-semibold text-gray-900 mb-4">Rôles & Permissions</h4>
              <ul className="space-y-3">
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-blue-500 rounded-full mr-3"></div>
                  <span>10 rôles basés sur votre seeder Laravel</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-blue-500 rounded-full mr-3"></div>
                  <span>25 permissions organisées en catégories</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-blue-500 rounded-full mr-3"></div>
                  <span>Permissions par plafond de crédit</span>
                </li>
                <li className="flex items-center">
                  <div className="h-2 w-2 bg-blue-500 rounded-full mr-3"></div>
                  <span>Interface de gestion visuelle</span>
                </li>
              </ul>
            </div>
          </div>

          <div className="mt-8 pt-8 border-t border-gray-200">
            <h4 className="text-lg font-semibold text-gray-900 mb-4">Fonctionnalités CRUD</h4>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <div className="text-2xl font-bold text-blue-600">CRÉER</div>
                <p className="text-sm text-gray-600">Ajouter utilisateurs & rôles</p>
              </div>
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <div className="text-2xl font-bold text-green-600">LIRE</div>
                <p className="text-sm text-gray-600">Consulter toutes les données</p>
              </div>
              <div className="text-center p-4 bg-yellow-50 rounded-lg">
                <div className="text-2xl font-bold text-yellow-600">MODIFIER</div>
                <p className="text-sm text-gray-600">Éditer utilisateurs & permissions</p>
              </div>
              <div className="text-center p-4 bg-red-50 rounded-lg">
                <div className="text-2xl font-bold text-red-600">SUPPRIMER</div>
                <p className="text-sm text-gray-600">Supprimer éléments</p>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer className="bg-gray-800 text-white py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <p className="text-lg">Système de Gestion Utilisateurs & Rôles</p>
            <p className="text-gray-400 mt-2">Frontend React avec données mockées - Accès direct sans authentification</p>
            <div className="mt-6 flex justify-center space-x-6">
              <Link to="/users" className="text-gray-300 hover:text-white">
                Utilisateurs
              </Link>
              <Link to="/roles" className="text-gray-300 hover:text-white">
                Rôles
              </Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Home;