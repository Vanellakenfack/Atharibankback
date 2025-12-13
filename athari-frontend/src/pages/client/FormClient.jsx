import React, { useEffect, useMemo, useState } from "react";
import Header from "../../components/layout/Header";
import {
  ThemeProvider,
  createTheme,
  CssBaseline,
  Container,
  Box,
  Grid,
  TextField,
  Button,
  Stepper,
  Step,
  StepLabel,
  RadioGroup,
  FormControlLabel,
  Radio,
  Select,
  MenuItem,
  InputLabel,
  FormControl,
  Avatar,
  Typography,
  Checkbox,
  FormGroup,
  FormLabel,
  Divider,
  Paper,
} from "@mui/material";
// Import des couleurs pour le nouveau thème
import { indigo, blueGrey, cyan } from "@mui/material/colors"; 
import axios from "axios";
import { useForm, Controller } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import * as Yup from "yup";

/**
 * THÈME DÉFINITIF - Bleu Foncé (Indigo)
 */
const muiTheme = createTheme({
  palette: {
    mode: "light",
    primary: {
      // Couleur principale : Indigo 700 (Bleu Foncé)
      main: indigo[700], 
      light: indigo[500],
      dark: indigo[900],
      contrastText: "#fff",
    },
    secondary: {
      // Couleur secondaire (Cyan pour un contraste vif)
      main: cyan.A700, 
    },
    background: {
      // Fond de page : Gris très clair (pour un meilleur contraste avec le blanc du formulaire)
      default: blueGrey[50], 
      paper: "#ffffff",
    },
  },
  typography: {
    fontFamily: "Inter, Roboto, Arial",
  },
  shape: {
    borderRadius: 12,
  },
  components: {
    MuiButton: {
      defaultProps: {
        disableElevation: true,
      },
      styleOverrides: {
        root: {
          borderRadius: 8,
          textTransform: 'none',
        },
      },
    },
    MuiPaper: {
        styleOverrides: {
            root: {
                // Style pour le StepLabel actif (couleur principale)
                "& .MuiStepLabel-label.Mui-active": {
                    fontWeight: 700,
                    color: indigo[700], // Bleu foncé
                },
                // Style pour le StepLabel complété
                "& .MuiStepLabel-label.Mui-completed": {
                    color: blueGrey[700],
                },
            }
        }
    }
  },
});

/** Étapes */
const STEPS = [
  "Administratif & Photo",
  "Adresse & Contact",
  "Documents d'identité",
  "Informations personnelles",
];

/** Validation esquema par étape (Yup) */
const schemas = [
  // Étape 0 - Administratif
  Yup.object({
    type_client: Yup.string().required("Type de client requis"),
    num_agence: Yup.string().required("Agence requise"),
    idclient: Yup.string().required("Identifiant requis"),
    nom_prenoms: Yup.string().required("Nom et prénoms requis"),
    sexe: Yup.string().required("Sexe requis"),
    code_intitule: Yup.string().nullable(),
  }),
  // Étape 1 - Adresse
  Yup.object({
    adresse_ville: Yup.string().required("Ville requise"),
    adresse_quartier: Yup.string().required("Quartier requis"),
    bp: Yup.string().nullable(),
    tel_domicile: Yup.string().nullable(),
    tel_bureau: Yup.string().nullable(),
    email: Yup.string().email("Email invalide").nullable(),
  }),
  // Étape 2 - Identité
  Yup.object({
    cni1: Yup.string().nullable(),
    du1: Yup.date().nullable(),
    au1: Yup.date().nullable(),
    cni2: Yup.string().nullable(),
    du2: Yup.date().nullable(),
    au2: Yup.date().nullable(),
  }),
  // Étape 3 - Personnelles & autres
  Yup.object({
    date_naissance: Yup.date().nullable(),
    lieu_naissance: Yup.string().nullable(),
    profession: Yup.string().nullable(),
    nom_mere: Yup.string().nullable(),
    nom_pere: Yup.string().nullable(),
    nationalite: Yup.string().nullable(),
    pays_residence: Yup.string().nullable(),
    photo: Yup.mixed().nullable(),
  }),
];

/** Données villes -> quartiers */
const CITY_DATA = {
  Douala: ["Akwa", "Bonapriso", "Deïdo", "Bali", "Makepe", "Bonanjo"],
  Yaoundé: ["Essos", "Mokolo", "Biyem-Assi", "Mvog-Ada", "Nkolbisson", "Bastos"],
  Bafoussam: ["Tamdja", "Banengo", "Djeleng", "Nkong-Zem"],
  Bamenda: ["Mankon", "Nkwen", "Bali", "Bafut"],
};

export default function FormClient() {
  const [activeStep, setActiveStep] = useState(0);
  const [photoPreview, setPhotoPreview] = useState(null);
  const [clientCounter, setClientCounter] = useState(1);

  const defaultValues = useMemo(
    () => ({
      type_client: "physique",
      num_agence: "",
      idclient: "",
      nom_prenoms: "",
      sexe: "",
      code_intitule: "",
      adresse_ville: "",
      adresse_quartier: "",
      bp: "",
      tel_domicile: "",
      tel_bureau: "",
      email: "",
      cni1: "",
      du1: "",
      au1: "",
      autre_preciser: "",
      cni2: "",
      du2: "",
      au2: "",
      date_naissance: "",
      lieu_naissance: "",
      nom_mere: "",
      nom_pere: "",
      profession: "",
      employeur: "",
      situation_familiale: "",
      regime_matrimonial: "",
      tranche_salariale_mere: "",
      nom_epoux: "",
      date_naissance_epoux: "",
      lieu_naissance_epoux: "",
      profession_pere: "",
      tranche_salariale_pere: "",
      fonction_epoux: "",
      adresse_epoux: "",
      numero_epoux: "",
      tranche_salariale_epoux: "",
      nationalite: "",
      pays_residence: "",
      Qualite: "",
      gestionnaire: "",
      famille: "",
      group: "",
      profil: "",
      client_checkbox: false,
      signataire: false,
      mantaire: false,
      interdit_chequier: false,
      taxable: false,
      photo: null,
    }),
    []
  );

  const {
    control,
    handleSubmit,
    trigger,
    watch,
    setValue,
    getValues,
    formState: { errors },
  } = useForm({
    defaultValues,
    resolver: yupResolver(schemas[activeStep]),
    mode: "onTouched",
  });

  // Watch some fields
  const selectedAgence = watch("num_agence");
  const selectedVille = watch("adresse_ville");

  // Génération automatique d'ID client (lors du changement d'agence)
  useEffect(() => {
    if (selectedAgence) {
      const formatted = String(clientCounter).padStart(6, "0");
      const id = `${selectedAgence}${formatted}`;
      setValue("idclient", id);
    } else {
      setValue("idclient", "");
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedAgence]);

  // Mise à jour des quartiers lorsque la ville change
  const [quartiersOptions, setQuartiersOptions] = useState([]);
  useEffect(() => {
    if (selectedVille && CITY_DATA[selectedVille]) {
      setQuartiersOptions(CITY_DATA[selectedVille]);
      if (!getValues("adresse_quartier")) {
        setValue("adresse_quartier", CITY_DATA[selectedVille][0] || "");
      }
    } else {
      setQuartiersOptions([]);
      setValue("adresse_quartier", "");
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedVille]);

  // PREVIEW IMAGE
  const handlePhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setValue("photo", file, { shouldValidate: true });
    setPhotoPreview(URL.createObjectURL(file));
  };

  // Navigation étapes
  const handleNext = async () => {
    const valid = await trigger(); 
    if (valid) setActiveStep((s) => Math.min(s + 1, STEPS.length - 1));
  };
  const handleBack = () => setActiveStep((s) => Math.max(s - 1, 0));

  // Soumission finale
  const onSubmit = async (formDataRaw) => {
    const valid = await trigger(); 
    if (!valid) return;
    
    const fd = new FormData();
    Object.entries(formDataRaw).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== false) {
        if (typeof v === 'boolean' && v === true) {
            fd.append(k, 'true');
        } else {
            fd.append(k, v);
        }
      }
    });

    try {
      const res = await axios.post("https://ton-api.com/clients", fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      console.log("API response:", res.data);
      alert("Client enregistré avec succès !");
    } catch (err) {
      console.error(err);
      alert("Erreur lors de l'enregistrement. Voir console.");
    }
  };

  // Keyboard nav (Left/Right)
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === "ArrowRight" && activeStep < STEPS.length - 1) handleNext();
      if (e.key === "ArrowLeft" && activeStep > 0) handleBack();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeStep, trigger]); 

  // Rendu des champs du formulaire
  const renderFormFields = () => {
    switch (activeStep) {
      case 0:
        return (
          <Box>
            <Typography variant="h6" color="primary" sx={{ mb: 3 }}>Informations Administratives</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={3}>
                <Controller
                  name="type_client"
                  control={control}
                  render={({ field }) => (
                    <FormControl fullWidth size="small">
                      <InputLabel id="type-client-label">Type client</InputLabel>
                      <Select labelId="type-client-label" label="Type client" {...field}>
                        <MenuItem value="physique">Personne Physique</MenuItem>
                        <MenuItem value="entreprise">Personne Morale</MenuItem>
                      </Select>
                    </FormControl>
                  )}
                />
              </Grid>

              <Grid item xs={12} md={3}>
                <Controller
                  name="num_agence"
                  control={control}
                  render={({ field }) => (
                    <FormControl fullWidth size="small" error={!!errors.num_agence}>
                      <InputLabel id="agence-label">Agence</InputLabel>
                      <Select labelId="agence-label" label="Agence" {...field}>
                        <MenuItem value="">-- Sélectionner --</MenuItem>
                        <MenuItem value="001">001 - Ekounou (Réussite)</MenuItem>
                        <MenuItem value="002">002 - Essos (Audace)</MenuItem>
                        <MenuItem value="003">003 - Etoudi (Speed)</MenuItem>
                        <MenuItem value="004">004 - Mendong (Power)</MenuItem>
                        <MenuItem value="005">005 - Mokolo (Imani)</MenuItem>
                      </Select>
                      {errors.num_agence && <Typography color="error" variant="caption">{errors.num_agence.message}</Typography>}
                    </FormControl>
                  )}
                />
              </Grid>

              <Grid item xs={12} md={3}>
                <Controller
                  name="idclient"
                  control={control}
                  render={({ field }) => (
                    <TextField 
                        fullWidth 
                        size="small"
                        label="Identifiant client" 
                        {...field} 
                        error={!!errors.idclient}
                        helperText={errors.idclient ? errors.idclient.message : 'Généré automatiquement (Agence + N° Client)'}
                        disabled
                    />
                  )}
                />
              </Grid>

              <Grid item xs={12} md={3}>
                <Controller
                  name="code_intitule"
                  control={control}
                  render={({ field }) => (
                    <TextField 
                        fullWidth 
                        size="small"
                        label="Code intitulé (M./Mme...)" 
                        {...field} 
                    />
                  )}
                />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller
                  name="nom_prenoms"
                  control={control}
                  render={({ field }) => (
                    <TextField 
                        fullWidth 
                        size="small"
                        label="Nom et prénoms" 
                        required 
                        {...field} 
                        error={!!errors.nom_prenoms}
                        helperText={errors.nom_prenoms ? errors.nom_prenoms.message : null}
                    />
                  )}
                />
              </Grid>

              <Grid item xs={12} md={6}>
                <FormControl component="fieldset" error={!!errors.sexe}>
                  <FormLabel component="legend" sx={{mb: 1}}>Sexe *</FormLabel>
                  <Controller
                    name="sexe"
                    control={control}
                    render={({ field }) => (
                      <RadioGroup row {...field}>
                        <FormControlLabel value="masculin" control={<Radio size="small" />} label="Masculin" />
                        <FormControlLabel value="feminin" control={<Radio size="small" />} label="Féminin" />
                      </RadioGroup>
                    )}
                  />
                  {errors.sexe && <Typography color="error" variant="caption">{errors.sexe.message}</Typography>}
                </FormControl>
              </Grid>
              
              <Grid item xs={12} md={4}>
                <Box
                  // Notez le changement de classe Tailwind pour le style bleu/indigo
                  className="flex flex-col items-center justify-center border-2 border-dashed border-indigo-300 rounded-xl p-4 h-full bg-indigo-50/50" 
                  onClick={() => document.getElementById("photo-input")?.click()}
                  sx={{ cursor: "pointer", minHeight: 180 }}
                >
                  <input
                    id="photo-input"
                    type="file"
                    accept="image/*"
                    style={{ display: "none" }}
                    onChange={handlePhotoChange}
                  />
                  <Typography className="text-sm font-medium text-gray-700 mb-2">Photo client</Typography>
                  {photoPreview ? (
                    <Avatar src={photoPreview} sx={{ width: 100, height: 100, border: `3px solid ${indigo[500]}` }} />
                  ) : (
                    <Avatar sx={{ width: 100, height: 100, bgcolor: indigo[300], color: "#fff" }}>
                      <Typography className="text-xl">PHOTO</Typography>
                    </Avatar>
                  )}
                  <Typography variant="caption" sx={{ mt: 1, color: blueGrey[600] }}>
                    Cliquez pour télécharger
                  </Typography>
                </Box>
              </Grid>
            </Grid>
          </Box>
        );

      case 1:
        return (
          <Box>
            <Typography variant="h6" color="primary" sx={{ mb: 3 }}>Adresse et Coordonnées</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                <Controller
                  name="adresse_ville"
                  control={control}
                  render={({ field }) => (
                    <FormControl fullWidth size="small" error={!!errors.adresse_ville}>
                      <InputLabel id="ville-label">Ville</InputLabel>
                      <Select labelId="ville-label" label="Ville" {...field}>
                        <MenuItem value="">-- Sélectionner --</MenuItem>
                        {Object.keys(CITY_DATA).map((v) => (
                          <MenuItem key={v} value={v}>{v}</MenuItem>
                        ))}
                      </Select>
                      {errors.adresse_ville && <Typography color="error" variant="caption">{errors.adresse_ville.message}</Typography>}
                    </FormControl>
                  )}
                />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller
                  name="adresse_quartier"
                  control={control}
                  render={({ field }) => (
                    <FormControl fullWidth size="small" error={!!errors.adresse_quartier} disabled={quartiersOptions.length === 0}>
                      <InputLabel id="quartier-label">Quartier</InputLabel>
                      <Select labelId="quartier-label" label="Quartier" {...field}>
                        <MenuItem value="">-- Sélectionner un quartier --</MenuItem>
                        {quartiersOptions.map((q) => (
                          <MenuItem key={q} value={q}>{q}</MenuItem>
                        ))}
                      </Select>
                      {errors.adresse_quartier && <Typography color="error" variant="caption">{errors.adresse_quartier.message}</Typography>}
                    </FormControl>
                  )}
                />
              </Grid>

              <Grid item xs={12} md={4}>
                <Controller
                  name="bp"
                  control={control}
                  render={({ field }) => <TextField fullWidth size="small" label="Boîte Postale (BP)" {...field} />}
                />
              </Grid>

              <Grid item xs={12} md={4}>
                <Controller
                  name="tel_domicile"
                  control={control}
                  render={({ field }) => <TextField fullWidth size="small" label="Téléphone domicile / Mobile" {...field} />}
                />
              </Grid>

              <Grid item xs={12} md={4}>
                <Controller
                  name="tel_bureau"
                  control={control}
                  render={({ field }) => <TextField fullWidth size="small" label="Fax / Tél. bureau" {...field} />}
                />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller
                  name="email"
                  control={control}
                  render={({ field }) => (
                    <TextField 
                        fullWidth 
                        size="small"
                        label="Email" 
                        {...field} 
                        error={!!errors.email}
                        helperText={errors.email ? errors.email.message : null}
                    />
                  )}
                />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller
                  name="profession_mere" 
                  control={control}
                  render={({ field }) => <TextField fullWidth size="small" label="Profession / Localisation" {...field} />}
                />
              </Grid>
            </Grid>
          </Box>
        );

      case 2:
        return (
          <Box>
            <Typography variant="h6" color="primary" sx={{ mb: 3 }}>Documents d'Identité</Typography>
            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Document Principal (CNI, Passeport, etc.)</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={3}>
                <Controller name="cni1" control={control} render={({ field }) => <TextField fullWidth size="small" label="N° Document 1" {...field} />} />
              </Grid>
              <Grid item xs={12} md={3}>
                <Controller name="du1" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Délivré le" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
              <Grid item xs={12} md={3}>
                <Controller name="au1" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Expire le" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
              <Grid item xs={12} md={3}>
                <Controller name="autre_preciser" control={control} render={({ field }) => <TextField fullWidth size="small" label="Autre (préciser)" {...field} />} />
              </Grid>
            </Grid>

            <Divider sx={{ my: 3 }} />

            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Document Secondaire</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={3}>
                <Controller name="cni2" control={control} render={({ field }) => <TextField fullWidth size="small" label="N° Document 2" {...field} />} />
              </Grid>
              <Grid item xs={12} md={3}>
                <Controller name="du2" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Délivré le (2)" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
              <Grid item xs={12} md={3}>
                <Controller name="au2" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Expire le (2)" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
            </Grid>
          </Box>
        );

      case 3:
        return (
          <Box>
            <Typography variant="h6" color="primary" sx={{ mb: 3 }}>Informations Personnelles, Familiales et Professionnelles</Typography>
            
            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Infos État Civil</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                <Controller name="date_naissance" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Date de naissance" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
              <Grid item xs={12} md={6}>
                <Controller name="lieu_naissance" control={control} render={({ field }) => <TextField fullWidth size="small" label="Lieu de naissance" {...field} />} />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller name="nom_mere" control={control} render={({ field }) => <TextField fullWidth size="small" label="Nom de la mère" {...field} />} />
              </Grid>
              <Grid item xs={12} md={6}>
                <Controller name="nom_pere" control={control} render={({ field }) => <TextField fullWidth size="small" label="Nom du père" {...field} />} />
              </Grid>
            </Grid>
            
            <Divider sx={{ my: 3 }} />
            
            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Situation Professionnelle et Familiale</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                <Controller name="profession" control={control} render={({ field }) => <TextField fullWidth size="small" label="Profession" {...field} />} />
              </Grid>
              <Grid item xs={12} md={6}>
                <Controller name="employeur" control={control} render={({ field }) => <TextField fullWidth size="small" label="Employeur" {...field} />} />
              </Grid>

              <Grid item xs={12} md={6}>
                <FormControl fullWidth size="small">
                  <InputLabel id="situation-label">Situation familiale</InputLabel>
                  <Controller
                    name="situation_familiale"
                    control={control}
                    render={({ field }) => (
                      <Select labelId="situation-label" label="Situation familiale" {...field}>
                        <MenuItem value="">-- Sélectionner --</MenuItem>
                        <MenuItem value="marie">Marié(e)</MenuItem>
                        <MenuItem value="celibataire">Célibataire</MenuItem>
                        <MenuItem value="autres">Autres</MenuItem>
                      </Select>
                    )}
                  />
                </FormControl>
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller name="regime_matrimonial" control={control} render={({ field }) => <TextField fullWidth size="small" label="Régime matrimonial" {...field} />} />
              </Grid>
            </Grid>
            
            <Divider sx={{ my: 3 }} />

            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Infos Conjoint (si Marié(e))</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={4}>
                <Controller name="nom_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" label="Nom & Prénom (époux)" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="date_naissance_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" type="date" label="Date de naissance (époux)" InputLabelProps={{ shrink: true }} {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="lieu_naissance_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" label="Lieu de naissance (époux)" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="fonction_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" label="Profession (époux)" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="adresse_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" label="Employeur (époux)" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="numero_epoux" control={control} render={({ field }) => <TextField fullWidth size="small" label="Téléphone (époux)" {...field} />} />
              </Grid>
            </Grid>

            <Divider sx={{ my: 3 }} />

            <Typography variant="subtitle2" sx={{ mb: 2, color: blueGrey[700] }}>Paramètres et Catégories Client</Typography>
            <Grid container spacing={3}>
              <Grid item xs={12} md={4}>
                <Controller name="nationalite" control={control} render={({ field }) => <TextField fullWidth size="small" label="Nationalité" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="pays_residence" control={control} render={({ field }) => <TextField fullWidth size="small" label="Pays de résidence" {...field} />} />
              </Grid>
              <Grid item xs={12} md={4}>
                <Controller name="Qualite" control={control} render={({ field }) => <TextField fullWidth size="small" label="Qualité" {...field} />} />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller name="gestionnaire" control={control} render={({ field }) => <TextField fullWidth size="small" label="Gestionnaire (id)" {...field} />} />
              </Grid>

              <Grid item xs={12} md={6}>
                <Controller name="profil" control={control} render={({ field }) => <TextField fullWidth size="small" label="Profil" {...field} />} />
              </Grid>

              <Grid item xs={12}>
                <FormLabel component="legend" sx={{ mb: 1, fontWeight: 'medium' }}>Rôles / Options</FormLabel>
                <FormGroup row>
                  <FormControlLabel control={<Controller name="client_checkbox" control={control} render={({ field }) => <Checkbox size="small" {...field} checked={!!field.value} />} />} label="Client" />
                  <FormControlLabel control={<Controller name="signataire" control={control} render={({ field }) => <Checkbox size="small" {...field} checked={!!field.value} />} />} label="Signataire" />
                  <FormControlLabel control={<Controller name="mantaire" control={control} render={({ field }) => <Checkbox size="small" {...field} checked={!!field.value} />} />} label="Mantaire" />
                  <FormControlLabel control={<Controller name="interdit_chequier" control={control} render={({ field }) => <Checkbox size="small" {...field} checked={!!field.value} />} />} label="Interdit chéquier" />
                  <FormControlLabel control={<Controller name="taxable" control={control} render={({ field }) => <Checkbox size="small" {...field} checked={!!field.value} />} />} label="Taxable" />
                </FormGroup>
              </Grid>
            </Grid>
          </Box>
        );

      default:
        return null;
    }
  };


  return (
    
    <ThemeProvider theme={muiTheme}>
    <Header />

      <CssBaseline />
      <Container maxWidth="lg" sx={{ py: 6, bgcolor: "white", minHeight: '100vh' }}>
       
        <Paper elevation={3} sx={{ p: { xs: 3, md: 5 }, borderRadius: 3 }}>
          <Typography variant="h4" sx={{ color: blueGrey[800], fontWeight: 'bold', mb: 1 }}>
            Nouveau Client
          </Typography>
          <Typography variant="subtitle1" color="text.secondary" sx={{ mb: 4 }}>
            Formulaire d'enregistrement en 4 étapes.
          </Typography>

          {/* Stepper MUI */}
          <Box sx={{ mb: 4 }}>
            <Stepper activeStep={activeStep} alternativeLabel>
              {STEPS.map((label, index) => (
                <Step key={label}>
                  <StepLabel 
                    sx={{ cursor: 'pointer' }}
                    onClick={async () => {
                      if (index < activeStep) {
                          setActiveStep(index);
                      } else {
                          const valid = await trigger();
                          if (valid) setActiveStep(index);
                      }
                    }}
                  >
                    <Typography variant="body2" sx={{ fontWeight: index === activeStep ? 'bold' : 'normal' }}>
                        {label}
                    </Typography>
                  </StepLabel>
                </Step>
              ))}
            </Stepper>
          </Box>

          {/* Progress Bar Tailwind (couleur changée) */}
          <Box sx={{ width: '100%', bgcolor: blueGrey[200], borderRadius: 1, height: 10, mb: 4, overflow: 'hidden' }}>
            <Box
              sx={{
                height: 10,
                bgcolor: indigo[500], // Bleu foncé
                transition: 'width 300ms ease-in-out',
                width: `${((activeStep + 1) / STEPS.length) * 100}%`
              }}
            />
          </Box>

          <form onSubmit={handleSubmit(onSubmit)}>
            
            {/* RENDER FORM FIELDS */}
            {renderFormFields()}

            {/* NAVIGATION */}
            <Box sx={{ display: 'flex', justifyContent: 'space-between', pt: 4, borderTop: `1px solid ${blueGrey[200]}` }}>
              <Button
                variant="outlined"
                onClick={handleBack}
                disabled={activeStep === 0}
                sx={{ minWidth: 120 }}
              >
                ← Précédent
              </Button>

              {activeStep < STEPS.length - 1 ? (
                <Button variant="contained" color="primary" onClick={handleNext} sx={{ minWidth: 120 }}>
                  Suivant →
                </Button>
              ) : (
                // Bouton final en couleur secondaire (Cyan) pour le contraste
                <Button type="submit" variant="contained" color="secondary" sx={{ minWidth: 180 }}>
                  ✅ Enregistrer le client
                </Button>
              )}
            </Box>
          </form>
        </Paper>
      </Container>
    </ThemeProvider>
  );
}