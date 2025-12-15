import React from 'react';
import { BrowserRouter, Routes, Route, Navigate, Link } from "react-router-dom";
import AccountsPage from '../pages/compte/ComptePage';
import AccountCreatePage from '../pages/compte/CreationCompte';
import AccountEditPage from '../pages/compte/EditionPage';
import AccountDetailPage from '../pages/compte/DetailCompte';
import Formclient from '../pages/client/FormClient';
//import Home from '../pages/Home';
import Login from '../pages/Login'; 
import Home from '../pages/Home';
import ListeClient from '../pages/client/ListeClient';
<<<<<<< HEAD
import Dashboard from  '../layouts/Dashboard'
=======
import RoleManagement from "../pages/users/RoleManagement";
import UserManagement from "../pages/users/UserManagement";
import ProtectedRoute from "../components/users/ProtectedRoute";

>>>>>>> 09f7f520819d17b8f5bd2c7cfcce97e473c264b0
const AppRoutes = () => {
  return (
    <Routes>
      {/* Route de connexion (URL: /login) */}
      <Route 
          path="/login" 
          element={<Login />} 
        />
        <Route
          path="/users/roles"
          element={<RoleManagement />}
        />
        <Route
          path="/users/management"
          element={<UserManagement />}
        />

      {/* Page d'Accueil (URL: /) */}
      <Route path="/" element={<Home />} />
      
      {/* Routes Comptes */}
      <Route path="/accounts" element={<AccountsPage />} />
      <Route path="/accounts/create" element={<AccountCreatePage />} />
      <Route path="/accounts/:id" element={<AccountDetailPage />} />
      <Route path="/accounts/:id/edit" element={<AccountEditPage />} />
        

        <Route path='/client' element= {<ListeClient/>} /> 
           
        <Route path='/client/creer' element= {<Formclient/>} /> 
        <Route path='/client/:id/edit' element= {<Formclient/>} />
                <Route path='dashboard' element= {<Dashboard/>} />


      

      {/* Route Catch-all (URL inexistante / 404) */}
      <Route path="*" element={<div>Page Non Trouvée (404)</div>} />

    </Routes>
  );
};

export default AppRoutes;