import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import ApiClient from "../ApiClient";
import logo from "../assets/img/logo.png";
import "../assets/css/login.css";

export default function Login() {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({ email: '', password: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    setError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const deviceName = 'Web Client';

    try {
      const response = await ApiClient.post('/login', {
        email: formData.email,
        password: formData.password,
        device_name: deviceName
      });

      localStorage.setItem('authToken', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
<<<<<<< HEAD:athari-frontend/src/pages/Login.jsx
      navigate('/dashboard');
=======
      
      // Redirection après succès
      navigate('/users/management');
>>>>>>> 09f7f520819d17b8f5bd2c7cfcce97e473c264b0:athari-frontend/src/pages/Login.tsx

    } catch (err) {
      const errorData = err.response?.data;
      if (errorData?.errors?.email) {
        setError(errorData.errors.email[0]);
      } else if (errorData?.message) {
        setError('Erreur de connexion : ' + errorData.message);
      } else {
        setError('Une erreur réseau est survenue.');
      }
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-body">
      {/* Bulles animées */}
      <div className="financial-bubbles">
        <div className="bubble">$</div>
        <div className="bubble">€</div>
        <div className="bubble">FCFA</div>
        <div className="bubble">£</div>
      </div>

      {/* Formulaire */}
      <div className="login-card">
        {/* Logo */}
        <div className="logo-container">
          <img src={logo} alt="Logo Athari Financial" />
        </div>

        {/* Titre */}
        <h2>Connexion</h2>
        <p className="subtitle">Accédez à votre espace sécurisé</p>

        {/* Erreur */}
        {error && (
          <div className="alert-error">
            {error}
          </div>
        )}

        {/* Formulaire */}
        <form onSubmit={handleSubmit}>
          {/* Email */}
          <div className="form-group">
            <label htmlFor="email">Adresse Email</label>
            <div className="input-wrapper">
              <i>✉</i>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="exemple@atharibank.com"
                value={formData.email || ""}
                onChange={handleChange}
                required
              />
            </div>
          </div>

          {/* Mot de passe */}
          <div className="form-group">
            <label htmlFor="password">Mot de passe</label>
            <div className="input-wrapper">
              <i>🔒</i>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Votre mot de passe"
                value={formData.password || ""}
                onChange={handleChange}
                required
              />
            </div>
          </div>

          {/* Bouton */}
          <button 
            type="submit" 
            disabled={loading}
            className="btn-submit"
          >
            {loading ? "Connexion en cours..." : "Se connecter →"}
          </button>
        </form>

        {/* Retour */}
        <a href="/" className="back-link">← Retour à l'accueil</a>
      </div>
    </div>
  );
}