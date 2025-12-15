import React from 'react';

const LoadingSpinner = ({ message = 'Chargement...' }: { message?: string }) => {
  return (
<<<<<<< HEAD
    <div className="flex flex-col items-center justify-center min-h-[160px]">
      <div className="w-10 h-10 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin mb-3" />
      <div className="text-sm text-gray-600">{message}</div>
=======
    <div className="flex flex-col items-center justify-center min-h-[200px]">
      {/* Spinner */}
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-600 mb-4"></div>
      {/* Texte */}
      <p className="text-gray-600 text-sm">{message}</p>
>>>>>>> dev
    </div>
  );
};

export default LoadingSpinner;