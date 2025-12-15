import React, { useState, useEffect } from 'react';
import {
    Box, Button, Container, Typography, IconButton, Dialog,
    DialogTitle, DialogContent, DialogActions, TextField,
    MenuItem, FormControl, InputLabel, Select, Paper, Chip, Tooltip
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, Refresh as RefreshIcon } from '@mui/icons-material';
import { useSnackbar } from 'notistack';
import api from '../../services/api/axios'; // Import de la config axios créée plus haut

// Liste des rôles EXACTEMENT comme dans votre Seeder PHP
const ROLES_AVAILABLE = [
    'DG',
    'Chef Comptable',
    'Chef d\'Agence (CA)',
    'Assistant Juridique (AJ)',
    'Assistant Comptable (AC)',
    'Caissière',
    'Agent de Crédit (AC)',
    'Collecteur',
    'Audit/Contrôle (IV)',
    'Admin'
];

const UserTable = () => {
    const { enqueueSnackbar } = useSnackbar();
    
    // --- États ---
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [openDialog, setOpenDialog] = useState(false);
    const [openDeleteDialog, setOpenDeleteDialog] = useState(false);
    
    // État du formulaire
    const [formData, setFormData] = useState({
        id: null,
        name: '',
        email: '',
        password: '', // Optionnel en édition
        role: ''
    });
    const [userToDelete, setUserToDelete] = useState(null);

    // --- Chargement des données ---
    const fetchUsers = async () => {
        setLoading(true);
        try {
            // Suppose une route Laravel: Route::get('/users', [UserController::class, 'index']);
            const response = await api.get('/users');
            // Adaptez selon la structure de retour de votre API (ex: response.data.data ou response.data)
            setUsers(response.data.data || response.data); 
        } catch (error) {
            enqueueSnackbar('Erreur lors du chargement des utilisateurs', { variant: 'error' });
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUsers();
    }, []);

    // --- Gestionnaires d'événements ---

    const handleOpenDialog = (user = null) => {
        if (user) {
            // Mode Édition
            setFormData({
                id: user.id,
                name: user.name,
                email: user.email,
                password: '', // On laisse vide pour ne pas écraser si pas modifié
                role: user.roles && user.roles.length > 0 ? user.roles[0].name : '' // Spatie retourne les rôles en array
            });
        } else {
            // Mode Création
            setFormData({ id: null, name: '', email: '', password: '', role: '' });
        }
        setOpenDialog(true);
    };

    const handleCloseDialog = () => {
        setOpenDialog(false);
        setFormData({ id: null, name: '', email: '', password: '', role: '' });
    };

    const handleSave = async () => {
        try {
            // Validation basique
            if (!formData.name || !formData.email || !formData.role) {
                enqueueSnackbar('Veuillez remplir les champs obligatoires', { variant: 'warning' });
                return;
            }

            if (formData.id) {
                // --- MISE À JOUR (PUT) ---
                // Route::put('/users/{id}', ...);
                const payload = { ...formData };
                if (!payload.password) delete payload.password; // Ne pas envoyer si vide

                await api.put(`/users/${formData.id}`, payload);
                enqueueSnackbar('Utilisateur mis à jour avec succès', { variant: 'success' });
            } else {
                // --- CRÉATION (POST) ---
                // Route::post('/users', ...);
                if (!formData.password) {
                    enqueueSnackbar('Le mot de passe est obligatoire pour la création', { variant: 'warning' });
                    return;
                }
                await api.post('/users', formData);
                enqueueSnackbar('Utilisateur créé avec succès', { variant: 'success' });
            }

            handleCloseDialog();
            fetchUsers(); // Rafraîchir la liste
        } catch (error) {
            const msg = error.response?.data?.message || 'Une erreur est survenue';
            enqueueSnackbar(msg, { variant: 'error' });
        }
    };

    const handleDeleteClick = (id) => {
        setUserToDelete(id);
        setOpenDeleteDialog(true);
    };

    const confirmDelete = async () => {
        try {
            // Route::delete('/users/{id}', ...);
            await api.delete(`/users/${userToDelete}`);
            enqueueSnackbar('Utilisateur supprimé', { variant: 'success' });
            setOpenDeleteDialog(false);
            fetchUsers();
        } catch (error) {
            enqueueSnackbar('Erreur lors de la suppression', { variant: 'error' });
        }
    };

    // --- Configuration des colonnes DataGrid ---
    const columns = [
        { field: 'id', headerName: 'ID', width: 70 },
        { field: 'name', headerName: 'Nom', flex: 1 },
        { field: 'email', headerName: 'Email', flex: 1 },
        { 
            field: 'role', 
            headerName: 'Rôle', 
            flex: 1,
            renderCell: (params) => {
                // Gestion de l'affichage du rôle (Spatie renvoie souvent un tableau d'objets roles)
                const roleName = params.row.roles?.[0]?.name || params.row.role || 'N/A';
                return <Chip label={roleName} color="primary" variant="outlined" size="small" />;
            }
        },
        {
            field: 'actions',
            headerName: 'Actions',
            width: 150,
            sortable: false,
            renderCell: (params) => (
                <Box>
                    <Tooltip title="Modifier">
                        <IconButton color="primary" onClick={() => handleOpenDialog(params.row)}>
                            <EditIcon />
                        </IconButton>
                    </Tooltip>
                    <Tooltip title="Supprimer">
                        <IconButton color="error" onClick={() => handleDeleteClick(params.row.id)}>
                            <DeleteIcon />
                        </IconButton>
                    </Tooltip>
                </Box>
            )
        }
    ];

    return (
        <Container maxWidth="lg" sx={{ mt: 4, mb: 4 }}>
            {/* En-tête */}
            <Paper sx={{ p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h5" component="h1">
                    Gestion des Utilisateurs
                </Typography>
                <Box>
                    <Button 
                        startIcon={<RefreshIcon />} 
                        onClick={fetchUsers} 
                        sx={{ mr: 1 }}
                    >
                        Actualiser
                    </Button>
                    <Button 
                        variant="contained" 
                        startIcon={<AddIcon />} 
                        onClick={() => handleOpenDialog()}
                    >
                        Ajouter
                    </Button>
                </Box>
            </Paper>

            {/* Tableau des données */}
            <Paper sx={{ height: 500, width: '100%' }}>
                <DataGrid
                    rows={users}
                    columns={columns}
                    pageSize={10}
                    rowsPerPageOptions={[5, 10, 20]}
                    checkboxSelection={false}
                    disableSelectionOnClick
                    loading={loading}
                    getRowId={(row) => row.id}
                />
            </Paper>

            {/* Dialog Création / Édition */}
            <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="sm" fullWidth>
                <DialogTitle>
                    {formData.id ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'}
                </DialogTitle>
                <DialogContent dividers>
                    <Box component="form" sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                        <TextField
                            label="Nom complet"
                            fullWidth
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        />
                        <TextField
                            label="Email"
                            type="email"
                            fullWidth
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        />
                        <FormControl fullWidth>
                            <InputLabel>Rôle</InputLabel>
                            <Select
                                value={formData.role}
                                label="Rôle"
                                onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                            >
                                {ROLES_AVAILABLE.map((role) => (
                                    <MenuItem key={role} value={role}>
                                        {role}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <TextField
                            label="Mot de passe"
                            type="password"
                            fullWidth
                            helperText={formData.id ? "Laisser vide pour conserver l'actuel" : "Requis pour la création"}
                            value={formData.password}
                            onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        />
                    </Box>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleCloseDialog} color="inherit">Annuler</Button>
                    <Button onClick={handleSave} variant="contained" color="primary">
                        Enregistrer
                    </Button>
                </DialogActions>
            </Dialog>

            {/* Dialog Confirmation Suppression */}
            <Dialog open={openDeleteDialog} onClose={() => setOpenDeleteDialog(false)}>
                <DialogTitle>Confirmer la suppression</DialogTitle>
                <DialogContent>
                    <Typography>
                        Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
                    </Typography>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setOpenDeleteDialog(false)}>Annuler</Button>
                    <Button onClick={confirmDelete} color="error" variant="contained">
                        Supprimer
                    </Button>
                </DialogActions>
            </Dialog>
        </Container>
    );
};

export default UserTable;