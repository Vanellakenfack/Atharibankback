import React, { useState, useEffect } from 'react';
import NotificationDropdown from './NotificationDropdown';

function Navbar() {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    try {
      const response = await fetch('/ajax/notifications');
      const data = await response.json();
      setNotifications(data);
      setUnreadCount(data.filter(n => !n.lu).length);
    } catch (error) {
      console.error('Error fetching notifications:', error);
    }
  };

  const handleLogout = (e) => {
    e.preventDefault();
    document.getElementById('logout-form').submit();
  };

  return (
    <nav className="bg-gradient-to-r from-blue-800 to-blue-600 shadow-lg px-8 py-4 sticky top-0 z-50 backdrop-blur-sm">
      <div className="flex justify-between items-center">
        <a className="text-white font-extrabold text-xl flex items-center gap-2 hover:text-yellow-200 transition-colors" href="#">
          🏦 Athari-Financial
        </a>
        <div className="flex items-center space-x-4">
          <div className="relative">
            <button className="text-white hover:bg-white/10 px-3 py-2 rounded-lg transition-colors relative">
              <i className="fas fa-bell"></i>
              {unreadCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1 py-0.5">
                  {unreadCount}
                </span>
              )}
            </button>
            <NotificationDropdown notifications={notifications} fetchNotifications={fetchNotifications} />
          </div>
          <a className="text-white hover:bg-white/10 px-3 py-2 rounded-lg transition-colors flex items-center gap-2" href="#" onClick={handleLogout}>
            <i className="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>
    </nav>
  );
}

export default Navbar;