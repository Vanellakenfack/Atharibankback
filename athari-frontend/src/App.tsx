import React from 'react'
import { BrowserRouter as Router } from 'react-router-dom'
import AppRoutes from './routes/AppRoutes'

function App() {

  return (
    <Router>
      <AppRoutes />  {/* AppRoutes gère ses propres Routes */}
    </Router>
  )
}

export default App