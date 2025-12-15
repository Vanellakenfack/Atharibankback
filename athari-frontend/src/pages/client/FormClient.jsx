import React, { useEffect, useMemo, useState } from "react";
import Header from "../../layouts/Header";
import axios from "axios";
import { useForm, Controller } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import * as Yup from "yup";

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

// Reusable Tailwind Input Components
const FormInput = ({ label, error, helperText, ...props }) => (
  <div className="w-full">
    {label && <label className="block text-sm font-semibold text-gray-800 mb-2">{label}</label>}
    <input
      {...props}
      className={`w-full px-4 py-3 border-2 rounded-xl text-base focus:outline-none focus:ring-2 transition-all ${
        error 
          ? "border-red-400 bg-red-50 focus:ring-red-500" 
          : "border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500"
      }`}
    />
    {helperText && <p className={`text-xs mt-2 font-medium ${error ? "text-red-600" : "text-gray-600"}`}>{helperText}</p>}
  </div>
);

const FormSelect = ({ label, error, helperText, options, ...props }) => (
  <div className="w-full">
    {label && <label className="block text-sm font-semibold text-gray-800 mb-2">{label}</label>}
    <select
      {...props}
      className={`w-full px-4 py-3 border-2 rounded-xl text-base focus:outline-none focus:ring-2 transition-all appearance-none bg-white ${
        error 
          ? "border-red-400 bg-red-50 focus:ring-red-500" 
          : "border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
      }`}
    >
      {options && options.map((opt) => (
        <option key={opt.value} value={opt.value}>
          {opt.label}
        </option>
      ))}
    </select>
    {helperText && <p className={`text-xs mt-2 font-medium ${error ? "text-red-600" : "text-gray-600"}`}>{helperText}</p>}
  </div>
);

const FormCheckbox = ({ label, ...props }) => (
  <label className="flex items-center gap-3 text-base text-gray-800 cursor-pointer">
    <input 
      type="checkbox" 
      {...props} 
      className="w-5 h-5 rounded-lg border-2 border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 cursor-pointer" 
    />
    <span className="font-medium">{label}</span>
  </label>
);

const FormRadio = ({ label, ...props }) => (
  <label className="flex items-center gap-3 text-base text-gray-800 cursor-pointer">
    <input 
      type="radio" 
      {...props} 
      className="w-5 h-5 border-2 border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 cursor-pointer" 
    />
    <span className="font-medium">{label}</span>
  </label>
);

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

  // Génération automatique d'ID client
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

  // Mise à jour des quartiers
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

  // Navigation
  const handleNext = async () => {
    const valid = await trigger();
    if (valid) setActiveStep((s) => Math.min(s + 1, STEPS.length - 1));
  };
  const handleBack = () => setActiveStep((s) => Math.max(s - 1, 0));

  // Soumission
  const onSubmit = async (formDataRaw) => {
    const valid = await trigger();
    if (!valid) return;

    const fd = new FormData();
    Object.entries(formDataRaw).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== false) {
        if (typeof v === "boolean" && v === true) {
          fd.append(k, "true");
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

  // Keyboard nav
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === "ArrowRight" && activeStep < STEPS.length - 1) handleNext();
      if (e.key === "ArrowLeft" && activeStep > 0) handleBack();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeStep, trigger]);

  // Render form fields
  const renderFormFields = () => {
    switch (activeStep) {
      case 0:
        return (
          <div>
            <h3 className="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b-2 border-indigo-600">📋 Informations Administratives</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <Controller
                name="type_client"
                control={control}
                render={({ field }) => (
                  <FormSelect
                    label="Type client"
                    error={!!errors.type_client}
                    options={[
                      { value: "physique", label: "Personne Physique" },
                      { value: "entreprise", label: "Personne Morale" },
                    ]}
                    {...field}
                  />
                )}
              />
              <Controller
                name="num_agence"
                control={control}
                render={({ field }) => (
                  <FormSelect
                    label="Agence"
                    error={!!errors.num_agence}
                    helperText={errors.num_agence?.message}
                    options={[
                      { value: "", label: "-- Sélectionner --" },
                      { value: "001", label: "001 - Ekounou (Réussite)" },
                      { value: "002", label: "002 - Essos (Audace)" },
                      { value: "003", label: "003 - Etoudi (Speed)" },
                      { value: "004", label: "004 - Mendong (Power)" },
                      { value: "005", label: "005 - Mokolo (Imani)" },
                    ]}
                    {...field}
                  />
                )}
              />
              <Controller
                name="idclient"
                control={control}
                render={({ field }) => (
                  <FormInput
                    label="Identifiant client"
                    type="text"
                    disabled
                    error={!!errors.idclient}
                    helperText="Généré automatiquement (Agence + N° Client)"
                    {...field}
                  />
                )}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <Controller
                name="code_intitule"
                control={control}
                render={({ field }) => (
                  <FormInput label="Code intitulé (M./Mme...)" type="text" {...field} />
                )}
              />
              <Controller
                name="nom_prenoms"
                control={control}
                render={({ field }) => (
                  <FormInput
                    label="Nom et prénoms *"
                    type="text"
                    error={!!errors.nom_prenoms}
                    helperText={errors.nom_prenoms?.message}
                    {...field}
                  />
                )}
              />
            </div>

            <div className="mb-6">
              <label className="block text-base font-bold text-gray-900 mb-4">Sexe *</label>
              <div className="flex gap-8">
                <Controller
                  name="sexe"
                  control={control}
                  render={({ field }) => (
                    <>
                      <FormRadio
                        label="Masculin"
                        value="masculin"
                        checked={field.value === "masculin"}
                        onChange={(e) => field.onChange(e.target.value)}
                      />
                      <FormRadio
                        label="Féminin"
                        value="feminin"
                        checked={field.value === "feminin"}
                        onChange={(e) => field.onChange(e.target.value)}
                      />
                    </>
                  )}
                />
              </div>
              {errors.sexe && <p className="text-sm text-red-600 mt-3 font-medium">{errors.sexe.message}</p>}
            </div>

            <div className="mb-6">
              <div
                className="flex flex-col items-center justify-center border-3 border-dashed border-indigo-400 rounded-2xl p-8 bg-indigo-50/50 cursor-pointer min-h-[220px] hover:border-indigo-600 hover:bg-indigo-100/50 transition-all"
                onClick={() => document.getElementById("photo-input")?.click()}
              >
                <input
                  id="photo-input"
                  type="file"
                  accept="image/*"
                  style={{ display: "none" }}
                  onChange={handlePhotoChange}
                />
                <p className="text-lg font-bold text-gray-800 mb-4">📸 Photo client</p>
                {photoPreview ? (
                  <img src={photoPreview} alt="Preview" className="w-28 h-28 object-cover rounded-full border-4 border-indigo-600 shadow-lg" />
                ) : (
                  <div className="w-28 h-28 flex items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-blue-500 text-white text-sm font-bold shadow-lg">PHOTO</div>
                )}
                <p className="text-sm text-gray-700 mt-4 font-medium">Cliquez pour télécharger une photo</p>
              </div>
            </div>
          </div>
        );

      case 1:
        return (
          <div>
            <h3 className="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b-2 border-indigo-600">📍 Adresse et Coordonnées</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <Controller
                name="adresse_ville"
                control={control}
                render={({ field }) => (
                  <FormSelect
                    label="Ville *"
                    error={!!errors.adresse_ville}
                    helperText={errors.adresse_ville?.message}
                    options={[
                      { value: "", label: "-- Sélectionner --" },
                      ...Object.keys(CITY_DATA).map((v) => ({ value: v, label: v })),
                    ]}
                    {...field}
                  />
                )}
              />
              <Controller
                name="adresse_quartier"
                control={control}
                render={({ field }) => (
                  <FormSelect
                    label="Quartier *"
                    error={!!errors.adresse_quartier}
                    helperText={errors.adresse_quartier?.message}
                    disabled={quartiersOptions.length === 0}
                    options={[
                      { value: "", label: "-- Sélectionner un quartier --" },
                      ...quartiersOptions.map((q) => ({ value: q, label: q })),
                    ]}
                    {...field}
                  />
                )}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <Controller name="bp" control={control} render={({ field }) => <FormInput label="Boîte Postale (BP)" type="text" {...field} />} />
              <Controller name="tel_domicile" control={control} render={({ field }) => <FormInput label="Téléphone domicile / Mobile" type="text" {...field} />} />
              <Controller name="tel_bureau" control={control} render={({ field }) => <FormInput label="Fax / Tél. bureau" type="text" {...field} />} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <Controller
                name="email"
                control={control}
                render={({ field }) => (
                  <FormInput
                    label="Email"
                    type="email"
                    error={!!errors.email}
                    helperText={errors.email?.message}
                    {...field}
                  />
                )}
              />
              <Controller name="profession_mere" control={control} render={({ field }) => <FormInput label="Profession / Localisation" type="text" {...field} />} />
            </div>
          </div>
        );

      case 2:
        return (
          <div>
            <h3 className="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b-2 border-indigo-600">🆔 Documents d'Identité</h3>
            
            <div className="mb-8">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                <span className="w-8 h-8 flex items-center justify-center bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">1</span>
                Document Principal (CNI, Passeport, etc.)
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                <Controller name="cni1" control={control} render={({ field }) => <FormInput label="N° Document" type="text" {...field} />} />
                <Controller name="du1" control={control} render={({ field }) => <FormInput label="Délivré le" type="date" {...field} />} />
                <Controller name="au1" control={control} render={({ field }) => <FormInput label="Expire le" type="date" {...field} />} />
                <Controller name="autre_preciser" control={control} render={({ field }) => <FormInput label="Autre (préciser)" type="text" {...field} />} />
              </div>
            </div>

            <div className="border-t-2 border-gray-300 pt-8">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                <span className="w-8 h-8 flex items-center justify-center bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">2</span>
                Document Secondaire (Optionnel)
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Controller name="cni2" control={control} render={({ field }) => <FormInput label="N° Document" type="text" {...field} />} />
                <Controller name="du2" control={control} render={({ field }) => <FormInput label="Délivré le" type="date" {...field} />} />
                <Controller name="au2" control={control} render={({ field }) => <FormInput label="Expire le" type="date" {...field} />} />
              </div>
            </div>
          </div>
        );

      case 3:
        return (
          <div>
            <h3 className="text-2xl font-bold text-gray-900 mb-8 pb-4 border-b-2 border-indigo-600">👤 Informations Personnelles</h3>

            {/* État Civil */}
            <div className="mb-8">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span className="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">1</span>
                Infos État Civil
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Controller name="date_naissance" control={control} render={({ field }) => <FormInput label="Date de naissance" type="date" {...field} />} />
                <Controller name="lieu_naissance" control={control} render={({ field }) => <FormInput label="Lieu de naissance" type="text" {...field} />} />
                <Controller name="nom_mere" control={control} render={({ field }) => <FormInput label="Nom de la mère" type="text" {...field} />} />
                <Controller name="nom_pere" control={control} render={({ field }) => <FormInput label="Nom du père" type="text" {...field} />} />
              </div>
            </div>

            {/* Situation Professionnelle */}
            <div className="mb-8 pt-8 border-t-2 border-gray-300">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span className="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">2</span>
                Situation Professionnelle
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Controller name="profession" control={control} render={({ field }) => <FormInput label="Profession" type="text" {...field} />} />
                <Controller name="employeur" control={control} render={({ field }) => <FormInput label="Employeur" type="text" {...field} />} />
                <Controller
                  name="situation_familiale"
                  control={control}
                  render={({ field }) => (
                    <FormSelect
                      label="Situation familiale"
                      options={[
                        { value: "", label: "-- Sélectionner --" },
                        { value: "marie", label: "Marié(e)" },
                        { value: "celibataire", label: "Célibataire" },
                        { value: "autres", label: "Autres" },
                      ]}
                      {...field}
                    />
                  )}
                />
                <Controller name="regime_matrimonial" control={control} render={({ field }) => <FormInput label="Régime matrimonial" type="text" {...field} />} />
              </div>
            </div>

            {/* Informations Conjoint */}
            <div className="mb-8 pt-8 border-t-2 border-gray-300">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span className="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">3</span>
                Informations Conjoint (si Marié(e))
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <Controller name="nom_epoux" control={control} render={({ field }) => <FormInput label="Nom & Prénom (époux)" type="text" {...field} />} />
                <Controller name="date_naissance_epoux" control={control} render={({ field }) => <FormInput label="Date de naissance (époux)" type="date" {...field} />} />
                <Controller name="lieu_naissance_epoux" control={control} render={({ field }) => <FormInput label="Lieu de naissance (époux)" type="text" {...field} />} />
                <Controller name="fonction_epoux" control={control} render={({ field }) => <FormInput label="Profession (époux)" type="text" {...field} />} />
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Controller name="adresse_epoux" control={control} render={({ field }) => <FormInput label="Employeur (époux)" type="text" {...field} />} />
                <Controller name="numero_epoux" control={control} render={({ field }) => <FormInput label="Téléphone (époux)" type="text" {...field} />} />
              </div>
            </div>

            {/* Paramètres et Catégories */}
            <div className="mb-8 pt-8 border-t-2 border-gray-300">
              <h4 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span className="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-full text-sm font-bold">4</span>
                Paramètres et Catégories Client
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <Controller name="nationalite" control={control} render={({ field }) => <FormInput label="Nationalité" type="text" {...field} />} />
                <Controller name="pays_residence" control={control} render={({ field }) => <FormInput label="Pays de résidence" type="text" {...field} />} />
                <Controller name="Qualite" control={control} render={({ field }) => <FormInput label="Qualité" type="text" {...field} />} />
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Controller name="gestionnaire" control={control} render={({ field }) => <FormInput label="Gestionnaire (id)" type="text" {...field} />} />
                <Controller name="profil" control={control} render={({ field }) => <FormInput label="Profil" type="text" {...field} />} />
              </div>
            </div>

            {/* Rôles et Options */}
            <div className="pt-8 border-t-2 border-gray-300">
              <h4 className="text-lg font-bold text-gray-800 mb-6">Rôles & Options</h4>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <Controller
                  name="client_checkbox"
                  control={control}
                  render={({ field }) => <FormCheckbox label="Client" checked={!!field.value} {...field} />}
                />
                <Controller
                  name="signataire"
                  control={control}
                  render={({ field }) => <FormCheckbox label="Signataire" checked={!!field.value} {...field} />}
                />
                <Controller
                  name="mantaire"
                  control={control}
                  render={({ field }) => <FormCheckbox label="Mantaire" checked={!!field.value} {...field} />}
                />
                <Controller
                  name="interdit_chequier"
                  control={control}
                  render={({ field }) => <FormCheckbox label="Interdit chéquier" checked={!!field.value} {...field} />}
                />
                <Controller
                  name="taxable"
                  control={control}
                  render={({ field }) => <FormCheckbox label="Taxable" checked={!!field.value} {...field} />}
                />
              </div>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 pb-12">
        <div className="w-full px-6 md:px-8 lg:px-10 py-8">
          {/* Header Section */}
          <div className="flex flex-col md:flex-row justify-between md:items-center gap-6 mb-8">
            <div>
              <h1 className="text-4xl md:text-5xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent mb-2">
                Nouveau Client
              </h1>
              <p className="text-gray-600 text-lg">Formulaire d'enregistrement en 4 étapes</p>
            </div>
            <button className="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl flex items-center gap-2 transition-all shadow-lg hover:shadow-2xl transform hover:scale-105 font-semibold w-fit hidden md:flex">
              ✓ Aperçu
            </button>
          </div>

          {/* Main Card */}
          <div className="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div className="p-8 md:p-12">
              {/* Stepper */}
              <div className="mb-10">
                <div className="flex gap-2 md:gap-6 overflow-x-auto pb-4">
                  {STEPS.map((label, index) => (
                    <div key={label} className="flex items-center flex-shrink-0">
                      <button
                        onClick={async () => {
                          if (index < activeStep) {
                            setActiveStep(index);
                          } else {
                            const valid = await trigger();
                            if (valid) setActiveStep(index);
                          }
                        }}
                        className={`flex items-center justify-center w-14 h-14 rounded-full font-bold text-lg transition-all shadow-lg ${
                          activeStep >= index
                            ? "bg-gradient-to-r from-indigo-600 to-blue-600 text-white scale-110"
                            : "bg-gray-200 text-gray-600 hover:bg-gray-300"
                        }`}
                      >
                        {index + 1}
                      </button>
                      <div className="hidden lg:block ml-4 text-sm font-bold text-gray-700 whitespace-nowrap max-w-[120px] truncate">{label}</div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Progress Bar */}
              <div className="mb-10">
                <div className="w-full bg-gray-300 rounded-full h-2.5 overflow-hidden shadow-sm">
                  <div
                    className="bg-gradient-to-r from-indigo-600 to-blue-600 h-full rounded-full transition-all duration-500"
                    style={{ width: `${((activeStep + 1) / STEPS.length) * 100}%` }}
                  />
                </div>
                <p className="text-sm text-gray-600 mt-4 text-center font-semibold">
                  Étape {activeStep + 1} de {STEPS.length} - {STEPS[activeStep]}
                </p>
              </div>

              {/* Form Content */}
              <form onSubmit={handleSubmit(onSubmit)}>
                <div className="mb-12 min-h-[450px]">{renderFormFields()}</div>

                {/* Navigation Buttons */}
                <div className="flex gap-4 pt-8 border-t-2 border-gray-200">
                  <button
                    type="button"
                    onClick={handleBack}
                    disabled={activeStep === 0}
                    className="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-bold hover:border-gray-400 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all text-center text-lg"
                  >
                    ← Précédent
                  </button>
                  {activeStep === STEPS.length - 1 ? (
                    <button
                      type="submit"
                      className="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-bold hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105 text-lg"
                    >
                      ✓ Enregistrer
                    </button>
                  ) : (
                    <button
                      type="button"
                      onClick={handleNext}
                      className="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105 text-lg"
                    >
                      Suivant →
                    </button>
                  )}
                </div>
              </form>
            </div>
          </div>

          {/* Footer Info */}
          <div className="mt-8 text-center text-gray-600 text-base">
            <p className="font-medium">Les champs marqués avec * sont obligatoires</p>
          </div>
        </div>
      </div>
    </>
  );
}
