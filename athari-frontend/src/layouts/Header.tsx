import React, { useState } from 'react';
import {
  AppBar,
  Toolbar,
  IconButton,
  InputBase,
  Badge,
  Box,
  Avatar,
  Menu,
  MenuItem,
  Tooltip,
} from '@mui/material';
import {
  Search as SearchIcon,
  Notifications as NotificationsIcon,
  Settings as SettingsIcon,
  Logout as LogoutIcon,
  MoreVert as MoreVertIcon,
} from '@mui/icons-material';
import { styled } from '@mui/material/styles';

const StyledAppBar = styled(AppBar)(({ theme }) => ({
  backgroundColor: '#ffffff',
  color: theme.palette.text.primary,
  boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
  borderBottom: `1px solid ${theme.palette.divider}`,
}));

const SearchWrapper = styled(Box)(({ theme }) => ({
  position: 'relative',
  borderRadius: theme.shape.borderRadius,
  backgroundColor: theme.palette.mode === 'light' ? '#f5f5f5' : '#f0f0f0',
  marginLeft: theme.spacing(2),
  marginRight: theme.spacing(2),
  width: '100%',
  maxWidth: 400,
  [theme.breakpoints.down('sm')]: {
    width: 'auto',
    maxWidth: 200,
  },
}));

const SearchIconWrapper = styled(Box)(({ theme }) => ({
  padding: theme.spacing(0, 2),
  height: '100%',
  position: 'absolute',
  pointerEvents: 'none',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  color: theme.palette.text.secondary,
}));

const StyledInputBase = styled(InputBase)(({ theme }) => ({
  color: 'inherit',
  width: '100%',
  '& .MuiInputBase-input': {
    padding: theme.spacing(1, 1, 1, 0),
    paddingLeft: `calc(1em + ${theme.spacing(4)})`,
    transition: theme.transitions.create('width'),
    width: '100%',
    [theme.breakpoints.down('sm')]: {
      width: '0ch',
      '&:focus': {
        width: '20ch',
      },
    },
  },
}));

export const Header: React.FC = () => {
  const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
  const [notifAnchorEl, setNotifAnchorEl] = useState<null | HTMLElement>(null);

  const handleProfileMenuOpen = (event: React.MouseEvent<HTMLElement>) => {
    setAnchorEl(event.currentTarget);
  };

  const handleProfileMenuClose = () => {
    setAnchorEl(null);
  };

  const handleNotificationsOpen = (event: React.MouseEvent<HTMLElement>) => {
    setNotifAnchorEl(event.currentTarget);
  };

  const handleNotificationsClose = () => {
    setNotifAnchorEl(null);
  };

  return (
    <StyledAppBar position="sticky">
      <Toolbar sx={{ display: 'flex', justifyContent: 'space-between', minHeight: 64 }}>
        {/* Logo/Title */}
        <Box sx={{ fontWeight: 700, fontSize: '1.2rem', color: '#1a1a2e' }}>
          ATHARIBANK
        </Box>

        {/* Search Bar */}
        <SearchWrapper>
          <SearchIconWrapper>
            <SearchIcon sx={{ fontSize: 20 }} />
          </SearchIconWrapper>
          <StyledInputBase
            placeholder="Rechercher client, compte..."
            inputProps={{ 'aria-label': 'search' }}
          />
        </SearchWrapper>

        {/* Right Actions */}
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          {/* Notifications */}
          <Tooltip title="Notifications">
            <IconButton
              size="large"
              color="inherit"
              onClick={handleNotificationsOpen}
              sx={{ '&:hover': { backgroundColor: '#f5f5f5' } }}
            >
              <Badge badgeContent={3} color="error">
                <NotificationsIcon sx={{ color: '#1a1a2e' }} />
              </Badge>
            </IconButton>
          </Tooltip>

          {/* Notifications Menu */}
          <Menu
            anchorEl={notifAnchorEl}
            open={Boolean(notifAnchorEl)}
            onClose={handleNotificationsClose}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            transformOrigin={{ vertical: 'top', horizontal: 'right' }}
          >
            <MenuItem onClick={handleNotificationsClose}>
              Nouvelle transaction: +5000 DA
            </MenuItem>
            <MenuItem onClick={handleNotificationsClose}>
              Client créé avec succès
            </MenuItem>
            <MenuItem onClick={handleNotificationsClose}>
              Mise à jour système disponible
            </MenuItem>
          </Menu>

          {/* Settings */}
          <Tooltip title="Paramètres">
            <IconButton
              size="large"
              color="inherit"
              sx={{ '&:hover': { backgroundColor: '#f5f5f5' } }}
            >
              <SettingsIcon sx={{ color: '#1a1a2e' }} />
            </IconButton>
          </Tooltip>

          {/* Divider */}
          <Box sx={{ width: 1, height: 32, mx: 1, borderRight: '1px solid #e0e0e0' }} />

          {/* User Profile */}
          <Tooltip title="Profil utilisateur">
            <IconButton
              onClick={handleProfileMenuOpen}
              size="small"
              sx={{ ml: 1, '&:hover': { backgroundColor: '#f5f5f5' } }}
            >
              <Avatar
                sx={{
                  width: 32,
                  height: 32,
                  background: 'linear-gradient(45deg, #FF6B9D 0%, #C44569 100%)',
                  fontSize: '0.9rem',
                  fontWeight: 700,
                }}
              >
                AD
              </Avatar>
            </IconButton>
          </Tooltip>

          {/* Profile Menu */}
          <Menu
            anchorEl={anchorEl}
            open={Boolean(anchorEl)}
            onClose={handleProfileMenuClose}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            transformOrigin={{ vertical: 'top', horizontal: 'right' }}
          >
            <MenuItem disabled>
              <Box>
                <Box sx={{ fontWeight: 600 }}>Admin User</Box>
                <Box sx={{ fontSize: '0.85rem', color: '#999' }}>admin@atharibank.com</Box>
              </Box>
            </MenuItem>
            <MenuItem onClick={handleProfileMenuClose}>Profil</MenuItem>
            <MenuItem onClick={handleProfileMenuClose}>Paramètres</MenuItem>
            <MenuItem onClick={handleProfileMenuClose} sx={{ color: 'error.main' }}>
              <LogoutIcon sx={{ mr: 1, fontSize: 20 }} />
              Déconnexion
            </MenuItem>
          </Menu>

          {/* More Options */}
          <Tooltip title="Plus d'options">
            <IconButton size="small" sx={{ '&:hover': { backgroundColor: '#f5f5f5' } }}>
              <MoreVertIcon sx={{ color: '#1a1a2e' }} />
            </IconButton>
          </Tooltip>
        </Box>
      </Toolbar>
    </StyledAppBar>
  );
};

export default Header;
