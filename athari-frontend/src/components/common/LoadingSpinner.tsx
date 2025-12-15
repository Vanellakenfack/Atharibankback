import React from 'react';

const LoadingSpinner = ({ message = 'Chargement...' }) => {
  return (
    <div className="flex flex-col items-center justify-center min-h-[200px]">
      {/* Spinner */}
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-600 mb-4"></div>
      {/* Texte */}
      <p className="text-gray-600 text-sm">{message}</p>
    </div>
  );
};

export default LoadingSpinner;