import React from 'react';
import { Edit2, Trash2, Download, Printer } from 'lucide-react';

interface CompteViewProps {
  compte?: any;
  onEdit?: () => void;
  onDelete?: () => void;
  onDownload?: () => void;
  onPrint?: () => void;
}

const CompteView: React.FC<CompteViewProps> = ({ 
  compte, 
  onEdit, 
  onDelete, 
  onDownload, 
  onPrint 
}) => {
  if (!compte) {
    return <div className="p-4">Aucun compte sélectionné</div>;
  }

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-4">{compte.numero}</h2>
        
        <div className="grid grid-cols-2 gap-4 mb-6">
          <div>
            <p className="text-sm text-gray-600">Type</p>
            <p className="text-lg font-semibold text-gray-900">{compte.type}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Solde</p>
            <p className="text-lg font-semibold text-indigo-600">{compte.solde} €</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Devise</p>
            <p className="text-lg font-semibold text-gray-900">{compte.devise || 'EUR'}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Status</p>
            <p className="text-lg font-semibold text-green-600">Actif</p>
          </div>
        </div>

        <div className="border-t pt-4 mb-6">
          <p className="text-sm text-gray-600 mb-2">Détails supplémentaires</p>
          <p className="text-gray-700">{compte.description || 'Pas de description'}</p>
        </div>
      </div>

      <div className="flex gap-2">
        <button
          onClick={onEdit}
          className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Edit2 size={18} />
          Modifier
        </button>
        <button
          onClick={onDownload}
          className="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Download size={18} />
          Télécharger
        </button>
        <button
          onClick={onPrint}
          className="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Printer size={18} />
          Imprimer
        </button>
        <button
          onClick={onDelete}
          className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-2 transition"
        >
          <Trash2 size={18} />
          Supprimer
        </button>
      </div>
    </div>
  );
};

export default CompteView;
