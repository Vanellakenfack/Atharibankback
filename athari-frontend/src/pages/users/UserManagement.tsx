import React, { useState, useMemo } from 'react';
import Swal from 'sweetalert2';

import { mockUsers, mockRoles } from './data/mockData';
import UserForm from '../../components/users/UserForm';

import { FiUserPlus, FiSearch, FiFilter } from 'react-icons/fi';

const roles = mockRoles;

const UserManagement = () => {
    const [users, setUsers] = useState(mockUsers);
    const [showForm, setShowForm] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [roleFilter, setRoleFilter] = useState('all');

    const handleAddUser = () => {
        setEditingUser(null);
        setShowForm(true);
    };

    const handleEditUser = (user) => {
        setEditingUser(user);
        setShowForm(true);
    };

    const handleSaveUser = (userData) => {
        if (editingUser) {
            setUsers(users.map(u => u.id === editingUser.id ? { ...u, ...userData } : u));
            Swal.fire('Succès', 'Utilisateur modifié avec succès', 'success');
        } else {
            const newUser = {
                id: Math.max(...users.map(u => u.id), 0) + 1,
                ...userData,
                createdAt: new Date().toISOString().split('T')[0],
                lastLogin: null,
                status: 'active'
            };
            setUsers([...users, newUser]);
            Swal.fire('Succès', 'Utilisateur ajouté avec succès', 'success');
        }

        setShowForm(false);
        setEditingUser(null);
    };

    const handleDeleteUser = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: "Vous ne pourrez pas annuler cette action!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer!',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                setUsers(users.filter(user => user.id !== id));
                Swal.fire('Supprimé!', 'Utilisateur supprimé avec succès.', 'success');
            }
        });
    };

    const handleToggleStatus = (id) => {
        setUsers(users.map(user =>
            user.id === id ? { ...user, status: user.status === 'active' ? 'inactive' : 'active' } : user
        ));
    };

    const filteredUsers = useMemo(() => {
        return users.filter(user => {
            const matchesSearch = user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.email.toLowerCase().includes(searchTerm.toLowerCase());

            const matchesStatus = statusFilter === 'all' || user.status === statusFilter;
            const matchesRole = roleFilter === 'all' || user.roles.includes(roleFilter);

            return matchesSearch && matchesStatus && matchesRole;
        });
    }, [users, searchTerm, statusFilter, roleFilter]);

    if (showForm) {
        return (
            <div className="container-fluid py-4">
                <UserForm
                    user={editingUser}
                    roles={roles}
                    onSave={handleSaveUser}
                    onCancel={() => { setShowForm(false); setEditingUser(null); }}
                />
            </div>
        );
    }

    return (
        <div className="p-4">
            <div className="w-full flex justify-between items-center mb-6 mt-1 pl-3">
                <div>
                    <h3 className="text-lg font-bold text-slate-800">Gestion des Utilisateurs</h3>
                    <p className="text-slate-500">Aperçu de tous les utilisateurs du système.</p>
                </div>
                <div className="flex items-center space-x-4">
                    <div className="flex items-center space-x-2">
                        <FiFilter className="text-slate-500" />
                        <select 
                            className="bg-white px-3 py-1 text-sm border border-slate-200 rounded focus:outline-none focus:border-slate-400"
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                        >
                            <option value="all">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>

                        <select 
                            className="bg-white px-3 py-1 text-sm border border-slate-200 rounded focus:outline-none focus:border-slate-400"
                            value={roleFilter}
                            onChange={(e) => setRoleFilter(e.target.value)}
                        >
                            <option value="all">Tous les rôles</option>
                            {roles.map(role => (
                                <option key={role.id} value={role.name}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    
                    <button 
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded flex items-center"
                        onClick={handleAddUser}
                    >
                        <FiUserPlus className="mr-2" />
                        Ajouter un Utilisateur
                    </button>
                </div>
            </div>

            <div className="w-full flex justify-between items-center mb-3 mt-1 pl-3">
                <div className="ml-3 w-full max-w-sm min-w-[200px] relative">
                    <div className="relative">
                        <input
                            className="bg-white w-full pr-11 h-10 pl-3 py-2 bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-400 shadow-sm focus:shadow-md"
                            placeholder="Rechercher un utilisateur..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                        <button
                            className="absolute h-8 w-8 right-1 top-1 my-auto px-2 flex items-center bg-white rounded"
                            type="button"
                        >
                            <FiSearch className="w-5 h-5 text-slate-600" />
                        </button>
                    </div>
                </div>
            </div>
            
            <div className="relative flex flex-col w-full h-full overflow-scroll text-gray-700 bg-white shadow-md rounded-lg bg-clip-border">
                <table className="w-full text-left table-auto min-w-max">
                    <thead>
                        <tr>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Nom
                                </p>
                            </th>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Email
                                </p>
                            </th>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Rôle(s)
                                </p>
                            </th>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Statut
                                </p>
                            </th>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Date de création
                                </p>
                            </th>
                            <th className="p-4 border-b border-slate-300 bg-slate-50">
                                <p className="block text-sm font-normal leading-none text-slate-500">
                                    Actions
                                </p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredUsers.map(user => (
                            <tr key={user.id} className="hover:bg-slate-50 border-b border-slate-200">
                                <td className="p-4 py-5">
                                    <p className="block font-semibold text-sm text-slate-800">{user.name}</p>
                                </td>
                                <td className="p-4 py-5">
                                    <p className="block text-sm text-slate-800">{user.email}</p>
                                </td>
                                <td className="p-4 py-5">
                                    <div className="flex flex-wrap gap-1">
                                        {user.roles.map((role, index) => (
                                            <span 
                                                key={index}
                                                className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                            >
                                                {role}
                                            </span>
                                        ))}
                                    </div>
                                </td>
                                <td className="p-4 py-5">
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                        user.status === 'active' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-red-100 text-red-800'
                                    }`}>
                                        {user.status === 'active' ? 'Actif' : 'Inactif'}
                                    </span>
                                </td>
                                <td className="p-4 py-5">
                                    <p className="block text-sm text-slate-800">{user.createdAt}</p>
                                </td>
                                <td className="p-4 py-5">
                                    <div className="block text-center flex space-x-2 justify-center">
                                        <button 
                                            className="text-blue-600 hover:text-blue-800"
                                            onClick={() => handleEditUser(user)}
                                            title="Modifier"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                                                <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z" />
                                                <path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z" />
                                            </svg>
                                        </button>
                                        <button 
                                            className="text-red-600 hover:text-red-800"
                                            onClick={() => handleDeleteUser(user.id)}
                                            title="Supprimer"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                                                <path fillRule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                        <button 
                                            className="text-green-600 hover:text-green-800"
                                            onClick={() => handleToggleStatus(user.id)}
                                            title={user.status === 'active' ? 'Désactiver' : 'Activer'}
                                        >
                                            {user.status === 'active' ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                                                    <path fillRule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3 10.5a.75.75 0 000-1.5H9a.75.75 0 000 1.5h6z" clipRule="evenodd" />
                                                </svg>
                                            ) : (
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                                                    <path fillRule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clipRule="evenodd" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default UserManagement;