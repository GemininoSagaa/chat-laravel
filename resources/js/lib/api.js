import axios from 'axios';

// Determinar la URL base correcta
const currentPath = window.location.pathname;
let baseURL = window.location.origin;

// Si estamos en un subdirectorio, ajustar la URL base para incluir ese directorio
if (currentPath.includes('/laravel/chat-laravel/public')) {
    baseURL = `${baseURL}/laravel/chat-laravel/public`;
}

console.log('API Base URL:', baseURL);

// Configurar axios con la URL base y los headers predeterminados
const api = axios.create({
    baseURL: baseURL,
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    }
});

// Interceptor para añadir el token de autenticación si existe
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Log de la solicitud
        console.log('API Request:', config.method?.toUpperCase(), config.url);
        
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Interceptor para manejar errores comunes
api.interceptors.response.use(
    response => {
        console.log('API Response:', response.config.url, response.data);
        return response;
    },
    error => {
        if (error.response) {
            console.error('API Error:', error.config.url, error.response.status, error.response.data);
            
            // Si el error es 401 (No autorizado), limpiar el token
            if (error.response.status === 401) {
                localStorage.removeItem('token');
            }
        } else if (error.request) {
            console.error('No se recibió respuesta del servidor:', error.request);
        } else {
            console.error('Error al configurar la petición:', error.message);
        }
        
        return Promise.reject(error);
    }
);

export default api;