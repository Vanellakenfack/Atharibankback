import React from 'react'
import { BrowserRouter as Router } from 'react-router-dom'
import Sidebar from './components/layout/Sidebar.tsx'
import Header from './components/layout/Header'
import AppRoutes from './routes/AppRoutes'

function App() {
  const [sideBarCollapsed, setSideBarCollapsed] = React.useState(false)
  const [currentPage, setCurrentPage] = React.useState('Dashboard')

  return (
    <Router>
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 transition-all duration-500">
        <div className="flex h-screen overflow-hidden">
          {/* <Sidebar 
            collapsed={sideBarCollapsed} 
            onToggle={() => setSideBarCollapsed(!sideBarCollapsed)}
            currentPage={currentPage}
            onPageChange={setCurrentPage}
          /> */}
          
          <div className="flex-1 flex flex-col overflow-hidden">
           
            
            {/* Zone de contenu pour les routes */}
            <div className="flex-1 overflow-y-auto p-4 md:p-6">
              <AppRoutes />  {/* AppRoutes gère ses propres Routes */}
            </div>
          </div>
        </div>
      </div>
    </Router>
  )
}

export default App