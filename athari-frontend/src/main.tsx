// src/main.jsx - Modification pour désactiver le double rendu
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
   <App />
)