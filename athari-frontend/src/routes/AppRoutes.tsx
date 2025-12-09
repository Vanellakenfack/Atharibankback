import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import MainLayout from '../components/layout/MainLayout';
import AccountsPage from '../pages/compte/ComptePage';
import AccountCreatePage from '../pages/compte/CreationCompte';
import AccountEditPage from '../pages/compte/EditionPage';
import AccountDetailPage from '../pages/compte/DetailCompte';
//import Home from '../pages/Home';
import Login from '../pages/Login'; 

const AppRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<MainLayout />}>
        <Route index element={<Navigate to="/accounts" replace />} />
        <Route path="accounts" element={<AccountsPage />} />
        <Route path="accounts/create" element={<AccountCreatePage />} />
        <Route path="accounts/:id" element={<AccountDetailPage />} />
        <Route path="accounts/:id/edit" element={<AccountEditPage />} />
        {/* Ajouter d'autres routes ici au fur et à mesure */}


        {/*       <Route path="*" element={<Navigate to="/login" replace />} /> */}
        <Route path="/login" element={<Login/>} />
        {/* <Route path="/home" element={<Home/>} /> */}
      </Route>

{/*       <Route>
        <Route path="/login" element={<Login/>} />
          <Route path="/home" element={<Home/>} />

      </Route> */}
    </Routes>
  );
};

export default AppRoutes;