import React from "react";
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
import EmailIcon from "@mui/icons-material/Email";
import LockIcon from "@mui/icons-material/Lock";
import "../assets/css/login.css";
import "../assets/css/bubbles.css";
import Sidebar from "../components/layout/Sidebar";
import { CenterFocusStrong } from "@mui/icons-material";

export default function Login() {
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

        sx={{
          width: "100%",
          maxWidth: 430,
          borderRadius: "20px",
          boxShadow: 5,
          backdropFilter: "blur(20px)",
          background: "rgba(255,255,255,0.9)",
        }}
      >
        
        <CardContent sx={{ textAlign: "center", p: 4 }}>
          {/* Logo */}
          <Box sx={{ mb:1, position: 'center' }}>
            <img
              src="../assets/img/logo.png"
              alt="Logo"
              style={{
                width: 100,
                height: 100,
                borderRadius: "50%",
                border: "3px solid white",
                objectFit: "cover",
              }}
            />
          </Box>

          <Typography variant="h5" color="primary" gutterBottom>
            Connexion
          </Typography>

          <Typography sx={{ color: "text.secondary", mb: 3 }}>
            Accédez à votre espace sécurisé
          </Typography>

          {/* Exemple d’erreur */}
          {/* 
          <Alert severity="error" sx={{ mb: 3 }}>
            Identifiants incorrects. Veuillez réessayer.
          </Alert>
          */}

          <TextField
            fullWidth
            margin="normal"
            label="Adresse Email"
            type="email"
            placeholder="exemple@atharibank.com"
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
            placeholder="Votre mot de passe"
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
            variant="contained"
            size="large"
            sx={{
              mt: 3,
              py: 1.3,
              fontWeight: 600,
            }}
          >
            Se connecter
          </Button>

          <Button
            sx={{ mt: 2 }}
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
