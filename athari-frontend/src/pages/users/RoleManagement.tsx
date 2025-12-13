import React, { useState } from 'react';
import Swal from 'sweetalert2';
import { mockRoles, permissionCategories } from './data/mockData';
import { FiShield, FiPlus, FiEdit, FiTrash2 } from 'react-icons/fi';

const RoleManagement = () => {
  const [roles, setRoles] = useState(mockRoles);
  const [selectedRole, setSelectedRole] = useState(roles[0]);
  const [showRoleForm, setShowRoleForm] = useState(false);
  const [newRoleName, setNewRoleName] = useState('');

  const handleRoleSelect = (role) => {
    setSelectedRole(role);
  };

  const handleAddRole = () => {
    if (!newRoleName.trim()) {
      Swal.fire('Erreur', 'Veuillez saisir un nom de rôle', 'error');
      return;
    }

    if (roles.find(r => r.name === newRoleName)) {
      Swal.fire('Erreur', 'Ce rôle existe déjà', 'error');
      return;
    }

    const newRole = {
      name: newRoleName,
      permissions: []
    };

    setRoles([...roles, newRole]);
    setSelectedRole(newRole);
    setNewRoleName('');
    setShowRoleForm(false);
    Swal.fire('Succès', 'Rôle créé avec succès', 'success');
  };

  const handleDeleteRole = (roleName) => {
    if (roleName === 'Admin' || roleName === 'DG') {
      Swal.fire('Erreur', 'Ce rôle ne peut pas être supprimé', 'error');
      return;
    }

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
        setRoles(roles.filter(r => r.name !== roleName));
        if (selectedRole.name === roleName) {
          setSelectedRole(roles[0]);
        }
        Swal.fire('Supprimé!', 'Rôle supprimé avec succès.', 'success');
      }
    });
  };

  const togglePermission = (permission) => {
    if (!selectedRole) return;

    const updatedPermissions = selectedRole.permissions.includes(permission)
      ? selectedRole.permissions.filter(p => p !== permission)
      : [...selectedRole.permissions, permission];

    const updatedRole = { ...selectedRole, permissions: updatedPermissions };
    
    setRoles(roles.map(r => r.name === selectedRole.name ? updatedRole : r));
    setSelectedRole(updatedRole);
  };

  const toggleAllPermissionsInCategory = (category, enable) => {
    if (!selectedRole) return;

    const categoryPermissions = permissionCategories[category] || [];
    let updatedPermissions;

    if (enable) {
      updatedPermissions = [...new Set([...selectedRole.permissions, ...categoryPermissions])];
    } else {
      updatedPermissions = selectedRole.permissions.filter(
        p => !categoryPermissions.includes(p)
      );
    }

    const updatedRole = { ...selectedRole, permissions: updatedPermissions };
    
    setRoles(roles.map(r => r.name === selectedRole.name ? updatedRole : r));
    setSelectedRole(updatedRole);
  };

  const isAllPermissionsInCategoryEnabled = (category) => {
    const categoryPermissions = permissionCategories[category] || [];
    return categoryPermissions.every(p => selectedRole.permissions.includes(p));
  };

  const isSomePermissionsInCategoryEnabled = (category) => {
    const categoryPermissions = permissionCategories[category] || [];
    return categoryPermissions.some(p => selectedRole.permissions.includes(p));
  };

  return (
    <div className="container mx-auto">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Gestion des Rôles et Permissions</h1>
          <p className="text-gray-600">Définissez les rôles et leurs permissions</p>
        </div>
        <button
          onClick={() => setShowRoleForm(true)}
          className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
        >
          <FiPlus className="mr-2" />
          Nouveau Rôle
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Liste des rôles */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-lg shadow">
            <div className="p-4 border-b">
              <h3 className="font-semibold text-gray-800">Rôles disponibles</h3>
            </div>
            <div className="max-h-96 overflow-y-auto">
              {roles.map((role) => (
                <div
                  key={role.name}
                  className={`p-4 border-b cursor-pointer hover:bg-gray-50 ${
                    selectedRole?.name === role.name ? 'bg-blue-50 border-l-4 border-blue-500' : ''
                  }`}
                  onClick={() => handleRoleSelect(role)}
                >
                  <div className="flex justify-between items-center">
                    <div className="flex items-center">
                      <FiShield className={`mr-3 ${
                        role.name === 'Admin' || role.name === 'DG' 
                          ? 'text-red-500' 
                          : 'text-blue-500'
                      }`} />
                      <div>
                        <h4 className="font-medium text-gray-900">{role.name}</h4>
                        <p className="text-sm text-gray-500">
                          {role.permissions.length} permission(s)
                        </p>
                      </div>
                    </div>
                    {(role.name !== 'Admin' && role.name !== 'DG') && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDeleteRole(role.name);
                        }}
                        className="text-red-500 hover:text-red-700 p-1"
                        title="Supprimer"
                      >
                        <FiTrash2 size={16} />
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Détails du rôle et permissions */}
        <div className="lg:col-span-3">
          {selectedRole ? (
            <div className="bg-white rounded-lg shadow">
              <div className="p-6 border-b">
                <div className="flex justify-between items-center">
                  <div>
                    <h2 className="text-xl font-bold text-gray-800 flex items-center">
                      <FiShield className="mr-3 text-blue-500" />
                      {selectedRole.name}
                    </h2>
                    <p className="text-gray-600 mt-1">
                      Gestion des permissions pour le rôle {selectedRole.name}
                    </p>
                  </div>
                  <div className="text-sm">
                    <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                      {selectedRole.permissions.length} permissions
                    </span>
                  </div>
                </div>
              </div>

              <div className="p-6">
                <div className="space-y-6">
                  {Object.entries(permissionCategories).map(([category, permissions]) => (
                    <div key={category} className="border rounded-lg p-4">
                      <div className="flex justify-between items-center mb-3">
                        <h3 className="font-semibold text-gray-800">{category}</h3>
                        <div className="flex space-x-2">
                          <button
                            onClick={() => toggleAllPermissionsInCategory(category, true)}
                            className={`text-xs px-2 py-1 rounded ${
                              isAllPermissionsInCategoryEnabled(category)
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                            }`}
                          >
                            Tout cocher
                          </button>
                          <button
                            onClick={() => toggleAllPermissionsInCategory(category, false)}
                            className={`text-xs px-2 py-1 rounded ${
                              !isSomePermissionsInCategoryEnabled(category)
                                ? 'bg-red-100 text-red-800'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                            }`}
                          >
                            Tout décocher
                          </button>
                        </div>
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                        {permissions.map((permission) => (
                          <div key={permission} className="flex items-center">
                            <input
                              type="checkbox"
                              id={`perm-${permission}`}
                              checked={selectedRole.permissions.includes(permission)}
                              onChange={() => togglePermission(permission)}
                              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label
                              htmlFor={`perm-${permission}`}
                              className="ml-2 text-sm text-gray-700"
                            >
                              {permission}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-lg shadow p-12 text-center">
              <FiShield className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">Sélectionnez un rôle</h3>
              <p className="mt-1 text-sm text-gray-500">
                Choisissez un rôle dans la liste pour voir et modifier ses permissions
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Modal pour ajouter un rôle */}
      {showRoleForm && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-semibold text-gray-800">Nouveau Rôle</h2>
              <button
                onClick={() => setShowRoleForm(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <FiEdit size={24} />
              </button>
            </div>

            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nom du rôle *
              </label>
              <input
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ex: Superviseur"
                value={newRoleName}
                onChange={(e) => setNewRoleName(e.target.value)}
              />
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setShowRoleForm(false)}
                className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
              >
                Annuler
              </button>
              <button
                onClick={handleAddRole}
                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
              >
                Créer le rôle
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default RoleManagement;