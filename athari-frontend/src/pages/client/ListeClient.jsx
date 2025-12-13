import React, { useState } from 'react';
import {
    Paper, Table, TableBody, TableCell, TableContainer,
    TableHead, TableRow, TableSortLabel, TablePagination, Box,
    IconButton, Typography, TextField, InputAdornment, useTheme,
    Button,
} from '@mui/material';
import Header from '../../components/layout/Header';
import { visuallyHidden } from '@mui/utils';
import {
    Edit as EditIcon,
    Visibility as VisibilityIcon,
    Delete as DeleteIcon,
    Search as SearchIcon,
    Add as AddIcon,
} from '@mui/icons-material';
// 1. IMPORT DU HOOK DE NAVIGATION
import { useNavigate } from 'react-router-dom'; 

// --- Données du Tableau ---

const initialData = [
    { id: 1, nom: 'charles', typepersone: 'physique', date_naissance: '18/01/2004', numerocni: 'kit12', telephone: '677255342', solde: 20000.50, datecreation: '12/05/2020' },
    { id: 2, nom: 'kenfack francois', typepersone: 'physique', date_naissance: '18/01/2004', numerocni: 'kit15', telephone: '677255342', solde: 15000.50, datecreation: '12/05/2020' },
    { id: 3, nom: 'meikeu vital', typepersone: 'physique', date_naissance: '18/01/2004', numerocni: 'kit12', telephone: '677255342', solde: 5000.50, datecreation: '12/05/2020' },
    { id: 4, nom: 'koffi', typepersone: 'physique', date_naissance: '18/01/2004', numerocni: 'kit12', telephone: '677255342', solde: 100000.50, datecreation: '12/05/2020' },
];

const headCells = [
    { id: 'nom', label: 'Nom et prenom' },
    { id: 'typepersone', label: 'Type de personne' },
    { id: 'date_naissance', label: 'Date de naissance' },
    { id: 'numerocni', label: 'Numéro CNI' },
    { id: 'telephone', label: 'Téléphone' },
    { id: 'solde', label: 'Solde du compte' },
    { id: 'actions', label: 'Actions', disableSorting: true },
];

// --- Fonctions de Tri (Inchngées) ---

function descendingComparator(a, b, orderBy) {
    if (b[orderBy] < a[orderBy]) return -1;
    if (b[orderBy] > a[orderBy]) return 1;
    return 0;
}
function getComparator(order, orderBy) {
    return order === 'desc'
        ? (a, b) => descendingComparator(a, b, orderBy)
        : (a, b) => -descendingComparator(a, b, orderBy);
}
function stableSort(array, comparator) {
    const stabilizedThis = array.map((el, index) => [el, index]);
    stabilizedThis.sort((a, b) => {
        const order = comparator(a[0], b[0]);
        if (order !== 0) return order;
        return a[1] - b[1];
    });
    return stabilizedThis.map((el) => el[0]);
}

// --- Fonctions d'Action (simulées) ---

const handleView = (id) => { console.log(`Afficher le client ID: ${id}`); };
const handleEdit = (id) => { console.log(`Éditer le client ID: ${id}`); };
const handleDelete = (id) => { console.log(`Supprimer le client ID: ${id}`); };

// ------------------------------------------------------------------

export default function ListeClient() {
    // Hooks Material-UI et états
    const theme = useTheme(); 
    const navigate = useNavigate(); // 2. INITIALISATION DU HOOK DE NAVIGATION

    const [order, setOrder] = useState('asc');
    const [orderBy, setOrderBy] = useState('nom');
    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(5);
    const [searchTerm, setSearchTerm] = useState('');

    const handleRequestSort = (property) => {
        const isActionsColumn = headCells.find(cell => cell.id === property)?.disableSorting;
        if (isActionsColumn) return;

        const isAsc = orderBy === property && order === 'asc';
        setOrder(isAsc ? 'desc' : 'asc');
        setOrderBy(property);
    };

    const handleChangePage = (event, newPage) => { setPage(newPage); };
    const handleChangeRowsPerPage = (event) => {
        setRowsPerPage(parseInt(event.target.value, 10));
        setPage(0);
    };

    // 3. FONCTION DE NAVIGATION POUR LE BOUTON
    const handleAddClient = () => {
        // Le chemin doit correspondre à celui que vous avez configuré pour le FormClient
        navigate('/client/creer'); 
        console.log("Action: Navigation vers le formulaire d'ajout de client");
    };

    // Filtrage des données
    const filteredData = initialData.filter(client => 
        client.nom.toLowerCase().includes(searchTerm.toLowerCase())
    );

    // Tri et pagination
    const visibleRows = stableSort(filteredData, getComparator(order, orderBy))
        .slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

    return (
        <Box sx={{ p: 3, pt: 0 }}>
                          <Header/>

            <Typography variant="h4" gutterBottom sx={{ mb: 3, color: 'primary.main', fontWeight: 600 }}>
                
            </Typography>

            {/* Zone d'actions (Ajouter Client + Recherche) */}
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
                {/* BOUTON AJOUTER CLIENT */}
                <Button
                    variant="contained"
                    color="primary" // Utilise la couleur principale (bleu foncé) du thème
                    startIcon={<AddIcon />}
                    onClick={handleAddClient} // 4. LIEN DE NAVIGATION
                    sx={{
                        fontWeight: 'bold', 
                        textTransform: 'none',
                        minWidth: 200, 
                        height: 40 
                    }}
                >
                    Ajouter un nouveau client
                </Button>

                {/* Champ de recherche/filtre */}
                <TextField
                    variant="outlined"
                    size="small"
                    placeholder="Rechercher un client..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchIcon />
                            </InputAdornment>
                        ),
                    }}
                    sx={{ width: 300 }}
                />
            </Box>


            <Paper sx={{ width: '100%', mb: 2, borderRadius: 2, boxShadow: 3 }}>
                <TableContainer sx={{ maxHeight: 600 }}>
                    <Table stickyHeader aria-label="tableau des clients">
                        
                        {/* EN-TÊTE DU TABLEAU AVEC LE FOND BLEU FONCÉ */}
                        <TableHead>
                            <TableRow>
                                {headCells.map((headCell) => (
                                    <TableCell
                                        key={headCell.id}
                                        align={headCell.id === 'actions' ? 'center' : 'left'}
                                        sortDirection={orderBy === headCell.id ? order : false}
                                        // Utilisation de primary.main (Bleu Foncé)
                                        sx={{
                                            backgroundColor: theme.palette.primary.main, 
                                            color: theme.palette.primary.contrastText, // Texte blanc
                                            fontWeight: 'bold',
                                            
                                            // Assurer la couleur blanche pour les éléments triables
                                            '& .MuiTableSortLabel-root': {
                                                color: theme.palette.primary.contrastText,
                                                '&:hover': {
                                                    color: theme.palette.primary.contrastText, 
                                                },
                                            },
                                            '& .MuiTableSortLabel-icon': {
                                                color: `${theme.palette.primary.contrastText} !important`,
                                            },
                                        }}
                                    >
                                        {headCell.disableSorting ? (
                                            <Box>{headCell.label}</Box>
                                        ) : (
                                            <TableSortLabel
                                                active={orderBy === headCell.id}
                                                direction={orderBy === headCell.id ? order : 'asc'}
                                                onClick={() => handleRequestSort(headCell.id)}
                                            >
                                                {headCell.label}
                                                {orderBy === headCell.id ? (
                                                    <Box component="span" sx={visuallyHidden}>
                                                        {order === 'desc' ? 'trié par ordre décroissant' : 'trié par ordre croissant'}
                                                    </Box>
                                                ) : null}
                                            </TableSortLabel>
                                        )}
                                    </TableCell>
                                ))}
                            </TableRow>
                        </TableHead>

                        <TableBody>
                            {visibleRows.map((row) => (
                                <TableRow
                                    hover
                                    tabIndex={-1}
                                    key={row.id}
                                    sx={{ 
                                        // Alternance de couleur de ligne
                                        '&:nth-of-type(odd)': {
                                            backgroundColor: theme.palette.action.hover,
                                        },
                                    }}
                                >
                                    {/* Colonnes de données */}
                                    <TableCell>{row.nom}</TableCell>
                                    <TableCell>{row.typepersone}</TableCell>
                                    <TableCell>{row.date_naissance}</TableCell>
                                    <TableCell>{row.numerocni}</TableCell>
                                    <TableCell>{row.telephone}</TableCell>
                                    <TableCell>
                                        {/* Formatage du solde en FCFA */}
                                        **{row.solde.toLocaleString('fr-FR', { style: 'currency', currency: 'XAF', minimumFractionDigits: 0 })}**
                                    </TableCell>

                                    {/* Colonne Actions */}
                                    <TableCell align="center">
                                        <IconButton
                                            size="small"
                                            onClick={() => handleView(row.id)}
                                            aria-label="Afficher"
                                            color="primary"
                                        >
                                            <VisibilityIcon fontSize="small" />
                                        </IconButton>
                                        <IconButton
                                            size="small"
                                            onClick={() => handleEdit(row.id)}
                                            aria-label="Éditer"
                                            color="default"
                                        >
                                            <EditIcon fontSize="small" />
                                        </IconButton>
                                        <IconButton
                                            size="small"
                                            onClick={() => handleDelete(row.id)}
                                            aria-label="Supprimer"
                                            color="error"
                                        >
                                            <DeleteIcon fontSize="small" />
                                        </IconButton>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>

                <TablePagination
                    rowsPerPageOptions={[5, 10, 25]}
                    component="div"
                    count={initialData.length}
                    rowsPerPage={rowsPerPage}
                    page={page}
                    onPageChange={handleChangePage}
                    onRowsPerPageChange={handleChangeRowsPerPage}
                    labelRowsPerPage="Lignes par page :"
                />
            </Paper>
        </Box>
    );
}