import React from 'react';

const LoadingSpinner = ({ message = 'Chargement...' }: { message?: string }) => {
  return (
    <div className="flex flex-col items-center justify-center min-h-[160px]">
      <div className="w-10 h-10 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin mb-3" />
      <div className="text-sm text-gray-600">{message}</div>
    </div>
  );
};

export default LoadingSpinner;