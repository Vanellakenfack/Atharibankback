import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import AccountsPage from '../pages/compte/ComptePage';
import AccountCreatePage from '../pages/compte/CreationCompte';
import AccountEditPage from '../pages/compte/EditionPage';
import AccountDetailPage from '../pages/compte/DetailCompte';
import Formclient from '../pages/client/FormClient';
//import Home from '../pages/Home';
import Login from '../pages/Login'; 
import Home from '../pages/Home';
import ListeClient from '../pages/client/ListeClient';

const AppRoutes = () => {
  return (
      <Routes>
      {/* Route de connexion (URL: /login) */}
      <Route path="/login" element={<Login />} />

      {/* Page d'Accueil (URL: /) */}
      <Route path="/" element={<Home />} />
      
      {/* Routes Comptes */}
      <Route path="/accounts" element={<AccountsPage />} />
      <Route path="/accounts/create" element={<AccountCreatePage />} />
      <Route path="/accounts/:id" element={<AccountDetailPage />} />
      <Route path="/accounts/:id/edit" element={<AccountEditPage />} />
        

        <Route path='/client' element= {<ListeClient/>} > </Route>
        <Route path='/creerclient' element= {<Formclient/>} > </Route>

      

      {/* Route Catch-all (URL inexistante / 404) */}
      <Route path="*" element={<div>Page Non Trouvée (404)</div>} />

    </Routes>
  );
};

export default AppRoutes;