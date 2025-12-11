// src/api/ApiClient.jsx ou où vous l'avez placé

import axios from 'axios'
// L'import de React n'est pas nécessaire ici

const ApiClient = axios.create({
// *** CORRECTION DE L'URL ***
    baseURL: 'http://127.0.0.1:8000/api', // Utilisez http, et 127.0.0.1 ou localhost
    //headers par defaut
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

//intercepteur de requete pour ajouter le token d'authentification
ApiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('authToken')
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

export default ApiClient; // Exportation correcte