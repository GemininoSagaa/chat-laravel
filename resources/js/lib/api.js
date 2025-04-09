import axios from 'axios';

// Utilidad para formateo y logs consistentes
const logger = {
    REQUEST: (method, url) => `[API REQUEST] ${new Date().toLocaleTimeString()} | ${method.toUpperCase()} ${url}`,
    RESPONSE: (status, method, url) => `[API RESPONSE] ${new Date().toLocaleTimeString()} | ${status} | ${method.toUpperCase()} ${url}`,
    ERROR: (method, url) => `[API ERROR] ${new Date().toLocaleTimeString()} | ${method.toUpperCase()} ${url}`,
    
    // Logs detallados con formato personalizado
    log: (title, ...data) => {
        console.log(`%c${title}`, 'color: #3490dc; font-weight: bold;');
        if (data.length > 0) {
            data.forEach(item => console.log(item));
        }
    },
    
    error: (title, ...data) => {
        console.error(`%c${title}`, 'color: #e74c3c; font-weight: bold;');
        if (data.length > 0) {
            data.forEach(item => console.error(item));
        }
    },
    
    success: (title, ...data) => {
        console.log(`%c${title}`, 'color: #2ecc71; font-weight: bold;');
        if (data.length > 0) {
            data.forEach(item => console.log(item));
        }
    },
    
    warning: (title, ...data) => {
        console.warn(`%c${title}`, 'color: #f39c12; font-weight: bold;');
        if (data.length > 0) {
            data.forEach(item => console.warn(item));
        }
    }
};

// Determinar la URL base correcta
const currentPath = window.location.pathname;
let baseURL = window.location.origin;

// Si estamos en un subdirectorio, ajustar la URL base para incluir ese directorio
if (currentPath.includes('/laravel/chat-laravel/public')) {
    baseURL = `${baseURL}/laravel/chat-laravel/public`;
}

logger.log('Inicializando API', `URL Base: ${baseURL}`);

// Configurar axios con la URL base y los headers predeterminados
const api = axios.create({
    baseURL: baseURL,
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    }
});

// Interceptor para añadir el token de autenticación y agregar logs
api.interceptors.request.use(
    config => {
        const timestamp = new Date();
        const token = localStorage.getItem('token');
        
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Log detallado de la solicitud
        logger.log(
            logger.REQUEST(config.method, config.url),
            { 
                timestamp,
                headers: config.headers,
                params: config.params || {},
                data: config.data || {}
            }
        );
        
        // Agregar timestamp para medir tiempo de respuesta
        config._requestTime = timestamp;
        
        return config;
    },
    error => {
        logger.error(
            '[API REQUEST ERROR]',
            error
        );
        return Promise.reject(error);
    }
);

// Interceptor para manejar respuestas y errores con logs detallados
api.interceptors.response.use(
    response => {
        const endTime = new Date();
        const duration = response.config._requestTime 
            ? endTime - response.config._requestTime 
            : null;
        
        // Log detallado de la respuesta exitosa
        logger.success(
            logger.RESPONSE(response.status, response.config.method, response.config.url),
            { 
                duration: duration ? `${duration}ms` : 'unknown',
                data: response.data
            }
        );
        
        return response;
    },
    error => {
        const endTime = new Date();
        const requestConfig = error.config || {};
        const duration = requestConfig._requestTime 
            ? endTime - requestConfig._requestTime 
            : null;
        
        // Log detallado del error
        logger.error(
            logger.ERROR(requestConfig.method || 'UNKNOWN', requestConfig.url || 'UNKNOWN'),
            { 
                duration: duration ? `${duration}ms` : 'unknown',
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                message: error.message
            }
        );
        
        // Log específico para errores de autenticación
        if (error.response && error.response.status === 401) {
            logger.warning('[AUTH] Token inválido o expirado - Cerrando sesión');
            localStorage.removeItem('token');
        }
        
        return Promise.reject(error);
    }
);

// Métodos auxiliares para llamadas comunes
api.helpers = {
    // Obtener datos del usuario autenticado
    getUser: async () => {
        try {
            logger.log('[AUTH] Verificando usuario actual');
            const response = await api.get('/api/user');
            logger.success('[AUTH] Usuario autenticado obtenido', response.data);
            return response.data;
        } catch (error) {
            logger.error('[AUTH] Error al obtener usuario actual', error);
            throw error;
        }
    },
    
    // Obtener amigos y solicitudes
    getFriends: async () => {
        try {
            logger.log('[FRIENDS] Obteniendo amigos y solicitudes');
            const response = await api.get('/api/friends');
            logger.success('[FRIENDS] Datos obtenidos correctamente', {
                amigos: response.data.friends?.length || 0,
                solicitudesRecibidas: response.data.received_requests?.length || 0,
                solicitudesEnviadas: response.data.sent_requests?.length || 0
            });
            return response.data;
        } catch (error) {
            logger.error('[FRIENDS] Error al obtener amigos', error);
            throw error;
        }
    },
    
    // Enviar una solicitud de amistad
    sendFriendRequest: async (email) => {
        try {
            logger.log('[FRIENDS] Enviando solicitud de amistad a:', email);
            const response = await api.post('/api/friends/request', { email });
            logger.success('[FRIENDS] Solicitud enviada correctamente', response.data);
            return response.data;
        } catch (error) {
            logger.error('[FRIENDS] Error al enviar solicitud de amistad', error);
            throw error;
        }
    }
};

export default api;