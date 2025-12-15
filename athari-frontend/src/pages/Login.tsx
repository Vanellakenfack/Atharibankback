import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
// Assurez-vous que le chemin est correct (par exemple, "../api/ApiClient")
import ApiClient from "../ApiClient"; 
import {
  Box,
  Card,
  CardContent,
  TextField,
  Typography,
  Button,
  InputAdornment,
  Alert,
} from "@mui/material";
// ... Imports Mui et styles (omis pour la clarté)
import EmailIcon from "@mui/icons-material/Email";
import LockIcon from "@mui/icons-material/Lock";
import logo from "../assets/img/logo.png";
// Correction: Suppression de l'import non utilisé de Redux (setLoading)
// import { setLoading } from "../store/compte/compteSlice"; 

export default function Login() {
  const navigate = useNavigate();

  // Correction: Initialisation à vide pour éviter les erreurs de lecture
  const [formData, setFormData] = useState({ email: '', password: '' });
  
  // Correction: Ajout de l'état loading (absent précédemment)
  const [loading, setLoading] = useState(false); 
  
  // Correction: Renommé seterror en setError (convention JavaScript)
  const [error, setError] = useState(null); 

  // Mise à jour de l'état lors de la saisie
  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    setError(null);
  };

  // Logique de connexion 
  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const deviceName = 'Web Client'; // Requis par le backend

    try {
      const response = await ApiClient.post('/login', {
        email: formData.email,
        password: formData.password,
        device_name: deviceName
      });

      // Stockage du token
      localStorage.setItem('authToken', response.data.token);

      // Stockage des informations utilisateur
      localStorage.setItem('user', JSON.stringify(response.data.user));
      
      // Redirection après succès
      navigate('/users/management');

    } catch (err) {
      const errorData = err.response?.data;
      if (errorData?.errors?.email) {
        // Erreur de validation du champ 'email' (ex: Identifiants invalides)
        setError(errorData.errors.email[0]); 
      } else if (errorData?.message) {
        // Erreur générique du backend
        setError('Erreur de connexion : ' + errorData.message);
      } else {
        // Erreur réseau inattendue
        setError('Une erreur réseau est survenue.');
      }
      // Nettoyage en cas d'échec
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
    } finally {
      setLoading(false); // Arrêter le chargement
    }
  };

  return (
    <Box
      sx={{
        background: "linear-gradient(to right, #6a11cb, #2575fc)",
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        overflow: "visible",
        zIndex: 1,
      }}
    >
      {/* Bulles animées */}
      <div className="financial-bubbles">
        <div className="bubble">$</div>
        <div className="bubble">€</div>
        <div className="bubble">FCFA</div>
        <div className="bubble">£</div>
      </div>

      <Card
        component='form'
        onSubmit={handleSubmit} // Le formulaire gère la soumission
        sx={{
          width: "100%",
          maxWidth: 430,
          borderRadius: "20px",
          boxShadow: 5,
          backdropFilter: "blur(20px)",
          background: "rgba(255,255,255,0.9)",
          position: "relative",
          zIndex: 10,
        }}
      >
        
        <CardContent sx={{ textAlign: "center", p: 4 }}>
          {/* Logo (omission du code pour la clarté) */}
          <Box sx={{ mb: 2, display: "flex", justifyContent: "center", alignItems: "center" }}>
            <img
              src={logo}
              alt="Logo"
              style={{
                width: 100,
                height: 100,
                borderRadius: "50%",
                border: "3px solid white",
                objectFit: "cover",
                display: "block",
              }}
            />
          </Box>

          <Typography variant="h5" color="primary" gutterBottom>
            Connexion
          </Typography>

          <Typography sx={{ color: "text.secondary", mb: 3 }}>
            Accédez à votre espace sécurisé
          </Typography>

          {/* Affichage de l'erreur */}
          {error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}

          <TextField
            fullWidth
            margin="normal"
            label="Adresse Email"
            type="email"
            name="email"
            placeholder="exemple@atharibank.com"
            value={formData.email || ''}
            onChange={handleChange}
            required // Ajout de required ici pour le formulaire
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <EmailIcon color="secondary" />
                </InputAdornment>
              ),
            }}
          />

          <TextField
            fullWidth
            margin="normal"
            label="Mot de passe"
            type="password"
            name="password"
            placeholder="Votre mot de passe"
            value={formData.password || ''}
            onChange={handleChange}
            required
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <LockIcon color="secondary" />
                </InputAdornment>
              ),
            }}
          />

          <Button
            fullWidth
            type="submit" // Déclenche la soumission du formulaire
            variant="contained"
            size="large"
            disabled={loading} // Utilisation de l'état loading
            // SUPPRESSION DE onClick={handleSubmit} - Redondant avec type="submit"
            sx={{
              mt: 3,
              py: 1.3,
              fontWeight: 600,
            }}
          >
           {loading ? "Connexion en cours..." : "Se connecter"}
          </Button>

          <Button
            href="/"
            variant="text"
            color="secondary"
            startIcon={<span>{"←"}</span>}
          >
            Retour à l'accueil
          </Button>
        </CardContent>
      </Card>
    </Box>
  );
}