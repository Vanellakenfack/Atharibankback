import React from 'react';
import {
  Paper,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Box,
  Button,
  Grid,
  IconButton,
} from '@mui/material';
import {
  Search as SearchIcon,
  FilterList as FilterIcon,
  Clear as ClearIcon,
} from '@mui/icons-material';

const AccountFilters = ({ filters, onFilterChange, onReset }) => {
  const handleChange = (e) => {
    const { name, value } = e.target;
    onFilterChange({ ...filters, [name]: value });
  };

  const handleSearch = (e) => {
    if (e.key === 'Enter') {
      onFilterChange({ ...filters });
    }
  };

  return (
    <Paper sx={{ p: 2, mb: 3 }}>
      <Grid container spacing={2} alignItems="center">
        <Grid item xs={12} md={4}>
          <TextField
            fullWidth
            label="Rechercher"
            name="search"
            value={filters.search || ''}
            onChange={handleChange}
            onKeyPress={handleSearch}
            InputProps={{
              startAdornment: <SearchIcon sx={{ mr: 1, color: 'action.active' }} />,
            }}
            placeholder="Numéro, client, compte..."
          />
        </Grid>

        <Grid item xs={12} md={2}>
          <FormControl fullWidth>
            <InputLabel>Type de compte</InputLabel>
            <Select
              name="accountType"
              value={filters.accountType || ''}
              onChange={handleChange}
              label="Type de compte"
            >
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="10">Compte courant</MenuItem>
              <MenuItem value="23">Mata journalier</MenuItem>
              <MenuItem value="22">Mata boost bloqué</MenuItem>
              <MenuItem value="07">Épargne bloquée</MenuItem>
            </Select>
          </FormControl>
        </Grid>

        <Grid item xs={12} md={2}>
          <FormControl fullWidth>
            <InputLabel>Agence</InputLabel>
            <Select
              name="agency"
              value={filters.agency || ''}
              onChange={handleChange}
              label="Agence"
            >
              <MenuItem value="">Toutes</MenuItem>
              <MenuItem value="001">001 - RÉUSSITE</MenuItem>
              <MenuItem value="002">002 - AUDACE</MenuItem>
              <MenuItem value="003">003 - SPEED</MenuItem>
              <MenuItem value="004">004 - POWER</MenuItem>
              <MenuItem value="005">005 - IMANI</MenuItem>
            </Select>
          </FormControl>
        </Grid>

        <Grid item xs={12} md={2}>
          <FormControl fullWidth>
            <InputLabel>Statut</InputLabel>
            <Select
              name="status"
              value={filters.status || ''}
              onChange={handleChange}
              label="Statut"
            >
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="active">Actif</MenuItem>
              <MenuItem value="pending">En attente</MenuItem>
              <MenuItem value="blocked">Bloqué</MenuItem>
              <MenuItem value="closed">Fermé</MenuItem>
            </Select>
          </FormControl>
        </Grid>

        <Grid item xs={12} md={2}>
          <Box sx={{ display: 'flex', gap: 1 }}>
            <Button
              variant="contained"
              startIcon={<FilterIcon />}
              onClick={() => onFilterChange(filters)}
            >
              Filtrer
            </Button>
            <IconButton
              onClick={onReset}
              color="secondary"
              title="Réinitialiser les filtres"
            >
              <ClearIcon />
            </IconButton>
          </Box>
        </Grid>
      </Grid>
    </Paper>
  );
};

export default AccountFilters;