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
import UsersList from "../pages/users/UsersList";
import UserCreate from "../pages/users/UserCreate";
import UserEdit from "../pages/users/UserEdit";
import ProtectedRoute from "../components/users/ProtectedRoute";

const AppRoutes = () => {
  return (
      <Routes>
      {/* Route de connexion (URL: /login) */}
      <Route path="/login" element={<Login />} />
      <Route
          path="/users"
          element={<UsersList />}
        />
        <Route
          path="/users/new"
          element={<UserCreate />}
        />
        <Route
          path="/users/:id/edit"
          element={<UserEdit />}
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
   

      

      {/* Route Catch-all (URL inexistante / 404) */}
      <Route path="*" element={<div>Page Non Trouvée (404)</div>} />

    </Routes>
  );
};

export default AppRoutes;