import React, { useState, useEffect } from 'react';
import axios from 'axios'; // Ajoute axios
//import OwnerSidebar from './OwnerSidebar';
import { 
  FiHome, FiDollarSign, FiCalendar, FiMessageSquare, 
  FiUser, FiSettings, FiLogOut, FiStar, FiClock,
  FiAlertCircle, FiCheckCircle, FiTrendingUp, FiMenu
} from 'react-icons/fi';

const Dashboard = () => {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [isMobile, setIsMobile] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');
  const [user, setUser] = useState(null); // Ajoute ce state
  const stats = {
    revenue: 2450,
    bookings: 18,
    rating: 4.7,
    pendingQuotes: 3,
    completedJobs: 42
  };

  const upcomingBookings = [
    { id: 1, client: "Marie madeleine", service: "Plomberie", date: "15/06/2023", time: "10:00-12:00", price: 89 },
    { id: 2, client: " simon ", service: "Électricité", date: "16/06/2023", time: "14:00-16:00", price: 120 },
    { id: 3, client: "Sophie bassong", service: "Peinture", date: "17/06/2023", time: "09:00-11:00", price: 150 }
  ];

  const pendingQuotes = [
    { id: 1, client: "Jean pierre", service: "Jardinage", requested: "12/06/2023", budget: "200-300€" },
    { id: 2, client: "Lucie talla", service: "Menuiserie", requested: "13/06/2023", budget: "150-250€" }
  ];
   const recentReviews = [
    { id: 1, client: "Marie madeleine", rating: 5, comment: "Excellent travail, très professionnel !", date: "10/06/2023" },
    { id: 2, client: "Pierre ngo", rating: 4, comment: "Bon service mais un peu en retard", date: "08/06/2023" }
  ];
  // Récupération des infos utilisateur connecté
  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      axios.get('http://localhost:8000/api/user', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      .then(res => setUser(res.data))
      .catch(err => {
        setUser(null);
        // Optionnel : redirige vers login si erreur 401
      });
    }
  }, []);

  // ...détection mobile, stats, bookings, etc. inchangés...

  const renderStars = (rating) => {
    return [...Array(5)].map((_, i) => (
      <FiStar 
        key={i} 
        className={i < rating ? 'text-warning' : 'text-secondary'} 
        fill={i < rating ? 'currentColor' : 'none'}
      />
    ));
  };

  const toggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  return (
    <div className="dashboard-container">
      {/* Overlay pour mobile */}
      {isMobile && sidebarOpen && (
        <div 
          className="sidebar-overlay"
          onClick={toggleSidebar}
        />
      )}

     {/*} <OwnerSidebar 
        isOpen={sidebarOpen} 
        onToggle={toggleSidebar} 
        isMobile={isMobile}
      />

      {/* Main Content */}
      <div 
        className={`main-content ${sidebarOpen ? 'sidebar-open' : 'sidebar-closed'}`}
        style={{
          marginLeft: isMobile ? 0 : sidebarOpen ? '250px' : '70px'
        }}
      >
        {/* Header */}
        <header className="dashboard-header">
          <div className="d-flex align-items-center">
            {isMobile && (
              <button 
                className="btn btn-sm me-3"
                onClick={toggleSidebar}
              >
                <FiMenu />
              </button>
            )}
            <h4 className="mb-0 fw-semibold">
              {activeTab === 'overview' && 'Tableau de Bord'}
              {activeTab === 'bookings' && 'Gestion des Réservations'}
              {activeTab === 'messages' && 'Messagerie'}
              {activeTab === 'services' && 'Mes Services'}
            </h4>
          </div>
          
          <div className="d-flex align-items-center gap-3">
            <div className="position-relative">
              <FiAlertCircle className="fs-5 text-secondary" />
              <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">2</span>
            </div>
            <div className="d-flex align-items-center">
              {/* Affiche la photo et le nom de l'utilisateur connecté */}
              {user ? (
                <>
                  <img 
                    src={user.avatar || "https://randomuser.me/api/portraits/men/42.jpg"} 
                    alt="Profile" 
                    className="rounded-circle me-2"
                    style={{width: '32px', height: '32px', objectFit: 'cover'}}
                  />
                  <span className="fw-medium d-none d-sm-inline"> {user && (
          <div className="dashboard-user-info mb-4">
            <strong></strong> {user.name} <br />
            {/* Ajoute d'autres champs selon ta structure utilisateur */}
          </div>
        )}</span>
                </>
              ) : (
                <>
                  <img 
                    src="https://randomuser.me/api/portraits/men/42.jpg" 
                    alt="Profile" 
                    className="rounded-circle me-2"
                    style={{width: '32px', height: '32px', objectFit: 'cover'}}
                  />
                  <span className="fw-medium d-none d-sm-inline">  </span>
                </>
              )}
            </div>
          </div>
        </header>


        {/* Dashboard Content */}
             <main className="dashboard-main">
          {activeTab === 'overview' && (
            <>
              {/* Stats Cards */}
              <div className="stats-grid">
                <div className="stat-card border-primary">
                  <div className="stat-icon bg-primary">
                    <FiDollarSign />
                  </div>
                  <div className="stat-content">
                    <p className="stat-label">Revenus ce mois</p>
                    <h4 className="stat-value">{stats.revenue} fcfa</h4>
                  </div>
                </div>
                
                <div className="stat-card border-success">
                  <div className="stat-icon bg-success">
                    <FiCheckCircle />
                  </div>
                  <div className="stat-content">
                    <p className="stat-label">Prestations terminées</p>
                    <h4 className="stat-value">{stats.completedJobs}</h4>
                  </div>
                </div>
                
                <div className="stat-card border-warning">
                  <div className="stat-icon bg-warning">
                    <FiStar />
                  </div>
                  <div className="stat-content">
                    <p className="stat-label">Note moyenne</p>
                    <div className="d-flex align-items-center">
                      <h4 className="stat-value me-2">{stats.rating}/5</h4>
                      <div className="star-rating">
                        {renderStars(Math.floor(stats.rating))}
                      </div>
                    </div>
                  </div>
                </div>
                
                <div className="stat-card border-danger">
                  <div className="stat-icon bg-danger">
                    <FiAlertCircle />
                  </div>
                  <div className="stat-content">
                    <p className="stat-label">Devis en attente</p>
                    <h4 className="stat-value">{stats.pendingQuotes}</h4>
                  </div>
                </div>
              </div>

             

              {/* Quotes and Reviews */}
              
            </>
          )}

          {activeTab === 'bookings' && (
            <div className="dashboard-card">
              <div className="card-body">
                <h5 className="card-title">Toutes vos réservations</h5>
                <p className="text-muted">Interface complète de gestion des réservations</p>
              </div>
            </div>
          )}

          {activeTab === 'messages' && (
            <div className="dashboard-card">
              <div className="card-body">
                <h5 className="card-title">Messagerie</h5>
                <p className="text-muted">Interface de discussion avec les clients</p>
              </div>
            </div>
          )}

          {activeTab === 'services' && (
            <div className="dashboard-card">
              <div className="card-body">
                <h5 className="card-title">Gestion de vos services</h5>
                <p className="text-muted">Interface pour modifier vos offres de services</p>
              </div>
            </div>
          )}
        </main>
      </div>
    </div>
  );
};

export default Dashboard;