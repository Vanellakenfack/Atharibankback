import React, { useState } from 'react';
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

// --- Fonctions de Tri ---

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
    const navigate = useNavigate();
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

    const handleChangePage = (newPage) => { setPage(newPage); };
    const handleChangeRowsPerPage = (newPerPage) => {
        setRowsPerPage(newPerPage);
        setPage(0);
    };

    // Filtrage des données
    const filteredData = initialData.filter(client => 
        client.nom.toLowerCase().includes(searchTerm.toLowerCase())
    );

    // Tri et pagination
    const visibleRows = stableSort(filteredData, getComparator(order, orderBy))
        .slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

    return (
        <div className="p-6">
            <h2 className="text-2xl font-bold mb-4">Liste des Clients</h2>

            <div className="flex justify-between items-center mb-4">
                <div />
                <div className="flex items-center gap-2">
                    <input
                        type="search"
                        className="border rounded px-3 py-2 w-72"
                        placeholder="Rechercher un client..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                    <button
                        onClick={() => navigate('/client/creer')}
                        className="bg-blue-600 text-white px-3 py-2 rounded"
                    >
                        + Nouveau
                    </button>
                </div>
            </div>

            <div className="bg-white rounded shadow overflow-hidden">
                <table className="min-w-full divide-y">
                    <thead className="bg-blue-600 text-white">
                        <tr>
                            {headCells.map((h) => (
                                <th key={h.id} className="px-4 py-3 text-left text-sm font-medium">{h.label}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {visibleRows.map((row) => (
                            <tr key={row.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">{row.nom}</td>
                                <td className="px-4 py-3">{row.typepersone}</td>
                                <td className="px-4 py-3">{row.date_naissance}</td>
                                <td className="px-4 py-3">{row.numerocni}</td>
                                <td className="px-4 py-3">{row.telephone}</td>
                                <td className="px-4 py-3">{row.solde.toLocaleString('fr-FR', { style: 'currency', currency: 'XAF', minimumFractionDigits: 0 })}</td>
                                <td className="px-4 py-3 text-center">
                                    <button onClick={() => handleView(row.id)} className="text-sky-600 mr-2">Voir</button>
                                    <button onClick={() => handleEdit(row.id)} className="text-green-600 mr-2">Éditer</button>
                                    <button onClick={() => handleDelete(row.id)} className="text-red-600">Supprimer</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-between mt-4">
                <div className="text-sm text-gray-600">Total: {initialData.length} clients</div>
                <div className="flex items-center gap-2">
                    <button
                        onClick={() => handleChangePage(Math.max(0, page - 1))}
                        className="px-3 py-1 border rounded"
                        disabled={page === 0}
                    >Préc</button>
                    <div className="text-sm">Page {page + 1}</div>
                    <button
                        onClick={() => handleChangePage(page + 1)}
                        className="px-3 py-1 border rounded"
                        disabled={(page + 1) * rowsPerPage >= filteredData.length}
                    >Suiv</button>
                    <select
                        value={rowsPerPage}
                        onChange={(e) => handleChangeRowsPerPage(Number(e.target.value))}
                        className="border rounded px-2 py-1"
                    >
                        <option value={5}>5</option>
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                    </select>
                </div>
            </div>
        </div>
    );
}